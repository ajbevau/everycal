<?php
/**
 * Adds the Admin panel extra messages and post fields
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Add the CSS
add_action( 'admin_print_styles', 'ecp1_print_admin_styles', 100 );
function ecp1_print_admin_styles() {
	global $post_type;
	if ( 'ecp1_calendar' != $post_type && 'ecp1_event' != $post_type )
		return; // Only render if looking at our custom post types
	$f2 = plugins_url( '/css/ecp1-admin.css', dirname( __FILE__ ) );
	//wp_enqueue_style( 'ecp1_admin_style', $f2 );
	printf( '<link rel="stylesheet" href="%s" type="text/css" media="all" />%s', $f2, "\n" );
}

// Make sure jQuery and jQuery UI are enqueued
add_action( 'admin_print_scripts', 'ecp1_print_admin_scripts', 100 );
function ecp1_print_admin_scripts() {
	global $post_type;
	if ( 'ecp1_calendar' != $post_type && 'ecp1_event' != $post_type )
		return; // Only render if looking at our custom post types

	// Use the WordPress queue so don't double load things
	// Remember this will load jQuery in no-conflict mode
	// 	jQuery(document).ready(function($) {
	// 		$() will work as an alias for jQuery() inside of this function
	// 	});
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui' );
}

// Add filters to make sure calendar and events display instead of post
add_filter( 'post_updated_messages', 'ecp1_calendar_updated_messages' );
function ecp1_calendar_updated_messages() {
	global $post, $post_ID;
	
	// Custom update messages for the calendar
	$messages['ecp1_calendar'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Calendar updated. <a href="%s">View calendar...</a>' ), esc_url( get_permalink( $post_ID ) ) ),
		2 => __( 'Custom field updated (2).' ),
		3 => __( 'Custom field deleted (3).' ),
		4 => __( 'Calendar updated.' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Calendar restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Calendar published. <a href="%s">View calendar...</a>' ), esc_url( get_permalink( $post_ID ) ) ),
		7 => __( 'Calendar saved.' ),
		8 => sprintf( __( 'Calendar submitted. <a target="_blank" href="%s">Preview calendar...</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		9 => sprintf( __( 'Calendar scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview calendar...</a>' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
		10 => sprintf( __( 'Calendar draft updated. <a target="_blank" href="%s">Preview calendar...</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
	);
	
	// Custom update messages for the events
	$messages['ecp1_event'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Event updated. <a href="%s">View event...</a>' ), esc_url( get_permalink( $post_ID ) ) ),
		2 => __( 'Custom field updated (2).' ),
		3 => __( 'Custom field deleted (3).' ),
		4 => __( 'Event updated.' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Event published. <a href="%s">View event...</a>' ), esc_url( get_permalink( $post_ID ) ) ),
		7 => __( 'Event saved.' ),
		8 => sprintf( __( 'Event submitted. <a target="_blank" href="%s">Preview event...</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		9 => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event...</a>' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
		10 => sprintf( __( 'Event draft updated. <a target="_blank" href="%s">Preview event...</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
	);
	
	return $messages;
}

// Display contextual help for calendars and events
add_action( 'contextual_help', 'ecp1_add_help_text', 15, 3 );
function ecp1_add_help_text($contextual_help, $screen_id, $screen) {
	$contextual_help .= var_dump($screen); // DEBUG code to determine $screen->id
	
	// If looking at a calendar
	if ( 'ecp1_calendar?' == $screen->id ) {
		$contextual_help = '<p>TODO: some help text for calendars</p>';
	} elseif ( 'ecp1_event?' == $screen->id ) {
		$contextual_help = '<p>TODO: some help text for events</p>';
	}
	
	return $contextual_help;
}

// Now that everything is defined add extra fields to the calendar and event types
include_once( ECP1_DIR . '/includes/data/calendar-fields.php' );
include_once( ECP1_DIR . '/includes/data/event-fields.php' );

?>
