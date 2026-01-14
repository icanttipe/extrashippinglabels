/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

$(() => {
    const grid = new window.prestashop.component.Grid('shipping_label');
    grid.addExtension(new window.prestashop.component.GridExtensions.SortingExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.FiltersResetExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.ReloadListExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.LinkRowActionExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.SubmitGridActionExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.SubmitBulkActionExtension());
    grid.addExtension(new window.prestashop.component.GridExtensions.BulkActionCheckboxExtension());
});
