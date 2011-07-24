<?php
/**
 * Registers hooks to enqueue styles and scripts for the client UI
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Define a global variable for the dynamic FullCalendar load script
$_ecp1_dynamic_calendar_script = null;

// Enqueue the jQuery and jQuery UI scripts that FullCalendar requires
// + Enqueue the FulLCalendar JS and CSS
add_action( 'wp_enqueue_scripts', 'ecp1_add_jquery' );
function ecp1_add_jquery() {
	global $post;
	if ( is_single() && ( 'ecp1_calendar' == $post->post_type || 'ecp1_event' == $post->post_type ) ) {
		// jQuery and jQuery UI first as they're required by FullCalendar
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );
		
		// Register the FullCalendar scripts and styles
		wp_register_style( 'ecp1_fullcalendar_style_all', plugins_url( '/fullcalendar/fullcalendar.css', dirname( __FILE__ ) ), false, false, 'all' );
		wp_register_style( 'ecp1_fullcalendar_style_print', plugins_url( '/fullcalendar/fullcalendar.print.css', dirname( __FILE__ ) ), false, array( 'ecp1_fullcalendar_style_all' ), 'print' );
		wp_register_script( 'ecp1_fullcalendar_script', plugins_url( '/fullcalendar/fullcalendar.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui' ) );
		// TODO: Register the minified version of the script
		
		// Enqueue the registered scripts and styles
		wp_enqueue_style( 'ecp1_fullcalendar_style_all' );
		wp_enqueue_style( 'ecp1_fullcalendar_style_print' );
		wp_enqueue_script( 'ecp1_fullcalendar_script' );
	}
}

// Function that will return the necessary HTML blocks and queue some static
// JS for the document load event to render a FullCalendar instance
function ecp1_render_calendar( $calendar ) {
	global $_ecp1_dynamic_calendar_script;
	
	// Register a hook to print the static JS to load FullCalendar on #ecp1_calendar
	add_action( 'wp_print_footer_scripts', 'ecp1_print_fullcalendar_load' );
	
	// Now build the actual JS that will be loaded
	$_ecp1_dynamic_calendar_script = 'alert("this is it");';
	
	// Now return HTML that the above script will use
	return sprintf( '<div id="#ecp1_calendar">%s</div>', __( 'Loading...' ) );
}

// Function to print the dynamic load script 
function ecp1_print_fullcalendar_load() {
	global $_ecp1_dynamic_calendar_script;
	if ( null != $ecp1_dynamic_calendar_script ) {
		echo $_ecp1_dynamic_calendar_script;
	}
}

?>