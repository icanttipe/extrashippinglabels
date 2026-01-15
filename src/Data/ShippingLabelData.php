<?php

declare(strict_types=1);

namespace PrestaShop\Module\ExtraShippingLabels\Data;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * A simple data object to hold shipping label information.
 */
class ShippingLabelData
{
    public readonly string $trackingNumber;
    public readonly string $filePath;
    public readonly ?string $moduleName;

    public function __construct(string $moduleName, string $filePath, string $trackingNumber)
    {
        $this->moduleName = $moduleName;
        $this->filePath = $filePath;
        $this->trackingNumber = $trackingNumber;
    }
}
