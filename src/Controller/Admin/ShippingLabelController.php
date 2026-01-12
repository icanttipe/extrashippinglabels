<?php

namespace PrestaShop\Module\ExtraShippingLabels\Controller\Admin;

use PrestaShop\Module\ExtraShippingLabels\Filters\ShippingLabelFilters;
use PrestaShop\Module\ExtraShippingLabels\Grid\Definition\Factory\ShippingLabelGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShopBundle\Service\Grid\ResponseBuilder as GridResponseBuilder;
use ShippingLabel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Validate;

class ShippingLabelController extends PrestaShopAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function indexAction(
        ShippingLabelFilters $filters,
        #[Autowire(service: 'prestashop.module.extrashippinglabels.grid.factory')]
        GridFactoryInterface $gridFactory
    ): Response {
        $grid = $gridFactory->getGrid($filters);

        return $this->render('@Modules/extrashippinglabels/views/templates/admin/list.html.twig', [
            'enable_sidebar' => true,
            'layout_title' => 'Shipping Labels',
            'grid' => $this->presentGrid($grid),
        ]);
    }

    public function searchAction(
        Request $request,
        GridResponseBuilder $gridResponseBuilder,
        ShippingLabelGridDefinitionFactory $gridDefinitionFactory
    ) {
        return $gridResponseBuilder->buildSearchResponse(
            $gridDefinitionFactory,
            $request,
            ShippingLabelFilters::class,
            'ps_extrashippinglabels_labels_index'
        );
    }

    public function deleteAction(int $shippingLabelId)
    {
        $shippingLabel = new ShippingLabel($shippingLabelId);

        if (!Validate::isLoadedObject($shippingLabel)) {
            $this->addFlash('error', $this->trans('Cannot find shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        // Delete the associated file from the filesystem
        $filePath = _PS_MODULE_DIR_ . 'shippinglabels/labels/' . $shippingLabel->label_filepath;
        if (file_exists($filePath) && is_file($filePath)) {
            if (!unlink($filePath)) {
                $this->addFlash('error', $this->trans('Could not delete file for shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
                return $this->redirectToList();
            }
        } else {
            $this->addFlash('warning', $this->trans('Label file not found for shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
        }

        if ($shippingLabel->delete()) {
            $this->addFlash('success', $this->trans('Successful deletion.', [], 'Admin.Notifications.Success'));
        } else {
            $this->addFlash('error', $this->trans('Could not delete shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
        }

        return $this->redirectToList();
    }

    public function bulkDeleteAction(Request $request)
    {
        /** @var array $shippingLabelIds */
        $shippingLabelIds = $request->request->get('shipping_label_bulk_action');

        if (empty($shippingLabelIds)) {
            $this->addFlash('error', $this->trans('You must select at least one item to delete.', [], 'Admin.Notifications.Error'));
            return $this->redirectToList();
        }

        $deletedCount = 0;
        foreach ($shippingLabelIds as $shippingLabelId) {
            $shippingLabel = new ShippingLabel((int) $shippingLabelId);
            if (Validate::isLoadedObject($shippingLabel)) {
                // Delete the associated file
                $filePath = _PS_MODULE_DIR_ . 'extrashippinglabels/labels/' . $shippingLabel->label_filepath;
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
                if ($shippingLabel->delete()) {
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            $this->addFlash('success', $this->trans('Bulk delete successful (%count% items deleted).', ['%count%' => $deletedCount], 'Modules.ExtraShippingLabels.Admin'));
        } else {
            $this->addFlash('error', $this->trans('No shipping labels were deleted.', [], 'Modules.ExtraShippingLabels.Admin'));
        }

        return $this->redirectToList();
    }

    private function redirectToList()
    {
        return $this->redirectToRoute('ps_extrashippinglabels_labels_index');
    }
}

