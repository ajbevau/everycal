<?php
/**
 * Defines the meta fields for the calendar post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// An array of meta field names and default values
$ecp1_calendar_fields = array( 
	'ecp1_description' => array( '', ''), // value, default
	'ecp1_external_url' => array( '', ''),
	'ecp1_timezone' => array( '', '_'),
	'ecp1_first_day' => array( '', -1 ),
	'ecp1_default_view' => array( '', 'none' ),
);

// Function to parse the custom post fields into the fields above
function _ecp1_parse_calendar_custom( $post_id=-1 ) {
	global $post, $ecp1_calendar_fields;
	
	// Determine if we're using the global post or a parameter post
	// Parameter will take precedence over the global post
	if ( $post_id < 0 )
		$post_id = $post->ID
	
	// Load the basic meta for this calendar post
	$custom = get_post_meta( $post_id, 'ecp1_calendar', true );

	// parse the custom meta fields into the value keys
	if ( is_array( $custom ) ) {
		foreach( array_keys( $ecp1_calendar_fields ) as $key ) {
			if ( isset( $custom[$key] ) )
				$ecp1_calendar_fields[$key][0] = $custom[$key];
			else
				$ecp1_calendar_fields[$key][0] = $ecp1_calendar_fields[$key][1];
		}
	} elseif ( '' == $custom ) { // it does not exist yet (reset to defaults so empty settings don't display previous calendars details)
		foreach( $ecp1_calendar_fields as $key=>$values )
			$ecp1_calendar_fields[$key][0] = $ecp1_calendar_fields[$key][1];
	} else { // if the setting exists but is something else
		printf( '<pre>%s</pre>', __( 'Every Calendar +1 plugin found non-array meta fields for this calendar.' ) );
	} 
}

// Function that returns true if value is default
function _ecp1_calendar_meta_is_default( $meta ) {
	global $ecp1_calendar_fields;
	if ( ! isset( $ecp1_calendar_fields[$meta] ) )
		return false;
	
	return $ecp1_calendar_fields[$meta][1] == $ecp1_calendar_fields[$meta][0];
}

?>