<?php
/**
 * Defines the meta fields for the event post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// The Event needs to know about calendars
require_once( ECP1_DIR . '/includes/data/calendar-fields.php' );

// An array of meta field names and default values
$ecp1_event_fields = array( 
	'ecp1_summary' => array( '', '' ), // value, default
	'ecp1_description' => array( '', '' ),
	'ecp1_url' => array( '', '' ),
	'ecp1_start_ts' => array( '', '' ),
	'ecp1_end_ts' => array( '', '' ),
	'ecp1_full_day' => array( '', 'N' ),
	'ecp1_calendar' => array( '', '' ),
	'ecp1_location' => array( '', '' ),
	'ecp1_coord_lat' => array( '', '' ),
	'ecp1_coord_lng' => array( '', '' ),
	
	// meta fields that describe the database structure
	'_meta' => array(
		'standalone' => array(	// $ecp1_event_fields key => postmeta table key
			'ecp1_start_ts' => 'ecp1_event_start',
			'ecp1_end_ts' => 'ecp1_event_end',
			'ecp1_calendar' => 'ecp1_event_calendar'
		),
		'calendar_tz' => 'UTC', // the TZ of the parent calendar
		'_loaded' => false, // custom fields not yet loaded
		'_id' => null, // the event ID
	)
);
// TODO: Add social media pages to 'like' etc...
// TODO: Repeating events
$_tmp = 'nothing here';

// Function to parse the custom post fields into the fields above
function _ecp1_parse_event_custom( $post_id=-1 ) {
	global $post, $ecp1_event_fields;
	
	// Determine if we're using the global post or a parameter post
	// Parameter will take precedence over the global post
	if ( $post_id < 0 )
		$post_id = $post->ID;

	// For efficiency sake only do this if not loaded or loading a new one
	if ( $ecp1_event_fields['_meta']['_loaded'] && $post_id == $ecp1_event_fields['_meta']['_id'] )
		return;
	
	// Load the basic meta for this event post
	$custom = get_post_meta( $post->ID, 'ecp1_event', true ); // will be everything NOT in _meta['standalone']

	// parse the custom meta fields into the value keys
	if ( is_array( $custom ) ) {
		// load the remaining meta fields from standalone into $custom
		foreach( $ecp1_event_fields['_meta']['standalone'] as $field_key=>$table_key )
			$custom[$field_key] = get_post_meta( $post->ID, $table_key, true );
		
		// look at all the non-meta keys and copy the database value in or use defaults
		foreach( array_keys( $ecp1_event_fields ) as $key ) {
			if ( '_meta' != $key ) {
				if ( isset( $custom[$key] ) )
					$ecp1_event_fields[$key][0] = $custom[$key];
				else
					$ecp1_event_fields[$key][0] = $ecp1_event_fields[$key][1];
			}
		}

		// Now lookup the calendar for this event and store calendar timezone
		if ( $ecp1_event_fields['ecp1_calendar'][1] != $ecp1_event_fields['ecp1_calendar'][0] ) {
			_ecp1_parse_calendar_custom( $ecp1_event_fields['ecp1_calendar'][0] );
			$ecp1_event_fields['_meta']['calendar_tz'] = ecp1_get_calendar_timezone();
		} // otherwise use UTC default

		// Flag the settings as loaded
		$ecp1_event_fields['_meta']['_loaded'] = true;
		$ecp1_event_fields['_meta']['_id'] = $post_id;
	} elseif ( '' == $custom ) { // it does not exist yet (reset to defaults so empty settings don't display previous events details)
		foreach( $ecp1_event_fields as $key=>$values ) {
			if ( '_meta' != $key )
				$ecp1_event_fields[$key][0] = $ecp1_event_fields[$key][1];
		}
		// Flag as loaded
		$ecp1_event_fields['_meta']['_loaded'] = true;
		$ecp1_event_fields['_meta']['_id'] = $post_id;
	} else { // if the setting exists but is something else
		printf( '<pre>%s</pre>', __( 'Every Calendar +1 plugin found non-array meta fields for this event.' ) );
	} 
}

// Function that returns true if value is default
function _ecp1_event_meta_is_default( $meta ) {
	global $ecp1_event_fields;
	if ( ! isset( $ecp1_event_fields[$meta] ) )
		return false; // unknown meta can't be at default
	if ( ! $ecp1_event_fields['_meta']['_loaded'] )
		return true; // if not loaded then treat as default
	
	return $ecp1_event_fields[$meta][1] == $ecp1_event_fields[$meta][0];
}

// Function that gets the meta value the get_default parameter
// controls what to do if settings are not yet loaded. If it is
// false and not loaded NULL will be returned, else the default.
function _ecp1_event_meta( $meta, $get_default=true ) {
	global $ecp1_event_fields;
	if ( ! isset( $ecp1_event_fields[$meta] ) )
		return null; // unknown meta is always NULL

	// if loaded then return value
	if ( $ecp1_event_fields['_meta']['_loaded'] )
		return $ecp1_event_fields[$meta][0];
	elseif ( $get_default ) // not loaded but want defaults
		return $ecp1_event_fields[$meta][1];
	else // not loaded and want NULL if so
		return null;
}

?>