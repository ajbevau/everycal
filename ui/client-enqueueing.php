<?php
/**
 * Registers hooks to enqueue styles and scripts for the client UI
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// We need the calendar providers for script enqueueing
require_once( ECP1_DIR . '/includes/external-calendar-providers.php' );
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// Enqueue the jQuery and jQuery UI scripts that FullCalendar requires
// + Enqueue the FulLCalendar JS and CSS
add_action( 'wp_enqueue_scripts', 'ecp1_add_client_scripts' );
function ecp1_add_client_scripts() {
	if ( is_single() || is_page() ) {
		// jQuery and jQuery UI first as they're required by FullCalendar
		wp_enqueue_script( 'jquery' );
		//wp_enqueue_script( 'jquery-ui-core' );
		
		// Register the FullCalendar scripts and styles
		wp_register_style( 'ecp1_fullcalendar_style_all', plugins_url( '/fullcalendar/fullcalendar.css', dirname( __FILE__ ) ), false, false, 'all' );
		wp_register_style( 'ecp1_fullcalendar_style_print', plugins_url( '/fullcalendar/fullcalendar.print.css', dirname( __FILE__ ) ), false, array( 'ecp1_fullcalendar_style_all' ), 'print' );
		wp_register_style( 'ecp1_client_style', plugins_url( '/css/ecp1-client.css', dirname( __FILE__ ) ), false, array( 'ecp1_fullcalendar_style_all' ), 'all' );
		wp_register_script( 'ecp1_fullcalendar_script', plugins_url( '/fullcalendar/fullcalendar.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		
		// Enqueue the registered scripts and styles
		wp_enqueue_style( 'ecp1_fullcalendar_style_all' );
		wp_enqueue_style( 'ecp1_fullcalendar_style_print' );
		wp_enqueue_style( 'ecp1_client_style' );
		wp_enqueue_script( 'ecp1_fullcalendar_script' );
		
		// Are there any enabled external calendar providers we should enqueue?
		if ( _ecp1_get_option( 'use_external_cals' ) ) {
			$providers = ecp1_calendar_providers();
			foreach( $providers as $provider=>$details ) {
				if ( _ecp1_calendar_provider_enabled( $provider ) ) {
					wp_register_script( 'ecp1_calendar_provider-' . $provider, plugins_url( '/fullcalendar/' . $details['fullcal_plugin'], dirname( __FILE__ ) ), array( 'ecp1_fullcalendar_script' ) );
					wp_enqueue_script( 'ecp1_calendar_provider-' . $provider );
				}
			}
		}
	}
}

?>