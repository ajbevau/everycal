<?php
/**
 * Defines the custom post types for the Every Calendar +1 plugin
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Define the capability types for the two custom post types
// if you DO NOT want to use a role manager then set these
// to a existing WordPress type: I recommend post for calendar
// and post for events.
// 
// If you want more fine grained access control then change 
// these to something else and setup the capability/roles.
define( 'ECP1_CALENDAR_CAP', 'post' );
define( 'ECP1_EVENT_CAP', 'post' );

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
		'parent_item_colon' => '',
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
		'parent_item_colon' => '',
	);
	
	// Custom calendar post type arguments
	$ecp1_cal_args = array(
		'labels' => $ecp1_cal_labels,
		'description' => __( 'EveryCal+1 Events' ),
		'public' => true,
		'exclude_from_search' => true, # don't show events unless the plugin says to
		'show_ui' => true,
		'show_in_menu' => 'edit.php?post_type=ecp1_event',
		# capabilities meta which will need a role manager if not default
		'capability_type' => ECP1_CALENDAR_CAP,
		'map_meta_cap' => true, # make sure all meta capabilities are mapped
		'supports' => array( 'title' ),
		'rewrite' => array( 'slug' => 'calendar' ),
		'show_in_nav_menus' => false,
	);
	
	// Custom event post type arguments
	$ecp1_evt_args = array(
		'labels' => $ecp1_evt_labels,
		'description' => __( 'EveryCal+1 Events' ),
		'public' => true,
		'exclude_from_search' => true, # don't show events unless the plugin says to
		'show_ui' => true,
		'menu_position' => 30,
		# capabilities meta which will need a role manage if not default
		'capability_type' => ECP1_EVENT_CAP,
		'map_meta_cap' => true, # make sure all meta capabilities are mapped
		'supports' => array( 'title', 'thumbnail' ),
		'rewrite' => array( 'slug' => 'event' ),
		'show_in_nav_menus' => false,
	);
	
	// Register the custom post type
	register_post_type( 'ecp1_event', $ecp1_evt_args );
	register_post_type( 'ecp1_calendar', $ecp1_cal_args );
}

// Now define a capbilities filter to allow editors of calendars
// the ability to edit all events in that calendar (hopefully).
add_filter( 'map_meta_cap', 'ecp1_map_calendar_cap_to_event', 100, 4 );
function ecp1_map_calendar_cap_to_event( $caps, $cap, $user_id, $args ) {
	
	// Only proceed if we have a post argument (i.e. the event)
	// and it actually is a post type of ecp1_event
	$event_id = is_array( $args ) ? $args[0] : $args;
	$event = get_post( $event_id );
	if ( 'ecp1_event' == get_post_type( $post ) ) {
		// TODO: look at $cap and $user_id and ecp1_calendar in post meta
		// NOTE: This is a RoadMap feature at the moment it's faked
	}
	
	// Finally return the caps that are left over
	return $caps;

}

?>
