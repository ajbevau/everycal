<?php
/**
 * Create a filter to render the event in a post
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// If we're in a event post type then register a filter
if ( 'ecp1_event' != $post_type ) {
	add_filter( 'the_content', 'ecp1_post_as_event' );
}

// Renders a event into the post
function ecp1_post_as_event() {
	global $post;
	$s .= "<p>DISPLAY AN EVENT WITH DEFAULTS</p><p><pre>".print_r($post,true);"</pre></p>";
	return $s;
}

?>
