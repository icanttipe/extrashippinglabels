<div class="tab-pane fade" id="shippingLabelsTabContent" role="tabpanel" aria-labelledby="shippingLabelsTab">
    <div class="card card-details">
        <div class="card-header d-none">
            <h3 class="card-header-title">
                <i class="material-icons">print</i>
                {l s='Shipping Labels' mod='extrashippinglabels'} ({$shipping_labels|count})
            </h3>
        </div>
        <div class="card-body p-0">
            {if !empty($shipping_labels)}
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{l s='Tracking' mod='extrashippinglabels'}</th>
                            <th>{l s='Date' mod='extrashippinglabels'}</th>
                            <th class="text-right">{l s='Actions' mod='extrashippinglabels'}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$shipping_labels item=label name=labels}
                        <tr>
                            <td class="align-middle">
                                <strong>{$label.tracking_number}</strong>
                                <button type="button" class="btn btn-link btn-sm p-0 ml-1 js-copy-tracking"
                                        data-tracking="{$label.tracking_number}"
                                        data-toggle="pstooltip"
                                        title="{l s='Copy' mod='extrashippinglabels'}">
                                    <i class="material-icons" style="font-size: 14px;">content_copy</i>
                                </button>
                            </td>
                            <td style="white-space: nowrap;">
                                {$label.date_formatted}
                            </td>
                            <td class="align-middle text-right" style="white-space: nowrap;">
                                {if isset($label.tracking_url) && $label.tracking_url}
                                    <a href="{$label.tracking_url}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                        <i class="material-icons" style="font-size: 16px;">travel_explore</i>
                                    </a>
                                {/if}
                                {if isset($label.download_url) && $label.download_url}
                                    <a href="{$label.download_url}" class="btn btn-sm" target="_blank">
                                        <i class="material-icons" style="font-size: 16px;">download</i>
                                    </a>
                                {/if}
                                {if isset($label.delete_url)}
                                    <form action="{$label.delete_url}" method="post" class="d-inline"
                                          onsubmit="return confirm('{l s='Are you sure you want to delete this label?' mod='extrashippinglabels' js=1}')">
                                        <button type="submit" class="btn btn-sm">
                                            <i class="material-icons" style="font-size: 16px;">delete</i>
                                        </button>
                                    </form>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            {else}
                <div class="text-center py-3">
                    <p class="text-muted mb-0">{l s='No shipping labels.' mod='extrashippinglabels'}</p>
                </div>
            {/if}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-copy-tracking').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tracking = this.dataset.tracking;
            navigator.clipboard.writeText(tracking).then(function() {
                var icon = btn.querySelector('i');
                icon.textContent = 'check';
                setTimeout(function() {
                    icon.textContent = 'content_copy';
                }, 1500);
            });
        });
    });
});
</script>
