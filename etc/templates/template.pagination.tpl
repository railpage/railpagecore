<div class="pagination_wrapper">
    
    {foreach $pagination as $key => $data}
    {if $data.class == "current"}
        <span class="pagination_block pagination_current">{$data.text}</span>
    {elseif $data.class == "other"}
        <span class="pagination_block pagination_other">{$data.text}</span>
    {elseif $data.class == "navigation"}
        <a href="{$data.href}" class="pagination_block pagination_navigation">{$data.text}</a>
    {else}
        <a href="{$data.href}" class="pagination_block pagination_other">{$data.text}</a>
    {/if}
    {/foreach}
    
</div>
