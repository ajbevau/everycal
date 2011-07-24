<?php
/**
 * Defines the custom post types for the Every Calendar +1 plugin
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

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
		#TODO: 'menu_icon' => get_bloginfo( 'plugin_url' ).'/img/cal_16.png',
		'menu_position' => 30,
		'capability_type' => 'post', # capabilities match posts
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
		'show_in_menu' => 'edit.php?post_type=ecp1_calendar',
		'capability_type' => 'post', # capabilities match posts
		'supports' => array( 'title', 'thumbnail', 'excerpt' ), //'editor' ),
		'rewrite' => array( 'slug' => 'event' ),
		'show_in_nav_menus' => false,
	);
	
	// Register the custom post type
	register_post_type( 'ecp1_calendar', $ecp1_cal_args );
	register_post_type( 'ecp1_event', $ecp1_evt_args );
}

?>
