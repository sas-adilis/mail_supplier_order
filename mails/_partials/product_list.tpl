<table cellpadding="0" cellspacing="0" width="100%" border="0">
    {foreach $products as $product}
    <tr>
        <td valign="top">
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td align="right">
                        {if $product['product_quantity']|intval > 1}<strong>{/if}{$product['product_quantity']}x{if $product['product_quantity']|intval > 1}</strong>{/if}
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>
        <td valign="top">
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td>
                        <strong>{$product['product_name']}</strong>
                        {if isset($product['product_reference']) && $product['product_reference'] != ''}
                            <br/>Référence : {$product['product_supplier_reference']}
                        {/if}
                        {hook h='displayProductMailSupplierOrder' product=$product}
                </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
{/foreach}
</table>