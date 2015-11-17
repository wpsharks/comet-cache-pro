<?php
namespace WebSharks\ZenCache\Pro;

/*
* Automatically wipes/clears on plugin activation/deactivation.
*
* @since 15xxxx Adding auto-wipe|clear on plugin activations/deactivations.
*
* @attaches-to `activated_plugin` hook.
* @attaches-to `deactivated_plugin` hook.
*
* @param string $plugin Plugin basename.
* @param bool True if activating|deactivating network-wide.
*
* @return int Total files wiped|cleared by this routine (if any).
*/
$self->autoClearOnPluginActivationDeactivation = function ($plugin, $network_wide) use ($self) {
    return $self->{($network_wide ? 'autoWipeCache' : 'autoClearCache')}();
};
