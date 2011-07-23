<?php
/**
 * An associative array of map providers and the relevant JS to include to provide a map UI
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Define the map providers
$_ecp1_maps = array(
	'none' => array( 'name' => 'None' ),
	'google' => array( 'name' => 'Google Maps', 'classname' => 'ECP1GoogleMaps', 'filename' => 'maps/google.php' ),
);

// Function that shamelessly returns the above array so we don't have to global it
// Eventually the plan is to create a more dynamic array using this function
function ecp1_map_providers() {
	global $_ecp1_maps;
	return $_ecp1_maps;
}

?>