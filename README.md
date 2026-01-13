# Shipping Labels Module

This module provides a standard base for managing and displaying shipping labels in PrestaShop 9.

## Purpose

This module is designed as an **architectural foundation** for shipping label management. It does not generate shipping labels itself - labels are created by external carrier modules (Colissimo, Chronopost, UPS, DHL, etc.).

## Features

- Centralized storage for shipping labels
- Display labels on order pages
- Download individual labels
- Bulk print multiple labels (merged into one PDF)
- Secure file management (labels stored in `/var/shipping_labels/`)
- Repository pattern for clean code architecture
- Extensible through hooks

## For Module Developers

### Creating a Shipping Label

To create a shipping label from your carrier module, use the repository:

```php
// Get the repository
$repository = $this->get('prestashop.module.extrashippinglabels.repository');

// Create a label
$labelId = $repository->createLabel(
    orderId: 123,
    moduleName: 'mycarriermodule',
    trackingNumber: 'TRACK123456',
    labelFilepath: 'label_123456.pdf'  // Filename only, will be stored in /var/shipping_labels/
);

if ($labelId > 0) {
    // Label created successfully
}
```

### Storing Label Files

When you generate a PDF label, save it to the labels directory:

```php
$repository = $this->get('prestashop.module.extrashippinglabels.repository');
$labelsDir = $repository->getLabelsDirectory();

// Generate your PDF content
$pdfContent = generateYourPdfLabel($order);

// Create a unique filename
$filename = 'label_' . $orderId . '_' . time() . '.pdf';
$filepath = $labelsDir . $filename;

// Save the file
file_put_contents($filepath, $pdfContent);

// Register in database
$labelId = $repository->createLabel(
    orderId: $orderId,
    moduleName: 'mycarriermodule',
    trackingNumber: $trackingNumber,
    labelFilepath: $filename  // Store only the filename
);
```

### Updating a Label

```php
$repository = $this->get('prestashop.module.extrashippinglabels.repository');

$repository->updateLabel($labelId, [
    'tracking_number' => 'NEW_TRACKING_123',
    'label_filepath' => 'new_label.pdf',
]);
```

### Retrieving Labels

```php
$repository = $this->get('prestashop.module.extrashippinglabels.repository');

// Get all labels for an order
$labels = $repository->getLabelsForOrder($orderId);

// Get a single label
$label = $repository->getLabelById($labelId);

// Find by tracking number
$labels = $repository->findByTrackingNumber('TRACK123');

// Find by module
$labels = $repository->findByModule('mycarriermodule');
```

### Deleting a Label

```php
$repository = $this->get('prestashop.module.extrashippinglabels.repository');

// Delete a label (also deletes the file)
$success = $repository->deleteLabel($labelId);
```

## Available Hooks

### actionGetShippingLabelData

Modify label data before display (e.g., add download URL):

```php
public function hookActionGetShippingLabelData($params)
{
    $label = &$params['label'];

    // Add custom data
    $label['custom_field'] = 'custom value';
    $label['download_url'] = 'https://...';
}
```

### actionModifyShippingLabelsForOrder

Modify the entire labels list before display on order page:

```php
public function hookActionModifyShippingLabelsForOrder($params)
{
    $labels = &$params['labels'];

    // Filter or modify labels
    foreach ($labels as &$label) {
        // Your modifications
    }
}
```

### displayShippingLabelActions

Add custom actions to the labels display on order pages:

```php
public function hookDisplayShippingLabelActions($params)
{
    $labelId = $params['id_shipping_label'];

    return '<a href="...">Custom Action</a>';
}
```

## Database Schema

Table: `ps_shipping_label`

| Column | Type | Description |
|--------|------|-------------|
| id_shipping_label | INT | Primary key |
| id_order | INT | Order ID |
| tracking_number | VARCHAR(255) | Tracking number (optional) |
| module_name | VARCHAR(64) | Name of the module that created the label |
| label_filepath | VARCHAR(255) | Filename of the PDF label |
| date_add | DATETIME | Creation date |
| date_upd | DATETIME | Last update date |

## Security Features

- Path traversal protection on file operations
- AdminSecurity attributes on all controller actions
- Files stored outside module directory
- Secure filename validation

## Architecture

- **Repository Pattern**: `ShippingLabelRepository` centralizes all business logic
- **Symfony Controllers**: Modern PrestaShop 9 architecture
- **Grid System**: Admin interface uses PrestaShop's Grid component
- **Dependency Injection**: Full DI support via services.yml

## File Storage

Labels are stored in:
```
/var/shipping_labels/
```

This location:
- Is outside the module directory (survives module updates)
- Is gitignored by default
- Is managed automatically by the repository

## Examples

### Complete Example: Carrier Module Integration

```php
class MyCarrierModule extends CarrierModule
{
    public function generateLabelForOrder($orderId)
    {
        // 1. Call your carrier's API
        $apiResponse = $this->callCarrierApi($orderId);

        // 2. Get the PDF from API
        $pdfContent = $apiResponse['label_pdf'];
        $trackingNumber = $apiResponse['tracking_number'];

        // 3. Save to filesystem
        $repository = $this->get('prestashop.module.extrashippinglabels.repository');
        $labelsDir = $repository->getLabelsDirectory();
        $filename = 'carrier_' . $orderId . '_' . time() . '.pdf';

        file_put_contents($labelsDir . $filename, $pdfContent);

        // 4. Register in database
        $labelId = $repository->createLabel(
            orderId: $orderId,
            moduleName: $this->name,
            trackingNumber: $trackingNumber,
            labelFilepath: $filename
        );

        return $labelId;
    }
}
```

## Troubleshooting

### Labels not appearing on order page

1. Check that the module is installed and enabled
2. Verify the hook `displayAdminOrderMainBottom` is registered
3. Check that labels exist in database for the order

### PDF merge not working

The bulk print feature requires TCPDF (included in PrestaShop). If you encounter issues:
- Verify TCPDF is available: `class_exists('TCPDF')`
- Check PHP memory limit (PDF merging can be memory-intensive)
- Consider implementing a custom PDF merger using your preferred library

### Permission errors on /var/shipping_labels/

Ensure the directory has proper permissions:
```bash
chmod 755 var/shipping_labels/
chown www-data:www-data var/shipping_labels/
```

## Contributing

This is a core PrestaShop module. Contributions should follow PrestaShop coding standards.

## License

Academic Free License (AFL 3.0)
