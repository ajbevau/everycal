<?php
/**
 * An associative array of map providers and the relevant JS to include to provide a map UI
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Define the map providers
$ecp1_maps = array(
	'none' => array( 'name' => 'None' ),
	'google' => array( 'name' => 'Google Maps', 'classname' => 'ECP1GoogleMaps', 'filename' => 'maps/google.php' ),
);

?>