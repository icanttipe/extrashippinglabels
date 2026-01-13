<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace PrestaShop\Module\ExtraShippingLabels\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ShippingLabel;
use Hook;

/**
 * Repository for managing shipping labels
 * This class centralizes all business logic related to shipping labels
 */
class ShippingLabelRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var string
     */
    private $labelsDirectory;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(Connection $connection, string $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        // Store labels in the var directory, outside of the module
        // This ensures labels persist even if the module is reinstalled or updated
        $this->labelsDirectory = _PS_ROOT_DIR_ . '/var/shipping_labels/';

        // Create directory if it doesn't exist
        if (!is_dir($this->labelsDirectory)) {
            @mkdir($this->labelsDirectory, 0755, true);
        }
    }

    /**
     * Get all labels for a specific order
     *
     * @param int $orderId
     * @return array
     * @throws Exception
     */
    public function getLabelsForOrder(int $orderId): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->dbPrefix . 'shipping_label')
            ->where('id_order = :id_order')
            ->setParameter('id_order', $orderId);

        $results = $qb->executeQuery()->fetchAllAssociative();

        if (empty($results)) {
            return [];
        }

        // Allow other modules to potentially enrich the label data (e.g., add a direct download link)
        foreach ($results as &$label) {
            Hook::exec('actionGetShippingLabelData', [
                'label' => &$label,
            ]);
        }

        return $results;
    }

    /**
     * Create a new shipping label
     * This method is designed to be called by other modules (carriers, shipping modules, etc.)
     *
     * @param int $orderId
     * @param string $moduleName
     * @param string|null $trackingNumber
     * @param string|null $labelFilepath
     * @return int The ID of the created label, or 0 on failure
     * @throws \Exception if the label file is not a valid PDF
     */
    public function createLabel(
        int $orderId,
        string $moduleName,
        ?string $trackingNumber = null,
        ?string $labelFilepath = null
    ): int {
        // Validate PDF file if provided
        if ($labelFilepath !== null) {
            $fullPath = $this->getSecureLabelFilepath($labelFilepath);
            if ($fullPath && file_exists($fullPath) && !$this->isValidPdfFile($fullPath)) {
                throw new \Exception('The label file must be a valid PDF');
            }
        }

        $label = new ShippingLabel();
        $label->id_order = $orderId;
        $label->module_name = $moduleName;
        $label->tracking_number = $trackingNumber;
        $label->label_filepath = $labelFilepath;

        if ($label->add()) {
            return (int) $label->id;
        }

        return 0;
    }

    /**
     * Update an existing shipping label
     *
     * @param int $labelId
     * @param array $data Associative array with fields to update
     * @return bool
     */
    public function updateLabel(int $labelId, array $data): bool
    {
        $label = new ShippingLabel($labelId);

        if (!$label->id) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (property_exists($label, $key) && $key !== 'id_shipping_label') {
                $label->$key = $value;
            }
        }

        return $label->update();
    }

    /**
     * Delete a shipping label and its associated file
     *
     * @param int $labelId
     * @return bool
     */
    public function deleteLabel(int $labelId): bool
    {
        $label = new ShippingLabel($labelId);

        if (!$label->id) {
            return false;
        }

        // Delete the associated file if it exists
        if (!empty($label->label_filepath)) {
            $this->deleteLabelFile($label->label_filepath);
        }

        return $label->delete();
    }

    /**
     * Delete multiple shipping labels
     *
     * @param array $labelIds
     * @return int Number of successfully deleted labels
     */
    public function bulkDeleteLabels(array $labelIds): int
    {
        $deletedCount = 0;

        foreach ($labelIds as $labelId) {
            if ($this->deleteLabel((int) $labelId)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get a single label by ID
     *
     * @param int $labelId
     * @return ShippingLabel|null
     */
    public function getLabelById(int $labelId): ?ShippingLabel
    {
        $label = new ShippingLabel($labelId);

        if (!$label->id) {
            return null;
        }

        return $label;
    }

    /**
     * Check if a label file exists in the filesystem
     *
     * @param string $filepath
     * @return bool
     */
    public function labelFileExists(string $filepath): bool
    {
        // Secure the filepath to prevent path traversal
        $secureFilepath = $this->getSecureLabelFilepath($filepath);

        if ($secureFilepath === null) {
            return false;
        }

        return file_exists($secureFilepath) && is_file($secureFilepath);
    }

    /**
     * Get the full secure path to a label file
     * This method prevents path traversal attacks by ensuring the file is within the labels directory
     *
     * @param string $filepath Relative filepath from database
     * @return string|null Full secure path or null if invalid
     */
    public function getSecureLabelFilepath(string $filepath): ?string
    {
        if (empty($filepath)) {
            return null;
        }

        // Use basename to prevent path traversal (removes any ../ or /)
        $secureFilename = basename($filepath);

        // Additional security check: ensure the filename doesn't start with a dot
        if (str_starts_with($secureFilename, '.')) {
            return null;
        }

        $fullPath = $this->labelsDirectory . $secureFilename;

        // Verify the resolved path is still within the labels directory
        $realLabelsDir = realpath($this->labelsDirectory);
        $realFilePath = realpath($fullPath);

        // If realpath returns false, the file doesn't exist yet (which is OK for new files)
        // But we still need to check the constructed path is safe
        if ($realFilePath !== false && $realLabelsDir !== false) {
            if (str_starts_with($realFilePath, $realLabelsDir) === false) {
                return null;
            }
        }

        return $fullPath;
    }

    /**
     * Delete a label file from the filesystem
     * This method is protected against path traversal attacks
     *
     * @param string $filepath
     * @return bool
     */
    public function deleteLabelFile(string $filepath): bool
    {
        $secureFilepath = $this->getSecureLabelFilepath($filepath);

        if ($secureFilepath === null) {
            return false;
        }

        if (file_exists($secureFilepath) && is_file($secureFilepath)) {
            return @unlink($secureFilepath);
        }

        return false;
    }

    /**
     * Get the labels directory path
     *
     * @return string
     */
    public function getLabelsDirectory(): string
    {
        return $this->labelsDirectory;
    }

    /**
     * Search labels by tracking number
     *
     * @param string $trackingNumber
     * @return array
     * @throws Exception
     */
    public function findByTrackingNumber(string $trackingNumber): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->dbPrefix . 'shipping_label')
            ->where('tracking_number = :tracking_number')
            ->setParameter('tracking_number', $trackingNumber);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get all labels created by a specific module
     *
     * @param string $moduleName
     * @return array
     * @throws Exception
     */
    public function findByModule(string $moduleName): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->dbPrefix . 'shipping_label')
            ->where('module_name = :module_name')
            ->setParameter('module_name', $moduleName);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Validate if a file is a valid PDF
     *
     * @param string $filepath Full path to the file
     * @return bool
     */
    private function isValidPdfFile(string $filepath): bool
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return false;
        }

        // Check file size (max 50MB for a label)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if (filesize($filepath) > $maxSize) {
            return false;
        }

        // Check MIME type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);

            if ($mimeType !== 'application/pdf') {
                return false;
            }
        }

        // Check PDF signature (first 4 bytes should be %PDF)
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return $header === '%PDF';
    }
}
