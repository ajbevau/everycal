<?php
/**
 * Defines the meta fields for the event post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

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
	)
);
// TODO: Add social media pages to 'like' etc...
// TODO: Repeating events

// Function to parse the custom post fields into the fields above
function _ecp1_parse_event_custom( $post_id=-1 ) {
	global $post, $ecp1_event_fields;
	
	// Determine if we're using the global post or a parameter post
	// Parameter will take precedence over the global post
	if ( $post_id < 0 )
		$post_id = $post->ID;
	
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
	} elseif ( '' == $custom ) { // it does not exist yet (reset to defaults so empty settings don't display previous events details)
		foreach( $ecp1_event_fields as $key=>$values )
			$ecp1_event_fields[$key][0] = $ecp1_event_fields[$key][1];
	} else { // if the setting exists but is something else
		printf( '<pre>%s</pre>', __( 'Every Calendar +1 plugin found non-array meta fields for this event.' ) );
	} 
}

// Function that returns true if value is default
function _ecp1_event_meta_is_default( $meta ) {
	global $ecp1_event_fields;
	if ( ! isset( $ecp1_event_fields[$meta] ) )
		return false;
	
	return $ecp1_event_fields[$meta][1] == $ecp1_event_fields[$meta][0];
}

?>