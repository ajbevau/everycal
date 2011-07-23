<?php
/**
 * Defines the plugin settings defaults and helper functions
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Define the database option name the plugin uses
define( 'ECP1_OPTIONS_GROUP', 'ecp1_options' );
define( 'ECP1_GLOBAL_OPTIONS', 'ecp1_global' );

// The defaults array of settings
$_ecp1_settings = array(

	// Meta setting that tells if the db options have been loaded
	'_db' => false,

	// Should a map be available for the event location: default true
	'use_maps' => array( 'default' => 1 ),
	
	// Which Map Provider should be used: default none
	// Should be set to a key out of the Map Providers array
	'map_provider' => array( 'default' => 'none' ),
	
	// Allow calendars to change the timezone they're for from WordPress: default true
	'tz_change' => array( 'default' => 1 ),

);

// Helper function that returns the whole options array or just the 
// value if a key specified - where the key is not in the database
// the default value is returned.
function _ecp1_get_option( $option_key, $reload_from_db=false ) { return _ecp1_get_options( $option_key, $reload_from_db ); }
function _ecp1_get_options( $option_key=null, $reload_from_db=false ) {
	global $_ecp1_settings;
	
	// Read the database settings if they haven't been or are needed again
	if ( ! $_ecp1_settings['_db'] || $reload_from_db ) {
		$dbopts = get_option( ECP1_GLOBAL_OPTIONS );
		
		// Loop over the default settings and load values where appropriate
		foreach( $_ecp1_settings as $key=>$defaults ) {
			if ( isset( $dbopts[$key] ) )
				$_ecp1_settings[$key]['value'] = $dbopts[$key];
		}
	}
	
	// Do they just want the value of the key option?
	// This is done here for a minor efficiency boost
	if ( ! is_null( $option_key ) ) {
		if ( ! array_key_exists( $option_key, $_ecp1_settings ) )
			return null;
		return isset( $_ecp1_settings[$option_key]['value'] ) ? $_ecp1_settings[$option_key]['value'] : $_ecp1_settings[$option_key]['default'] ;
	}
		
	// Build an array of the actual values
	$real_settings = array();
	foreach( $_ecp1_settings as $key=>$values ) {
		if ( is_array( $values ) )
			$real_settings[$key] = isset( $values['value'] ) ? $values['value'] : $values['default'];
	}
	
	// Finally return the whole array
	return $real_settings;
}

// Tests if the option is at it's default value
function _ecp1_option_is_default( $key ) {
	global $_ecp1_settings;
	if ( ! array_key_exists( $key, $_ecp1_settings ) )
		return false;	// Unknown key
	if ( ! isset( $_ecp1_settings[$key]['value'] ) )
		return true;	// No value MUST BE default
	return $_ecp1_settings[$key]['default'] == $_ecp1_settings[$key]['value'];
}

// Returns the default value for the given option
function _ecp1_option_get_default( $key ) {
	global $_ecp1_settings;
	if ( ! array_key_exists( $key, $_ecp1_settings ) )
		return null;
	return $_ecp1_settings[$key]['default'];
}

// Creates an HTML select of all timezones 
// Based on http://neo22s.com/timezone-select-for-php/
function _ecp1_timezone_select( $id, $pick='_', $extra_attrs=null ) {
	$outstr = sprintf( '<select id="%s" name="%s" %s>', $id, $id, $extra_attrs );
	$outstr .= sprintf( '<option value="_"%s>%s</option>', '_' == $pick ? ' selected="selected"' : '', __( 'WordPress Default' ) );
	$timezone_identifiers = DateTimeZone::listIdentifiers();
	foreach( $timezone_identifiers as $value ) {
		if ( preg_match( '/^(Africa|America|Antartica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $value ) ){
			$ex = explode( '/', $value ); //obtain continent and city	
			if ( $continent != $ex[0] ) {
				if ( '' != $continent ) $outstr .= sprintf( '</optgroup>' );
				$outstr .= sprintf( '<optgroup label="%s">', $ex[0] );
			}
			
			// create an offset value so people can pick the right place
			$dtz = new DateTimeZone( $value );
			$offset = $dtz->getOffset( new DateTime( 'now' ) );
			$offset = 'UTC' . ( $offset < 0 ? '-' : '+' ) . ( abs( $offset/3600 ) );
			
			$city = isset( $ex[2] ) ? $ex[2] : $ex[1]; // Continent/Country/City
			$continent = $ex[0]; // for next loop
			$outstr .= sprintf( '<option value="%s"%s>%s (%s)</option>', $value, $value == $pick ? ' selected="selected"' : '', $city, $offset );
		}
	}
	$outstr .= sprintf( '</optgroup></select>' );
	return $outstr;
}

?>
