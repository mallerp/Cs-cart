{if ($runtime.controller == "block_manager" && $runtime.mode == "manage")}
    {include file="addons/help_tutorial/components/video.tpl" items=["Tv7AZhmLwkw", "RseUfuFdctg"] open=false}
{elseif ($runtime.controller == "themes" && $runtime.mode == "manage")}
    {include file="addons/help_tutorial/components/video.tpl" items=["BVOLfcROTyg"] open=false}
{elseif ($runtime.controller == "store_import" && $runtime.mode == "index")}
    {include file="addons/help_tutorial/components/video.tpl" items=["cCJOoAZnCqk"] open=false}
{elseif ("ULTIMATE"|fn_allowed_for && $runtime.controller == "companies")}
    {include file="addons/help_tutorial/components/video.tpl" items=["eUam0Puui3M"] open="ULTIMATE:FREE"|fn_allowed_for && $runtime.mode == 'manage'}
{elseif ($runtime.controller == "index" && $runtime.mode == "index")}
    {include file="addons/help_tutorial/components/video.tpl" items=["5STIqzsPU9c"] open=false}
{elseif ($runtime.controller == "seo_rules" && $runtime.mode == "manage")}
    {include file="addons/help_tutorial/components/video.tpl" items=["JUFXyew6lig"] open=false}
{/if}