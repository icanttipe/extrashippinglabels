<?php

namespace PrestaShop\Module\ExtraShippingLabels\Controller;

use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Hook;

class BulkLabelGenerationController extends PrestaShopAdminController
{
    /**
     * Handles the bulk action for generating shipping labels.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function generateBulkAction(Request $request): RedirectResponse
    {
        // The grid component sends the selected IDs in this parameter
        $orderIds = $request->request->all('order_orders_bulk');

        if (is_array($orderIds) && !empty($orderIds)) {
            $generatedCount = 0;
            foreach ($orderIds as $orderId) {
                // We trigger our custom hook for each selected order
                Hook::exec('actionOrderGenerateShippingLabel', [
                    'id_order' => (int)$orderId,
                ]);
                $generatedCount++;
            }

            // Add a confirmation message (flash bag)
            $this->addFlash(
                'success',
                $this->trans('Hook for generating labels triggered for %d selected orders.', ['%d' => $generatedCount], 'Modules.Extrashippinglabels.Admin')
            );
        } else {
            // Add an error message if no orders were selected
            $this->addFlash(
                'error',
                $this->trans('You must select at least one order.', [], 'Modules.Extrashippinglabels.Admin')
            );
        }

        // Redirect back to the orders list
        return $this->redirectToRoute('admin_orders_index');
    }
}
