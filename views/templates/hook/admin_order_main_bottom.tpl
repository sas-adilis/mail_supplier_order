<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-header-title">{l s='Mails for suppliers' mod='mail_supplier_order'}</h3>
    </div>
    <div class="card-body">
        {foreach from=$suppliers item=supplier name=supplier}
            <div class="row m-2 {if !$smarty.foreach.supplier.last}pb-2 border-bottom{/if} align-items-center">
                <strong class="col-auto">{$supplier.name}</strong>
                <div class="col">
                    {$supplier['products']|count} {l s='product(s) of this supplier' mod='mail_supplier_order'}
                </div>
                <div class="col-auto">
                    <a href="{$supplier.mail}" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#mailSupplierPopup{$supplier.id_supplier}">
                        <i class="fa fa-envelope"></i> {l s='Send mail' mod='mail_supplier_order'}
                    </a>
                </div>
                <div class="modal fade" id="mailSupplierPopup{$supplier.id_supplier}" tabindex="-1" role="dialog" aria-labelledby="mailSupplierPopupLabel{$supplier.id_supplier}" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <form action="{$module_config_url}" method="post">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="mailSupplierPopupLabel{$supplier.id_supplier}">{l s='Mail Options' mod='mail_supplier_order'}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">

                                        <input type="hidden" name="mso_id_order" value="{$id_order|intval}">
                                        <input type="hidden" name="mso_id_supplier" value="{$supplier.id_supplier|intval}">

                                        <div class="form-horizontal">
                                            <div class="form-group row">
                                                <label class="form-control-label" for="mso_subject_{$supplier.id_supplier|intval}">{l s='Mail subject' mod='mail_supplier_order'}</label>
                                                <div class="col-sm">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="mso_subject" id="mso_subject_{$supplier.id_supplier|intval}" value="{$supplier.mail_subject}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="form-control-label" for="mso_email_{$supplier.id_supplier|intval}">{l s='Supplier mail' mod='mail_supplier_order'}</label>
                                                <div class="col-sm">
                                                    <div class="input-group">
                                                        <input id="mso_email_{$supplier.id_supplier|intval}" type="email" class="form-control" name="mso_email" value="{$supplier.email}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="form-group">
                                            <label class="mso_content_{$supplier.id_supplier|intval}">{l s='Mail content' mod='mail_supplier_order'}</label>
                                            <textarea id="mso_content_{$supplier.id_supplier|intval}" name="mso_content" class="autoload_rte form-control">{$supplier.mail_content}</textarea>
                                        </div>
                                </div>
                                <div class="modal-footer justify-content-between">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-secondary pull-left" data-dismiss="modal">{l s='Close' mod='mail_supplier_order'}</button>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{$module_config_url}&action=downloadDeliverySlip&mso_id_order={$id_order|intval}&mso_id_supplier={$supplier.id_supplier|intval}" class="btn btn-outline-secondary">{l s='Download delivery slip' mod='mail_supplier_order'}</a>
                                        <button type="submit" name="submitSendMail" class="btn btn-primary">{l s='Send email' mod='mail_supplier_order'}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        {/foreach}
    </div>
</div>
<script type="text/javascript">
    const iso = '{$iso|addslashes}';
    const pathCSS = '{$smarty.const._THEME_CSS_DIR_|addslashes}';
    const ad = '{$ad|addslashes}';

    $(document).ready(function(){
        tinySetup({
            editor_selector :"autoload_rte",
            resize      : true,   // Good, it is set right :)
        });
    });
</script>
