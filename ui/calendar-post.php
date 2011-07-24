<?php
/**
 * Create a filter to render the calendar in a post
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Add a filter that checks if this is a calendar and then re-configures the content if so
add_filter( 'the_content', 'ecp1_post_as_calendar' );

// Renders a calendar into the post
function ecp1_post_as_calendar( $content ) {
	global $post;
	if ( is_single() && 'ecp1_calendar' == $post->post_type ) {
		// Only make the changes if this is a single post display of an ECP1 Calendar
		$content = ecp1_render_calendar( null );
	}
	return $content;
}

?>
