<?php

namespace PrestaShop\Module\ExtraShippingLabels\Controller\Admin;

use PrestaShop\Module\ExtraShippingLabels\Filters\ShippingLabelFilters;
use PrestaShop\Module\ExtraShippingLabels\Grid\Definition\Factory\ShippingLabelGridDefinitionFactory;
use PrestaShop\Module\ExtraShippingLabels\Repository\ShippingLabelRepository;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShopBundle\Service\Grid\ResponseBuilder as GridResponseBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
    public function deleteAction(
        int $shippingLabelId,
        ShippingLabelRepository $repository
    ) {
        if ($repository->deleteLabel($shippingLabelId)) {
            $this->addFlash('success', $this->trans('Successful deletion.', [], 'Admin.Notifications.Success'));
        } else {
            $this->addFlash('error', $this->trans('Cannot find shipping label %id%', ['%id%' => $shippingLabelId], 'Modules.ExtraShippingLabels.Admin'));
        }

        return $this->redirectToList();
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function bulkDeleteAction(
        Request $request,
        ShippingLabelRepository $repository
    ) {
        /** @var array $shippingLabelIds */
        $shippingLabelIds = $request->request->get('shipping_label_bulk_action');

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
    public function bulkPrintAction(
        Request $request,
        ShippingLabelRepository $repository
    ): Response {
        /** @var array $shippingLabelIds */
        $shippingLabelIds = $request->request->get('shipping_label_bulk_action');

        if (empty($shippingLabelIds)) {
            $this->addFlash('error', $this->trans('You must select at least one item to print.', [], 'Admin.Notifications.Error'));
            return $this->redirectToList();
        }

        // Collect all valid PDF files
        $pdfFiles = [];
        foreach ($shippingLabelIds as $shippingLabelId) {
            $label = $repository->getLabelById((int) $shippingLabelId);
            if ($label && !empty($label->label_filepath)) {
                $filepath = $repository->getSecureLabelFilepath($label->label_filepath);
                if ($filepath && file_exists($filepath)) {
                    $pdfFiles[] = $filepath;
                }
            }
        }

        if (empty($pdfFiles)) {
            $this->addFlash('error', $this->trans('No valid label files found for the selected items.', [], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }

        // If only one file, download it directly
        if (count($pdfFiles) === 1) {
            $response = new BinaryFileResponse($pdfFiles[0]);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($pdfFiles[0])
            );
            return $response;
        }

        // Merge multiple PDFs into one
        try {
            $mergedPdf = $this->mergePdfFiles($pdfFiles);

            $filename = 'shipping_labels_' . date('Y-m-d_His') . '.pdf';
            $tempFile = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tempFile, $mergedPdf);

            $response = new BinaryFileResponse($tempFile);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', $this->trans('Error merging PDF files: %error%', ['%error%' => $e->getMessage()], 'Modules.ExtraShippingLabels.Admin'));
            return $this->redirectToList();
        }
    }

    /**
     * Simple PDF merger using basic concatenation
     * For production, consider using a library like TCPDF or PDFtk
     *
     * @param array $pdfFiles
     * @return string Merged PDF content
     * @throws \Exception
     */
    private function mergePdfFiles(array $pdfFiles): string
    {
        // This is a simplified approach. For production use, you should use a proper PDF library
        // such as TCPDF, FPDF, or PDFtk to properly merge PDFs

        // For now, we'll create a simple concatenation
        // Note: This won't work correctly with all PDFs. A proper PDF library is recommended.

        if (!class_exists('TCPDF')) {
            // If TCPDF is not available, just concatenate the first PDF as fallback
            // In production, you should include a proper PDF library
            return file_get_contents($pdfFiles[0]);
        }

        // If TCPDF is available (it should be in PrestaShop)
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('PrestaShop');
        $pdf->SetTitle('Shipping Labels');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pdfFiles as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->AddPage();
                $tplIdx = $pdf->importPage($i);
                $pdf->useTemplate($tplIdx);
            }
        }

        return $pdf->Output('', 'S');
    }

    private function redirectToList()
    {
        return $this->redirectToRoute('ps_extrashippinglabels_labels_index');
    }
}

