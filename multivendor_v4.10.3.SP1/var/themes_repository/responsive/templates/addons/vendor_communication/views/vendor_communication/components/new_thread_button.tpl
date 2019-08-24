{if "MULTIVENDOR"|fn_allowed_for}
    {if $auth.user_id}
        <a title="{__("vendor_communication.contact_vendor")}" class="ty-vendor-communication__post-write cm-dialog-opener cm-dialog-auto-size" data-ca-target-id="new_thread_dialog_{$object_id}" rel="nofollow">
            <i class="ty-icon-chat"></i>
            {__("vendor_communication.contact_vendor")}
        </a>
    {else}
        {assign var="return_current_url" value=$config.current_url|escape:url}

        <a title="{__("vendor_communication.contact_vendor")}" href="{"auth.login_form?return_url=`$return_current_url`"|fn_url}" {if $settings.Security.secure_storefront != "partial"} data-ca-target-id="new_thread_login_form" class="cm-dialog-opener cm-dialog-auto-size ty-vendor-communication__post-write"{else}class="ty-vendor-communication__post-write"{/if} rel="nofollow">
            <i class="ty-icon-chat"></i>
            {__("vendor_communication.contact_vendor")}
        </a>

        {if $show_form && $settings.Security.secure_storefront != "partial"}
            {include file="addons/vendor_communication/views/vendor_communication/components/login_form.tpl"}
        {/if}
    {/if}
{/if}