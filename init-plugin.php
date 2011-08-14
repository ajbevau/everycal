<?php
/**
 * Loads the custom post types, functions and any misc init
 * hooks that the custom templates require. After this file
 * is loaded you can work with the custom types.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Turn on debugging if requested
if ( ECP1_DEBUG ) {
	ini_set( 'display_errors', 1 );
	error_reporting( E_ALL );
}

// Define the Custom Post Type
require_once( ECP1_DIR . '/includes/custom-post-type.php' );

// Load the helper functions
require_once( ECP1_DIR . '/functions.php' );

// Add a hook on init to register the custom rewrite rules
// so they don't get dropped with other permalink updates
// NOTE: this is forward declared (see install-activate.php)
add_action( 'init', 'ecp1_add_rewrite_rules' );

// Add a hook on init to register ECP1_TEMPLATE_TAG and the
// other variables that the custom template scripts need.
add_action( 'init', 'ecp1_register_query_tags' );
function ecp1_register_query_tags() {
	// Tags that should be added to $wp_query->query_vars
	// note that UNLESS you're rewrite rule includes these
	// tags then the regex is NOT validated.
	$tags = array(
		ECP1_TEMPLATE_TAG => '([a-z\-]+)', # which template to use
		ECP1_TEMPLATE_TEST_ARG => '([^&]+)', # for template devs to enable debuging
		'ecp1_start'      => '([0-9])+',   # unix timestamp for event start lookup
		'ecp1_end'        => '([0-9]+)',   #  and the end lookup (neither validated)
		'ecp1_cal'        => '([a-zA-Z0-9_\-]+)',  # the ecp1_calendar slug to lookup
	);

	// Loop over the tags and add them to the query vars
	foreach( $tags as $name=>$regex )
		add_rewrite_tag( "%$name%", $regex );
}

?>
