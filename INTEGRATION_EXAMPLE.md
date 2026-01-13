# Integration Example for Carrier Modules

This document provides practical examples for integrating your carrier module with the Shipping Labels module.

## Basic Integration

### Step 1: Check if the module is installed

```php
public function isShippingLabelsModuleAvailable()
{
    return Module::isInstalled('extrashippinglabels') && Module::isEnabled('extrashippinglabels');
}
```

### Step 2: Generate and Register a Label

```php
use PrestaShop\Module\ExtraShippingLabels\Repository\ShippingLabelRepository;

public function generateShippingLabel($orderId)
{
    // Check if the base module is available
    if (!$this->isShippingLabelsModuleAvailable()) {
        throw new Exception('Shipping Labels module is required');
    }

    // Load order
    $order = new Order($orderId);
    if (!Validate::isLoadedObject($order)) {
        throw new Exception('Order not found');
    }

    // Call your carrier API to generate label
    $labelData = $this->callCarrierApiToGenerateLabel($order);

    // Get the repository
    /** @var ShippingLabelRepository $repository */
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');

    // Save PDF file
    $labelsDir = $repository->getLabelsDirectory();
    $filename = sprintf(
        '%s_order_%d_%s.pdf',
        $this->name,
        $orderId,
        date('YmdHis')
    );

    $fullPath = $labelsDir . $filename;
    file_put_contents($fullPath, $labelData['pdf_content']);

    // Register in database
    $labelId = $repository->createLabel(
        orderId: $orderId,
        moduleName: $this->name,
        trackingNumber: $labelData['tracking_number'],
        labelFilepath: $filename
    );

    if ($labelId === 0) {
        // Cleanup on failure
        @unlink($fullPath);
        throw new Exception('Failed to register shipping label');
    }

    return $labelId;
}
```

## Advanced Integration

### With Error Handling

```php
public function generateShippingLabelWithErrorHandling($orderId)
{
    try {
        // Check prerequisites
        if (!$this->isShippingLabelsModuleAvailable()) {
            $this->context->controller->errors[] = $this->l('Shipping Labels module is not available');
            return false;
        }

        // Generate label
        $labelId = $this->generateShippingLabel($orderId);

        // Success message
        $this->context->controller->confirmations[] = sprintf(
            $this->l('Shipping label generated successfully (ID: %d)'),
            $labelId
        );

        return $labelId;

    } catch (Exception $e) {
        // Log error
        PrestaShopLogger::addLog(
            sprintf('Error generating shipping label for order %d: %s', $orderId, $e->getMessage()),
            3,
            null,
            'Order',
            $orderId
        );

        // User-friendly error
        $this->context->controller->errors[] = $this->l('An error occurred while generating the shipping label');

        return false;
    }
}
```

### With Automatic Order State Update

```php
public function generateLabelAndUpdateOrder($orderId)
{
    // Generate label
    $labelId = $this->generateShippingLabel($orderId);

    if ($labelId === 0) {
        return false;
    }

    // Get tracking number
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');
    $label = $repository->getLabelById($labelId);

    // Update order with tracking number
    $order = new Order($orderId);
    $order->shipping_number = $label->tracking_number;
    $order->update();

    // Change order state to "Shipped"
    $newOrderStateId = Configuration::get('PS_OS_SHIPPING');
    $orderHistory = new OrderHistory();
    $orderHistory->id_order = $orderId;
    $orderHistory->changeIdOrderState($newOrderStateId, $orderId, true);
    $orderHistory->addWithemail();

    return $labelId;
}
```

### Bulk Label Generation

```php
public function generateLabelsForMultipleOrders(array $orderIds)
{
    $results = [
        'success' => [],
        'errors' => [],
    ];

    foreach ($orderIds as $orderId) {
        try {
            $labelId = $this->generateShippingLabel($orderId);
            $results['success'][$orderId] = $labelId;
        } catch (Exception $e) {
            $results['errors'][$orderId] = $e->getMessage();
        }
    }

    return $results;
}
```

## Integration with Order Admin Page

### Add a "Generate Label" Button

```php
// In your module's main class
public function hookDisplayAdminOrderTabContent($params)
{
    $orderId = (int)$params['id_order'];

    // Check if label already exists
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');
    $existingLabels = $repository->getLabelsForOrder($orderId);

    $this->context->smarty->assign([
        'order_id' => $orderId,
        'has_label' => !empty($existingLabels),
        'module_name' => $this->name,
        'ajax_url' => $this->context->link->getAdminLink('AdminModules', true) .
                      '&configure=' . $this->name . '&ajax=1',
    ]);

    return $this->display(__FILE__, 'views/templates/admin/order_tab.tpl');
}
```

### AJAX Handler for Label Generation

```php
// In your module's main class
public function ajaxProcessGenerateLabel()
{
    $orderId = (int)Tools::getValue('order_id');

    if (!$orderId) {
        die(json_encode(['success' => false, 'error' => 'Invalid order ID']));
    }

    try {
        $labelId = $this->generateShippingLabel($orderId);

        die(json_encode([
            'success' => true,
            'label_id' => $labelId,
            'message' => $this->l('Label generated successfully'),
        ]));
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]));
    }
}
```

### Template (order_tab.tpl)

