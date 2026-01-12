<?php
class ShippingLabel extends ObjectModel
{
    /** @var int Order ID */
    public $id_order;

    /** @var string|null Tracking number */
    public $tracking_number;

    /** @var string Module name */
    public $module_name;

    /** @var string|null Label filename */
    public $label_filepath;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'shipping_label',
        'primary' => 'id_shipping_label',
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'tracking_number' => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'],
            'module_name' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true, 'size' => 64],
            'label_filepath' => ['type' => self::TYPE_STRING, 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];
}
