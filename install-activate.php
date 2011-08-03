<?php
/**
 * Registers hooks for the installer and plugin activation mechanisms
 * to make sure we clean up after ourselves if someone doesn't like 
 * the plugin etc...
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Create the rewrite rules needed for plugin
function ecp1_add_rewrite_rules() {
	// Rewrite rules as target => destination
	$rewrites = array(
		'ecp1/([a-zA-Z0-9_\-]+)/events.json$' => 'index.php?ecp1tpl=event-json&ecp1_cal=$matches[1]', // Events as JSON
	);

	// Loop over the rules and add them to the top then
	// need to go on the top otherwise /pagename/ is used
	foreach( $rewrites as $from=>$to )
		add_rewrite_rule( $from, $to, 'top' );
}

// Function that activates plugin rewrite rules and flushes them to cache
function _ecp1_activate_rewrite() {
	ecp1_register_types();     # register the custom cal/event types
	ecp1_add_rewrite_rules();  # setup custom template urls (e.g. json events)
	flush_rewrite_rules();     # flush the rules to the database and .htaccess
}

// Function that deactivates plugin rewrite rules and flushes them to cache
function _ecp1_deactivate_rewrite() {
	// They aren't added so flushing will flush all but ours
	flush_rewrite_rules();
}

?>
