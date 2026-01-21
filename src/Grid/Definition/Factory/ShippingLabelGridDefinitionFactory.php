<?php

namespace PrestaShop\Module\ExtraShippingLabels\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\SubmitRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\LinkColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractFilterableGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ShippingLabelGridDefinitionFactory extends AbstractFilterableGridDefinitionFactory
{
    const GRID_ID = 'shipping_label';

    protected function getId()
    {
        return self::GRID_ID;
    }

    protected function getName()
    {
        return 'Shipping labels';
    }

    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add((new BulkActionColumn('shipping_labels_bulk'))
                ->setOptions(['bulk_field' => 'id_shipping_label']))
            ->add(
                (new DataColumn('id_shipping_label'))
                    ->setName('ID')
                    ->setOptions(['field' => 'id_shipping_label'])
            )
            ->add(
                (new LinkColumn('id_order'))
                    ->setName($this->trans('Order ID', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'id_order',
                        'route' => 'admin_orders_view',
                        'route_param_name' => 'orderId',
                        'route_param_field' => 'id_order',
                    ])
            )
            ->add(
                (new DataColumn('tracking_number'))
                    ->setName('Tracking number')
                    ->setOptions(['field' => 'tracking_number'])
            )
            ->add(
                (new DataColumn('module_name'))
                    ->setName('Module')
                    ->setOptions(['field' => 'module_name'])
            )
            ->add(
                (new DataColumn('date_add'))
                    ->setName('Date')
                    ->setOptions(['field' => 'date_add'])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName('Actions')
                    ->setOptions([
                        'actions' => (new RowActionCollection())
                            ->add(
                                (new LinkRowAction('download'))
                                    ->setName('Download')
                                    ->setIcon('cloud_download')
                                    ->setOptions([
                                        'route' => 'ps_extrashippinglabels_labels_download',
                                        'route_param_name' => 'shippingLabelId',
                                        'route_param_field' => 'id_shipping_label',
                                    ])
                            )
                            ->add(
                                (new LinkRowAction('delete'))
                                    ->setName('Delete')
                                    ->setIcon('delete')
                                    ->setOptions([
                                        'route' => 'ps_extrashippinglabels_labels_delete',
                                        'route_param_name' => 'shippingLabelId',
                                        'route_param_field' => 'id_shipping_label',
                                        'confirm_message' => 'Delete selected item?',
                                    ])
                            ),
                    ])
            );
    }

    protected function getFilters()
    {
        return (new FilterCollection())
            ->add(
                (new Filter('id_shipping_label', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => ['placeholder' => 'ID'],
                    ])
                    ->setAssociatedColumn('id_shipping_label')
            )
            ->add(
                (new Filter('id_order', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => ['placeholder' => 'Order ID'],
                    ])
                    ->setAssociatedColumn('id_order')
            )
            ->add(
                (new Filter('tracking_number', TextType::class))
                    ->setTypeOptions(['required' => false])
                    ->setAssociatedColumn('tracking_number')
            )
            ->add(
                (new Filter('module_name', TextType::class))
                    ->setTypeOptions(['required' => false])
                    ->setAssociatedColumn('module_name')
            )
            ->add(
                (new Filter('actions', SearchAndResetType::class))
                    ->setTypeOptions([
                        'reset_route' => 'admin_common_reset_search_by_filter_id',
                        'reset_route_params' => ['filterId' => self::GRID_ID],
                        'redirect_route' => 'ps_extrashippinglabels_labels_index',
                    ])
                    ->setAssociatedColumn('actions')
            );
    }

    protected function getBulkActions()
    {
        return (new BulkActionCollection())
            ->add(
                (new SubmitBulkAction('download_bulk'))
                    ->setName('Download')
                    ->setOptions([
                        'submit_route' => 'ps_extrashippinglabels_labels_download_bulk',
                    ])
            )
            ->add(
                (new SubmitBulkAction('delete_bulk'))
                    ->setName('Delete')
                    ->setOptions([
                        'submit_route' => 'ps_extrashippinglabels_labels_delete_bulk',
                        'confirm_message' => 'Delete selected items?',
                    ])
            );
    }
}
