<?php
/**
 * Google Maps implementation of the ECP1MapInterface abstract class
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// And that we know about the abstract class
require_once( ECP1_DIR . '/includes/maps/map-interface.php' );

// The Google Maps implementation class
class ECP1GoogleMap extends ECP1Map {
	
	// Resource files for Google Maps
	private $resources = array( 'admin_js'=>'google/admin.js', 'admin_css'=>null, 'client_js'=>'google/client.js', 'client_css'=>null );
	
	// Return file names of JS/CSS need or null if none
	public function get_resources( $type=null, $file=null ) {
		if ( is_null( $type ) || is_null( $file ) || ! is_numeric( $type ) || ! is_numeric( $file ) )
			return null;
		
		$type = ECP1Map::ECP1MAP_ADMIN == $type ? 'admin' : ( ECP1Map::ECP1MAP_CLIENT == $type ? 'client' : '' );
		$file = ECP1Map::ECP1MAP_SCRIPT == $file ? 'js' : ( ECP1Map::ECP1MAP_STYLE == $file ? 'css' : '' );
		$lookup = $type . '_' . $file;
		return array_key_exists( $lookup, $this->resources ) ? $this->resources[$lookup] : null;
	}
	
	// Returns the onload event initialization function
	// In this implementation the Google Maps library is loaded async via the function
	public function get_onload_function() {
		return 'InitGoogleMaps';
	}
	
	// Returns the Map Render function
	// This function actually calls two separate functions depending on the options hash
	public function get_maprender_function() {
		return 'RenderGoogleMap';
	}

	// Returns the Center Point function
	public function get_centerpoint_function() {
		return 'GetGoogleMapCenterPoint';
	}

	// Returns the Marker List function
	public function get_markerlist_function() {
		return 'GetGoogleMapMarkers';
	}

	// Returns the Add Marker function
	public function get_addmarker_function() {
		return 'AddGoogleMapMarker';
	}

	// Returns the Get Map Zoom function
	public function get_mapzoom_function() {
		return 'GetGoogleMapZoom';
	}
	
	// Returns the Unload Map function
	public function get_unload_function() {
		return 'DeleteGoogleMap';
	}
	
	// Yes we support geocoding and are ready to run
	public function good_to_go() { return true; }
	public function support_geocoding() { return true; }
	
}

?>
