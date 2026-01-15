<?php

declare(strict_types=1);

namespace PrestaShop\Module\ExtraShippingLabels\Contract;

use Order;
use PrestaShop\Module\ExtraShippingLabels\Data\ShippingLabelData;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interface for modules that can generate shipping labels.
 */
interface ShippingLabelGeneratorInterface
{
    /**
     * Generates a shipping label for a given order.
     *
     * @param Order $order The order object
     *
     * @return ShippingLabelData An object containing the label information
     *
     * @throws \PrestaShopException If there is an error during generation
     */
    public function generateLabel(Order $order): ShippingLabelData;

    /**
     * Checks if a label can be generated for the given order.
     * This is useful for checking conditions before showing a "Generate" button.
     *
     * @param Order $order The order object
     *
     * @return bool
     */
    public function canGenerateLabel(Order $order): bool;
}
