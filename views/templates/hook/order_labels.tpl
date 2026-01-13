<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-title">
            <i class="material-icons">local_shipping</i>
            {l s='Shipping Labels' mod='extrashippinglabels'}
        </h3>
    </div>
    <div class="card-body">
        {if !empty($shipping_labels)}
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Tracking Number' mod='extrashippinglabels'}</th>
                    <th>{l s='Module' mod='extrashippinglabels'}</th>
                    <th>{l s='Date' mod='extrashippinglabels'}</th>
                    <th class="text-right">{l s='Actions' mod='extrashippinglabels'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$shipping_labels item=label}
                    <tr>
                        <td>{$label.tracking_number|default:'N/A'}</td>
                        <td>{$label.module_name}</td>
                        <td>{$label.date_add|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                        <td class="text-right">
                            {if isset($label.download_url) && $label.download_url}
                                <a href="{$label.download_url}" class="btn btn-link mr-2" target="_blank"
                                   data-toggle="pstooltip" data-placement="top"
                                   title="{l s='Download' mod='extrashippinglabels'}">
                                    <i class="material-icons">cloud_download</i>
                                </a>
                            {/if}

                            {capture assign='extraActions'}
                                {hook h='displayShippingLabelActions' id_shipping_label=$label.id_shipping_label}
                            {/capture}

                            {if !empty($extraActions) || isset($label.delete_url)}
                                <div class="btn-group">
                                    <a class="btn btn-link" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{l s='More actions' mod='extrashippinglabels'}">
                                        <i class="material-icons">more_vert</i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        {$extraActions nofilter}
                                        {if isset($label.delete_url)}
                                            <form action="{$label.delete_url}" method="post">
                                                <button type="submit" class="dropdown-item js-confirm-submit"
                                                        data-confirm-message="{l s='Delete selected item?' d='Admin.Notifications.Warning'}">
                                                    {l s='Delete' d='Admin.Actions'}
                                                </button>
                                            </form>
                                        {/if}
                                    </div>
                                </div>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {else}
            <p>{l s='No shipping labels found for this order.' mod='extrashippinglabels'}</p>
        {/if}
    </div>
</div>
