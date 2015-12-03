<div id="{$modal.id}" class="{$modal.class}" {if $modal.hide}style="display: none;"{/if}>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>{$modal.header}</h3>
    </div>
    
    {if $modal.formaction}
    <form method="post" action="{$modal.formaction}">
    {/if}
    
    <div class="modal-body">
        {$modal.body}
    </div>
    <div class="modal-footer">
        {foreach $modal.actions as $action}
        {if $action.element == "button"}
        <button class="{$action.class}" {foreach $action.attrs as $attr => $value} {$attr}="{$value}"{/foreach}>{$action.label}</button>
        {else}
        <a {foreach $action.attrs as $attr => $value} {$attr}="{$value}"{/foreach}>{$action.label}</a>
        {/if}
        {/foreach}
    </div>
    
    {if $modal.formaction}
    </form>
    {/if}
</div>