```smarty
<div class="panel">
    <div class="panel-heading">
        <i class="icon-truck"></i>
        {l s='Shipping Label' mod='yourmodule'}
    </div>
    <div class="panel-body">
        {if $has_label}
            <div class="alert alert-success">
                {l s='A shipping label has already been generated for this order.' mod='yourmodule'}
            </div>
        {else}
            <button type="button" class="btn btn-primary" id="generate-label-btn" data-order-id="{$order_id}">
                <i class="icon-file-pdf-o"></i>
                {l s='Generate Shipping Label' mod='yourmodule'}
            </button>
        {/if}
    </div>
</div>

<script>
$(document).ready(function() {
    $('#generate-label-btn').click(function() {
        var orderId = $(this).data('order-id');
        var btn = $(this);

        btn.prop('disabled', true);
        btn.html('<i class="icon-spinner icon-spin"></i> {l s='Generating...' mod='yourmodule'}');

        $.ajax({
            url: '{$ajax_url|escape:'quotes':'UTF-8'}',
            type: 'POST',
            data: {
                action: 'GenerateLabel',
                order_id: orderId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                    btn.prop('disabled', false);
                    btn.html('<i class="icon-file-pdf-o"></i> {l s='Generate Shipping Label' mod='yourmodule'}');
                }
            }
        });
    });
});
</script>
```

## Enriching Label Display with Hooks

### Add Carrier-Specific Information

```php
public function hookActionGetShippingLabelData($params)
{
    $label = &$params['label'];

    // Only modify labels created by this module
    if ($label['module_name'] !== $this->name) {
        return;
    }

    // Add carrier-specific data
    $label['carrier_name'] = 'My Carrier';
    $label['service_type'] = 'Express';

    // Add tracking URL
    if (!empty($label['tracking_number'])) {
        $label['tracking_url'] = sprintf(
            'https://tracking.mycarrier.com/track/%s',
            urlencode($label['tracking_number'])
        );
    }
}
```

### Add Custom Actions

```php
public function hookDisplayShippingLabelActions($params)
{
    $labelId = (int)$params['id_shipping_label'];

    // Get label details
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');
    $label = $repository->getLabelById($labelId);

    // Only add actions for labels from this module
    if (!$label || $label->module_name !== $this->name) {
        return '';
    }

    $this->context->smarty->assign([
        'label_id' => $labelId,
        'tracking_number' => $label->tracking_number,
        'module_url' => $this->context->link->getAdminLink('AdminModules', true) .
                        '&configure=' . $this->name,
    ]);

    return $this->display(__FILE__, 'views/templates/admin/label_actions.tpl');
}
```

## Testing Your Integration

### Unit Test Example

```php
class ShippingLabelIntegrationTest extends PHPUnit\Framework\TestCase
{
    private $module;
    private $repository;

    protected function setUp(): void
    {
        $this->module = Module::getInstanceByName('yourmodule');
        $this->repository = $this->module->get('prestashop.module.extrashippinglabels.repository');
    }

    public function testLabelCreation()
    {
        $orderId = 1; // Use a test order
        $labelId = $this->repository->createLabel(
            orderId: $orderId,
            moduleName: 'yourmodule',
            trackingNumber: 'TEST123',
            labelFilepath: 'test.pdf'
        );

        $this->assertGreaterThan(0, $labelId);

        // Cleanup
        $this->repository->deleteLabel($labelId);
    }

    public function testLabelRetrieval()
    {
        $orderId = 1;
        $labels = $this->repository->getLabelsForOrder($orderId);

        $this->assertIsArray($labels);
    }
}
```

## Common Patterns

### Prevent Duplicate Labels

```php
public function generateShippingLabelIfNotExists($orderId)
{
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');

    // Check if label already exists
    $existingLabels = $repository->getLabelsForOrder($orderId);

    foreach ($existingLabels as $label) {
        if ($label['module_name'] === $this->name) {
            // Label already exists
            return (int)$label['id_shipping_label'];
        }
    }

    // Generate new label
    return $this->generateShippingLabel($orderId);
}
```

### Update Tracking Number Later

```php
public function updateTrackingNumber($labelId, $trackingNumber)
{
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');

    return $repository->updateLabel($labelId, [
        'tracking_number' => $trackingNumber,
    ]);
}
```

### Regenerate Label

```php
public function regenerateLabel($orderId)
{
    $repository = $this->get('prestashop.module.extrashippinglabels.repository');

    // Find existing label
    $existingLabels = $repository->getLabelsForOrder($orderId);

    foreach ($existingLabels as $label) {
        if ($label['module_name'] === $this->name) {
            // Delete old label
            $repository->deleteLabel($label['id_shipping_label']);
            break;
        }
    }

    // Generate new label
    return $this->generateShippingLabel($orderId);
}
```

## Best Practices

1. **Always check module availability** before using the repository
2. **Handle exceptions** gracefully and provide user feedback
3. **Use unique filenames** to avoid conflicts (include module name, order ID, timestamp)
4. **Clean up files** on failure
5. **Log errors** for debugging
6. **Don't generate duplicate labels** for the same order/module combination
7. **Use hooks** to enrich label data without modifying core files
8. **Test thoroughly** with your carrier's API sandbox before production

## Support

For issues or questions about integration, please refer to the PrestaShop developer documentation or open an issue on the PrestaShop GitHub repository.
