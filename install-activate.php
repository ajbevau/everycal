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
function _ecp1_add_rewrite_rules() {
	// Event data as a JSON feed
	$rewrite_to = str_replace( site_url() . '/', '', plugins_url( '/ui/get-events.php', __FILE__ ) );
	add_rewrite_rule( 'ecp1/events.json$', $rewrite_to, 'top' );
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
