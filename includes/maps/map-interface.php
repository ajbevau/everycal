<?php
/**
 * Abstract implementation of the ECP1 Maps pluggable interface.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// The abstract maps class
abstract class ECP1Map {
	
	// Constants for lookups in the get_resources function
	const ECP1MAP_CLIENT = 100;
	const ECP1MAP_ADMIN  = 101;
	const ECP1MAP_SCRIPT = 200;
	const ECP1MAP_STYLE  = 201;
	
	// Abstract function that returns just the filename of any JS or CSS
	// to load into the browser session: there are four parameter combos
	// type: ECP1MAP_CLIENT or ECP1MAP_ADMIN; and
	// file: ECP1MAP_SCRIPT or ECP1MAP_STYLE
	// e.g. $this->get_resources( client, style );
	//
	// If a particular resource is not needed / provided return null
	//
	// Paths should be relative to includes/maps/ (e.g. google/load.js)
	abstract public function get_resources( $type=null, $file=null );
	
	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called when a page is loaded 
	// (onload) that contains maps.
	//
	// The JS function should configure the global environment so maps can
	// be rendered (e.g. dynamically insert the Map JS library into DOM).
	//
	// The JS function should accept one parameter which is a callback 
	// function to be executed once the initialization is done.
	abstract public function get_onload_function();
	
	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to render a map onto
	// the page. This function will always be called AFTER the onload above
	// BUT it is your job to track that the environment is ready.
	//
	// For example the Google Maps interface callback function sets a global
	// "I'm Ready" variable that the render function checks before acting.
	// 
	// The JS function should accept an options hash in two different forms:
	// 1) { 'element': ID, 'lat': Lat, 'lng': Long, 'mark': DisplayAMarker }
	// 2) { 'element': ID, 'location': TextString, 'mark': DisplayAMarker }
	//
	// The function is called depending on if coords exist => 1 else => 2
	// If the map provider does not support geocoding and the event does not
	// have lat/long meta values then NEITHER will be called.
	abstract public function get_maprender_function();

	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to get the center
	// point of a map. The map container ID will be passed to the function.
	//
	// This should return { 'lat':X, 'lng':Y }
	abstract public function get_centerpoint_function();

	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to get an array of 
	// markers on a given map. If the map provider does not support markers
	// an empty array should be returned.
	//
	// The map container ID will be passed to the function.
	//
	// This should return array of { 'lat':X, 'lng':Y, 'src':URL to Image }
	abstract public function get_markerlist_function();

	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to add a marker to a
	// map that has been created. If the provider does not support markers
	// then the function can do nothing.
	//
	// Params: Map container ID and { 'lat':X, 'lng':Y, 'src':URL to Image }
	abstract public function get_addmarker_function();

	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to get the zoom level
	// of a given map. The map container ID will be passed to the function.
	//
	// Return a number.
	abstract public function get_mapzoom_function();

	// Should return the name of a function defined in the CLIENT and ADMIN
	// SCRIPT where the named function will be called to unload a map. The
	// map container ID will be passed to the function.
	abstract public function get_unload_function();

	// Returns true or false if this Map Provider can be used now
	// Always returns false in the abstract class
	public function good_to_go() {
		return false;
	}
	
	// Does this map provider support geocoding of location strings?
	// Always returns false in the abstract class
	public function support_geocoding() {
		return false;
	}

}

?>
