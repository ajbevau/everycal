<?php
/**
 * Registers hooks for the installer and plugin activation mechanisms
 * to make sure we clean up after ourselves if someone doesn't like 
 * the plugin etc...
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// There are several data URLs this plugin exposes:
// 1 - Event data as a JSON feed
// TODO: extras
//
// This function registers a hook on activation to create rewrite
// rules for these data URLs and to flush them to .htaccess
register_activation_hook( __FILE__, 'ecp1_plugin_activation' );
function ecp1_plugin_activation() {
    _ecp1_add_rewrite_rules();
    flush_rewrite_rules();
}
 
// Register a hook to flush rewrite rules on deactivation
register_deactivation_hook( __FILE__, 'ecp1_plugin_deactivation' );
function wp_ozh_plu_deactivate() {
    flush_rewrite_rules();
}

// TODO: Uninstall hook to clean up the database
 
// Create the rewrite rules needed for plugin
add_action( 'init', '_ecp1_add_rewrite_rules' );
function _ecp1_add_rewrite_rules() {
	// Event data as a JSON feed
    add_rewrite_rule( 'ecp1/events.json$', plugins_url( '/ui/get-events.php', dirname( __FILE__ ) ), 'top' );
}

?>