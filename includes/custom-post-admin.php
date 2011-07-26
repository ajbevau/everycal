<?php
/**
 * Adds the Admin panel extra messages and post fields
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Functions that will enqueue CSS / JS based on param
function ecp1_enqueue_admin_css() {
	wp_register_style( 'ecp1_admin_style', plugins_url( '/css/ecp1-admin.css', dirname( __FILE__ ) ) );
	wp_enqueue_style( 'ecp1_admin_style' );
}
// Now for the JS
function ecp1_enqueue_admin_js() {
	wp_enqueue_script( 'jquery' );
}
// Specialised function for jQuery Date Picker
function ecp1_event_edit_libs() {
	wp_register_style( 'ecp1_jquery-ui-datepicker_style', plugins_url( '/jquery-ui/datepicker.css', dirname( __FILE__ ) ) );
	wp_enqueue_style( 'ecp1_jquery-ui-datepicker_style' );

	wp_register_script( 'ecp1_jquery-ui-datepicker_script', plugins_url( '/jquery-ui/datepicker.min.js', dirname( __FILE__ ) ), array( 'jquery-ui-core' ) );
	wp_register_script( 'ecp1_event_datepicker_script', plugins_url( '/js/datepicker.js', dirname( __FILE__ ) ), array( 'ecp1_jquery-ui-datepicker_script' ) );

	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'ecp1_jquery_ui_datepicker_script' );
	wp_enqueue_script( 'ecp1_event_datepicker_script' );
	
	// Include the TinyMCE editor - this requires use of the_editor($content, 'element_id')
	// inplace of the <textarea></textarea> tags on the event meta box - and naturally will
	// obey user preferences on richtext editors etc...
	if ( user_can_richedit() ) {
		wp_register_script( 'ecp1_event_wysiwyg_script', plugins_url( '/js/tinymce.js', dirname( __FILE__ ) ), false, false, true );	
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_scripts( 'editor' );
		if ( function_exists( 'add_thickbox' ) ) add_thickbox();
		wp_enqueue_scripts( 'media-upload' );
		if ( function_exists( 'wp_tiny_mce' ) ) wp_tiny_mce();
		wp_admin_css();
		wp_enqueue_script( 'utils' );
		do_action( 'admin_print_styles-post-php' );
		do_action( 'admin_print_styles' );
		wp_enqueue_script( 'ecp1_event_wysiwyg_script' );
	}
}

// Add the CSS for either post type
add_action( 'admin_enqueue_scripts', 'ecp1_add_admin_styles', 100 );
function ecp1_add_admin_styles() {
	global $post_type;
	if ( 'ecp1_calendar' == $post_type || 'ecp1_event' == $post_type ) {
		ecp1_enqueue_admin_css();
	}
}

// Add the global JS for either post type
add_action( 'admin_enqueue_scripts', 'ecp1_add_admin_scripts', 100, 1 );
function ecp1_add_admin_scripts( $hook=null ) {
	global $post_type;
	if ( 'ecp1_calendar' == $post_type || 'ecp1_event' == $post_type ) {
		ecp1_enqueue_admin_js();
	}
	if ( 'ecp1_event' == $post_type && in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
		ecp1_event_edit_libs();
	}
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
include_once( ECP1_DIR . '/includes/data/calendar-fields-admin.php' );
include_once( ECP1_DIR . '/includes/data/event-fields-admin.php' );

?>
