<?php
/**
 * Defines the custom post types for the Every Calendar +1 plugin
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Add action hooks
add_action( 'init', 'ecp1_register_types' );

// Function that creates the ECP1 Custom Post Types
function ecp1_register_types() {
	// Custom labels for the calendar post type
	$ecp1_cal_labels = array(
		'name' => _x( 'Calendars', 'post type general name' ),
		'singular_name' => _x( 'Calendar', 'post type singular name' ),
		'add_new' => _x( 'New Calendar', 'ecp1_event' ),
		'add_new_item' => __( 'New Calendar' ),
		'edit_item' => __( 'Edit Calendar' ),
		'new_item' => __( 'New Calendar' ),
		'view_item' => __( 'View Calendar' ),
		'search_items' => __( 'Search Calendars' ),
		'not_found' => __( 'No calendars found for your criteria!' ),
		'not_found_in_trash' => __( 'No calendars found in trash' ),
		'parent_item_colon' => ''
	);
	
	// Custom labels for the event post type
	$ecp1_evt_labels = array(
		'name' => _x( 'Events', 'post type general name' ),
		'singular_name' => _x( 'Event', 'post type singular name' ),
		'add_new' => _x( 'New Event', 'ecp1_event' ),
		'add_new_item' => __( 'New Event' ),
		'edit_item' => __( 'Edit Event' ),
		'new_item' => __( 'New Event' ),
		'view_item' => __( 'View Event' ),
		'search_items' => __( 'Search Events' ),
		'not_found' => __( 'No events found for your criteria!' ),
		'not_found_in_trash' => __( 'No events found in trash' ),
		'parent_item_colon' => ''
	);
	
	// Custom calendar post type arguments
	$ecp1_cal_args = array(
		'labels' => $ecp1_cal_labels,
		'description' => __( 'EveryCal+1 Events' ),
		'public' => true,
		'exclude_from_search' => true, # don't show events unless the plugin says to
		'show_ui' => true,
		#TODO: 'menu_icon' => get_bloginfo( 'plugin_url' ).'/img/cal_16.png',
		'menu_position' => 30,
		'capability_type' => 'post', # capabilities match posts
		'supports' => array( 'title' ),
		'show_in_nav_menus' => false
	);
	
	// Custom event post type arguments
	$ecp1_evt_args = array(
		'labels' => $ecp1_evt_labels,
		'description' => __( 'EveryCal+1 Events' ),
		'public' => true,
		'exclude_from_search' => true, # don't show events unless the plugin says to
		'show_ui' => true,
		'show_in_menu' => 'edit.php?post_type=ecp1_calendar',
		'capability_type' => 'post', # capabilities match posts
		'supports' => array( 'title', 'thumbnail', 'excerpt' ), //'editor' ),
		'show_in_nav_menus' => false
	);
	
	// Register the custom post type
	register_post_type( 'ecp1_calendar', $ecp1_cal_args );
	register_post_type( 'ecp1_event', $ecp1_evt_args );
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
	
	return $messages;
}
add_filter( 'post_updated_messages', 'ecp1_event_updated_messages' );
function ecp1_event_updated_messages() {
	global $post, $post_ID;
	
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

?>
