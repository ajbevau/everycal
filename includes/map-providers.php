<?php
/**
 * An associative array of map providers and the relevant JS to include to provide a map UI
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Define the map providers
$ecp1_maps = array(
	'none' => array( 'id' => 0, 'name' => 'Disabled' ),
	'google' => array( 'id' => 1, 'name' => 'Google Maps', 'classname' => 'ECP1GoogleMaps', 'filename' => 'maps/google.php' ),
);

?>