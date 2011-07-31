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
	// The JS function will be called in two different ways:
	// 1) func( ElementID, CoordX, CoordY, DisplayAMarker ); or
	// 2) func( ElementID, LocationString, DisplayAMarker )
	//
	// The function is called depending on if coords exist => 1 else => 2
	// but if the map provider does not support geocoding and there are
	// no coords then NEITHER will be called.
	abstract public function get_maprender_function();
	
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