<?php
/**
 * Registers hooks for the installer and plugin activation mechanisms
 * to make sure we clean up after ourselves if someone doesn't like 
 * the plugin etc...
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// TODO: Uninstall function to clean up the database
 
// Create the rewrite rules needed for plugin
// Register this on the init hook too so WordPress knows about it
add_action( 'init', '_ecp1_add_rewrite_rules' );
function _ecp1_add_rewrite_rules() {
	// Event data as a JSON feed
    add_rewrite_rule( 'ecp1/events.json$', plugins_url( '/ui/get-events.php', dirname( __FILE__ ) ), 'top' );
}

// Function that activates plugin rewrite rules and flushes them to cache
function _ecp1_activate_rewrite() {
	_ecp1_add_rewrite_rules();
	flush_rewrite_rules();
}

// Function that deactivates plugin rewrite rules and flushes them to cache
function _ecp1_deactivate_rewrite() {
	// They aren't added so flushing will flush all but ours
	flush_rewrite_rules();
}


?>