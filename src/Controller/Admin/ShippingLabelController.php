<?php

namespace PrestaShop\Module\ExtraShippingLabels\Controller\Admin;

use Exception;
use Hook;
use PrestaShop\Module\ExtraShippingLabels\Filters\ShippingLabelFilters;
use PrestaShop\Module\ExtraShippingLabels\Grid\Definition\Factory\ShippingLabelGridDefinitionFactory;
use PrestaShop\Module\ExtraShippingLabels\Repository\ShippingLabelRepository;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShopBundle\Service\Grid\ResponseBuilder as GridResponseBuilder;
use setasign\Fpdi\Fpdi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function deleteAction(int $shippingLabelId, Request $request, ShippingLabelRepository $repository) {
        if ($repository->deleteLabel($shippingLabelId)) {
            $this->addFlash('success', $this->trans('Successful deletion.', [], 'Admin.Notifications.Success'));
        } else {
            $this->addFlash('error', $this->trans('Cannot find shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
        }

        $redirectUrl = $request->query->get('redirect');
        if ($redirectUrl) {
            return $this->redirect($redirectUrl);
        }

        return $this->redirectToList();
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function deleteBulkAction(Request $request, ShippingLabelRepository $repository): RedirectResponse
    {
        $shippingLabelIds = $request->request->all('shipping_label_shipping_labels_bulk');

        if (empty($shippingLabelIds)) {
            $this->addFlash('error', $this->trans('You must select at least one item to delete.', [], 'Admin.Notifications.Error'));
            return $this->redirectToList();
        }

        $deletedCount = $repository->bulkDeleteLabels($shippingLabelIds);

        if ($deletedCount > 0) {
            $this->addFlash('success', $this->trans('Bulk delete successful (%count% items deleted).', ['%count%' => $deletedCount], 'Modules.ExtraShippingLabels.Admin'));
        } else {
            $this->addFlash('error', $this->trans('No shipping labels were deleted.', [], 'Modules.ExtraShippingLabels.Admin'));
        }

        return $this->redirectToList();
    }

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function downloadAction(int $shippingLabelId, ShippingLabelRepository $repository): Response
    {
        $label = $repository->getLabelById($shippingLabelId);

        if (!$label) {
            $this->addFlash('error', $this->trans('Cannot find shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        if (empty($label->label_filepath)) {
            $this->addFlash('error', $this->trans('No file associated with this shipping label', [], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        $filepath = $repository->getSecureLabelFilepath($label->label_filepath);

        if ($filepath === null || !file_exists($filepath)) {
            $this->addFlash('error', $this->trans('Label file not found', [], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($label->label_filepath)
        );

        return $response;
    }

    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function downloadBulkAction(Request $request, ShippingLabelRepository $repository): Response
    {
        $shippingLabelIds = $request->request->all('shipping_label_shipping_labels_bulk');
        $orderIds = $request->request->all('order_orders_bulk');

        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $shippingLabels = $repository->getLabelsForOrder($orderId);
                array_push($shippingLabelIds, ...array_map(fn(array $d) => $d['id_shipping_label'], $shippingLabels));
            }
        }

        if (empty($shippingLabelIds)) {
            $this->addFlash('error', $this->trans('You must select at least one item to download.', [], 'Admin.Notifications.Error'));
            return $this->redirectToList();
        }

        $pdfFiles = [];
        $failedLabels = [];
        foreach ($shippingLabelIds as $shippingLabelId) {
            $label = $repository->getLabelById((int) $shippingLabelId);
            if ($label && !empty($label->label_filepath)) {
                $filepath = $repository->getSecureLabelFilepath($label->label_filepath);
                if ($filepath && file_exists($filepath)) {
                    $pdfFiles[] = $filepath;
                } else {
                    $failedLabels[] = $shippingLabelId;
                }
            } else {
                $failedLabels[] = $shippingLabelId;
            }
        }

        if (empty($pdfFiles)) {
            $this->addFlash('error', $this->trans('No valid label files found for the selected items.', [], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        if (!empty($failedLabels)) {
            $this->addFlash('warning', $this->trans('Could not find labels for IDs: %ids%', ['%ids%' => implode(', ', $failedLabels)], 'Modules.ExtraShippingLabels.Admin'));
        }

        try {
            $filename = 'shipping_labels_merged_' . date('Y-m-d') . '.pdf';
            $response = new Response($this->mergePdfFiles($pdfFiles));
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            return $response;
        } catch (Exception $e) {
            $this->addFlash('error', $this->trans('An error occurred while merging PDFs: %error%', ['%error%' => $e->getMessage()], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }
    }

    public function generateBulkAction(Request $request): RedirectResponse
    {
        $orderIds = $request->request->all('order_orders_bulk');

        if (!empty($orderIds)) {
            $generatedCount = 0;
            foreach ($orderIds as $orderId) {
                try {
                    Hook::exec('actionOrderGenerateShippingLabel', ['id_order' => (int)$orderId]);
                    $generatedCount++;
                } catch (Exception $e) {
                    $this->addFlash('error', $this->trans(
                        'Error generating label for order #%id%: %error%',
                        ['%id%' => $orderId, '%error%' => $e->getMessage()],
                        'Modules.Extrashippinglabels.Admin'
                    ));
                }
            }

            if ($generatedCount > 0) {
                $this->addFlash('success', $this->trans(
                    'Labels generated for %count% order(s).',
                    ['%count%' => $generatedCount],
                    'Modules.Extrashippinglabels.Admin'
                ));
            }
        } else {
            $this->addFlash('error', $this->trans('You must select at least one order.', [], 'Modules.Extrashippinglabels.Admin'));
        }

        return $this->redirectToRoute('admin_orders_index');
    }

    /**
     * @return string Merged PDF content
     * @throws Exception
     */
    private function mergePdfFiles(array $pdfFiles): string
    {
        $merger = new Fpdi();
        foreach ($pdfFiles as $file) {
            $pageCount = $merger->setSourceFile($file);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $merger->importPage($i);
                $size = $merger->getTemplateSize($tpl);
                $merger->AddPage($size['orientation'], $size);
                $merger->useTemplate($tpl);
            }
        }
        return $merger->Output('S');
    }

    private function redirectToList()
    {
        return $this->redirectToRoute('ps_extrashippinglabels_labels_index');
    }
}
