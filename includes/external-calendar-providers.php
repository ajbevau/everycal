<?php
/**
 * An associative array of map providers and the relevant JS to include to provide a map UI
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Define the calendar providers
$_ecp1_calendars = array(
	'google' => array( 'name' => 'Google Calendar', 'fullcal_plugin' => 'gcal.js', 'fullcal_datatype' => 'gcal' ),
);

// Function that shamelessly returns the above array so we don't have to global it
// Eventually the plan is to create a more dynamic array using this function
function ecp1_calendar_providers() {
	global $_ecp1_calendars;
	return $_ecp1_calendars;
}

?>
