<?php
/**
 * Create a filter to render the event in a post
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Add a filter that checks if this is an event and then re-configures the content if so
add_filter( 'the_content', 'ecp1_post_as_event' );

// Renders an event into the post
function ecp1_post_as_event( $content ) {
	global $post;
	if ( is_single() && 'ecp1_event' == $post->post_type ) {
		// Only make the changes if this is a single post display of an ECP1 Event
		$content = "<p>DISPLAY AN EVENT WITH DEFAULTS</p><p><pre>".print_r($post,true);"</pre></p>";
	}
	return $content;
}

?>
