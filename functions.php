<?php
/**
 * Every Calendar +1 Plugin Helper Functions
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Returns all calendars the user can edit
function _ecp1_current_user_calendars() {
	return get_posts( array( 'post_type'=>'ecp1_calendar', 'suppress_filters'=>false, 'numberposts'=>-1, 'nopaging'=>true ) );
}

// Helper to convert a UTC offset to a timezone
// This is a hardcoded map that SHOULD only ever get used if the
// WordPress option 'timezone_string' is empty and the calendar
// is set to use WordPress default timezone.
// 
// http://www.phpbuilder.com/board/showthread.php?t=10359010
function _ecp1_gmt_offset_to_timezone( $offset=0 ) {
	// This is only meant to be rough
	$tzs = array(
		'-12'   => 'Pacific/Kwajalein',
		'-11'   => 'Pacific/Samoa',
		'-10'   => 'Pacific/Honolulu',
		 '-9'   => 'America/Juneau',
		 '-8'   => 'America/Los_Angeles',
		 '-7'   => 'America/Denver',
		 '-6'   => 'America/Mexico_City',
		 '-5'   => 'America/New_York',
		 '-4'   => 'America/Caracas',
		 '-3.5' => 'America/St_Johns',
		 '-3'   => 'America/Argentina/Buenos_Aires',
		 '-2'   => 'America/Noronha', // close enough
		 '-1'   => 'Atlantic/Azores',
		 '0'    => 'Europe/London',
		 '1'    => 'Europe/Paris',
		 '2'    => 'Europe/Helsinki',
		 '3'    => 'Europe/Moscow',
		 '3.5'  => 'Asia/Tehran',
		 '4'    => 'Asia/Baku',
		 '4.5'  => 'Asia/Kabul',
		 '5'    => 'Asia/Karachi',
		 '5.5'  => 'Asia/Calcutta',
		 '6'    => 'Asia/Colombo',
		 '7'    => 'Asia/Bangkok',
		 '8'    => 'Asia/Singapore',
		 '9'    => 'Asia/Tokyo',
		 '9.5'  => 'Australia/Darwin',
		 '10'   => 'Pacific/Guam',
		 '11'   => 'Asia/Magadan',
		 '12'   => 'Asia/Kamchatka'
	);

	// Look for the offset if it doesn't exist e.g. 6.5 round up
	if ( ! array_key_exists( "$offset", $tzs ) )
		$offset = round( (int) $offset, 0, PHP_ROUND_HALF_UP );
	return $tzs["$offset"];
}

// Prettify a raw timezone name string into City +/- Offset
function ecp1_timezone_display( $tz ) {
	try {
		$dtz = new DateTimeZone( $tz );
		$offset = $dtz->getOffset( new DateTime( 'now' ) ); // automatically handles DST
		$offset = 'UTC' . ( $offset < 0 ? ' - ' : ' + ' ) . ( abs( $offset/3600 ) );
		$ex = explode( '/', $dtz->getName() );
		$name = str_replace( '_', ' ', ( isset( $ex[2] ) ? $ex[2] : isset( $ex[1] ) ? $ex[1] : $dtz->getName() ) ); // Continent/Country/City
		return sprintf ( '%s (%s)', $name, $offset );
	} catch( Exception $tzmiss ) {
		// not a valid timezone
		return __( 'Unknown' );
	}
}

// Creates an HTML select of all timezones
// Based on http://neo22s.com/timezone-select-for-php/
function _ecp1_timezone_select( $id, $pick='_', $extra_attrs=null ) {
	$outstr = sprintf( '<select id="%s" name="%s" %s>', $id, $id, $extra_attrs );
	$outstr .= sprintf( '<option value="_"%s>%s</option>', '_' == $pick ? ' selected="selected"' : '', __( 'WordPress Timezone' ) );
	$timezone_identifiers = DateTimeZone::listIdentifiers();
	foreach( $timezone_identifiers as $value ) {
		if ( preg_match( '/^(Africa|America|Antartica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $value ) ){
			$ex = explode( '/', $value ); //obtain continent and city
			if ( $continent != $ex[0] ) {
				if ( '' != $continent ) $outstr .= sprintf( '</optgroup>' );
				$outstr .= sprintf( '<optgroup label="%s">', $ex[0] );
			}

			$city = ecp1_timezone_display( $value );
			$continent = $ex[0]; // for next loop
			$outstr .= sprintf( '<option value="%s"%s>%s</option>', $value, $value == $pick ? ' selected="selected"' : '', $city );
		}
	}
	$outstr .= sprintf( '</optgroup></select>' );
	return $outstr;
}


// Function that returns the timezone string for a calendar
// by looking at the calendar and WordPress settings
function ecp1_get_calendar_timezone() {
        $raw_timezone = 'UTC';
        $timezone = get_option( 'timezone_string' );    // Use the WordPress default if available
        $gmt_offset = get_option( 'gmt_offset' );       // or can use the GMT Offset and map (approximately)
	
        if ( ! _ecp1_calendar_meta_is_default( 'ecp1_timezone' ) // Calendar TZ Set
			&& _ecp1_get_option( 'tz_change' ) )     //   and changes are allowed
                $raw_timezone = _ecp1_calendar_meta( 'ecp1_timezone', false );
	elseif ( ! empty( $timezone ) )   // Using WordPress city based timezone
		$raw_timezone = $timezone;
        elseif ( ! empty( $gmt_offset ) ) // Using WordPress GMT Offset
                $raw_timezone = _ecp1_gmt_offset_to_timezone( $gmt_offset ); // this is REALLY approximate

	// go back to UTC if null
	if ( is_null( $raw_timezone) )
		$raw_timezone = 'UTC';
	return $raw_timezone;
}

?>
