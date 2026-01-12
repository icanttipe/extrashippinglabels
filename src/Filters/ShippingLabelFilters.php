<?php

namespace PrestaShop\Module\ExtraShippingLabels\Filters;

use PrestaShop\PrestaShop\Core\Search\Filters;
use PrestaShop\Module\ExtraShippingLabels\Grid\Definition\Factory\ShippingLabelGridDefinitionFactory;

class ShippingLabelFilters extends Filters
{
    /** @var string */
    protected $filterId = ShippingLabelGridDefinitionFactory::GRID_ID;

    public static function getDefaults()
    {
        return [
            'limit' => 20,
            'offset' => 0,
            'orderBy' => 'id_shipping_label',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}