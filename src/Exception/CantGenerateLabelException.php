<?php

declare(strict_types=1);

namespace PrestaShop\Module\ExtraShippingLabels\Exception;

use PrestaShopException;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Thrown when a shipping label cannot be generated.
 */
class CantGenerateLabelException extends PrestaShopException
{
}
