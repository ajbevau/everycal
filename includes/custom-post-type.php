<?php
/**
 * Defines the custom post types for the Every Calendar +1 plugin
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Add action hooks
add_action( 'init', 'ecp1_register_events' );
add_action( 'init', 'ecp1_register_calendars' );
add_action( 'ecp1_calendar_edit_form_fields', 'ecp1_calendar_custom_fields', 10, 2 );

// Function that creates the ECP1 Custom Post Types
function ecp1_register_events() {
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
		'menu_name' => __( 'Events' )
	);
	
	// Custom event post type arguments
	$ecp1_evt_args = array(
		'labels' => $ecp1_evt_labels,
		'description' => __( 'EveryCal+1 Events' ),
		'public' => true,
		'exclude_from_search' => true, # don't show events unless the plugin says to
		'show_ui' => true,
		'capability_type' => 'post', # capabilities match posts
		'supports' => array( 'title', 'thumbnail', 'excerpt', 'editor' ),
		'show_in_nav_menus' => false
	);
	
	// Register the custom post type
	register_post_type( 'ecp1_event', $ecp1_evt_args );
}

// Function that creates calendars as a custom taxonomy for the events
function ecp1_register_calendars() {
	// Labels for the calendar taxonomy
	$ecp1_cal_labels = array(
		'name' => _x( 'Calendars', 'taxonomy general name' ),
		'singular_name' => _x( 'Calendar', 'taxonomy singular name' ),
		'search_items' => __( 'Search Calendars' ),
		'popular_items' => __( 'Popular Calendars' ),
		'all_items' => __( 'All Calendars' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit Calendar' ),
		'update_item' => __( 'Update Calendar' ),
		'add_new_item' => __( 'Add New Calendar' ),
		'new_item_name' => __( 'New Calendar Name' ),
		'separate_items_with_commas' => __( 'Separate calendar names with commas' ),
		'add_or_remove_items' => __( 'Add or remove from calendars' ),
		'choose_from_most_used' => __( 'Choose from most used calendars' )
	);
	
	// Arguments for the taxonomy
	$ecp1_cal_args = array(
		'labels' => $ecp1_cal_labels,
		'public' => true,
		'show_in_nav_menus' => false,
		'show_ui' => true,
		'show_tagcloud' => false,
		'hierarchical' => true,
		'rewrite' => array( 'slug' => 'calendar' ),
		'query_var' => true
	);
	
	// Register the custom taxonomy to the custom type
	register_taxonomy( 'ecp1_calendar', 'ecp1_event', $ecp1_cal_args );
}

// Function that adds custom fields to the calendar taxonomy
function ecp1_calendar_custom_fields($tag, $taxonomy) {
	$calendar_url = get_metadata($tag->taxonomy, $tag->term_id, 'ecp1_calendar_external_url', true);
	if ( ! $calendar_url )
		$calendar_url = '';
	#TODO: ESCAPE THE URL PROPERLY
?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="ecp1_calendar_external_url">External Link</label></th>
		<td>
			<input id="ecp1_calendar_external_url" name="ecp1_calendar_external_url" type="text" value="<?php echo $calendar_url; ?>" /><br/>
			<p class="description">The URL of an external calendar you would like to display locally in this calendar. <em>Note: You cannot add events to calendars that have external URLs.</em></p>
		</td>
	</tr>
<?php
}

?>