<?php
/**
 * Create a filter to render the calendar in a post
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// If we're in a calendar post type then register a filter
if ( 'ecp1_calendar' != $post_type ) {
	add_filter( 'the_content', 'ecp1_post_as_calendar' );
}

// Renders a calendar into the post
function ecp1_post_as_calendar() {
	global $post;
	$s .= "<p>DISPLAY A CALENDAR WITH DEFAULTS</p><p><pre>".print_r($post,true);"</pre></p>";
	return $s;
}

?>
