<?php
/**
 * An associative array of map providers and the relevant JS to include to provide a map UI
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Define the map providers
$_ecp1_maps = array(
	'none' => array( 'name' => 'None' ),
	'google' => array( 'name' => 'Google Maps', 'classname' => 'ECP1GoogleMap', 'filename' => 'google/provider.php' ),
);

// Function that shamelessly returns the above array so we don't have to global it
// Eventually the plan is to create a more dynamic array using this function
function ecp1_map_providers() {
	global $_ecp1_maps;
	return $_ecp1_maps;
}

// Function that returns an instance of the currently activated map provider
// or null if no instance can be created. This function does not check if
// maps can be used it simply assumes they can be.
function ecp1_get_map_provider_instance() {
	$providers = ecp1_map_providers();
	$provider = _ecp1_get_option( 'map_provider' );
	if ( ! array_key_exists( $provider, $providers ) )
		return null; // no such provider
	
	$provider = $providers[$provider];
	if ( ! array_key_exists( 'classname', $provider ) || ! array_key_exists( 'filename', $provider ) )
		return null; // no PHP class defined
	
	// Load the PHP class script and return an instance
	require_once( ECP1_DIR . '/includes/maps/' . $provider['filename'] );
	return new $provider['classname']();
}

?>