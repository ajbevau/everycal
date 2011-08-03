<?php
/**
 * Registers hooks to enqueue styles and scripts for the client UI
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// We need the calendar providers for script enqueueing
require_once( ECP1_DIR . '/includes/external-calendar-providers.php' );
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// Define a global variable for the dynamic FullCalendar load script
$_ecp1_dynamic_calendar_script = null;

// Define a global variable for the dynamic load script for events (e.g. Maps)
$_ecp1_dynamic_event_script = null;

// Function that will return the necessary HTML blocks and queue some static
// JS for the document load event to render a FullCalendar instance
function ecp1_render_calendar( $calendar ) {
	global $_ecp1_dynamic_calendar_script;
	
	// Make sure the calendar provided is valid
	if ( ! is_array( $calendar ) )
		return sprintf( '<div id="ecp1_calendar" class="ecp1_error">%s</div>', __( 'Invalid calendar cannot display.' ) );

	// The parameter NEEDS to contain post slug
	if ( ! isset( $calendar['slug'] ) || empty( $calendar['slug'] ) )
		return sprintf( '<div id="ecp1_calendar" class="ecp1_error">%s</div>', __( 'No calendar slug provided cannot fetch events.' ) );
	
	// Extract the calendar meta data or go to renderer defaults
	
	// First day of the week
	$first_day = get_option( 'start_of_week ' );	// 0=Sunday 6=Saturday (uses WordPress)
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_first_day' ) && is_numeric( $calendar['ecp1_first_day'][0] ) &&
			( 0 <= $calendar['ecp1_first_day'][0] && $calendar['ecp1_first_day'][0] <= 6 ) ) {
		$first_day = $calendar['ecp1_first_day'][0];
	}
	
	// Text based description make sure it's escaped
	$description = $calendar['ecp1_description'][1];
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_description' ) )
		$description = wp_filter_post_kses( $calendar['ecp1_description'][0] );
	
	// Timezone events in this calendar occur in
	$raw_timezone = ecp1_get_calendar_timezone();
	$timezone = ecp1_timezone_display( $raw_timezone );
	
	$default_view = 'month';	// How the calendar displays by default
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_default_view' ) &&
			in_array( $calendar['ecp1_default_view'][0], array( 'month', 'week', 'day' ) ) ) {
		$default_view = $calendar['ecp1_default_view'][0];
	}

	// Default parameters for event sources
	$event_source_params = array(
		'_defaults' => array( // Note values ARE NOT quoted automatically do it HERE
			'startParam' => "'ecp1_start'",  # default is start but plugin uses ecp1_start
			'endParam'   => "'ecp1_end'",    #  as above but for end
			'ignoreTimezone' => 'false',     # don't ignore ISO8601 timezone details
			'color'     => "'#36c'",         # the event background and border colours
			'textColor' => "'#fff'",         #  and the text colour (any css format)
		)
	);
	
	// Create a URL and event parameter array for local event posts
	$event_source_params['local'] = $event_source_params['_defaults'];
	$event_source_params['local']['url'] = sprintf( "'%s/ecp1/%s/events.json'", site_url(), $calendar['slug'] );
	// TODO: Allow color/textColor customisation

	// Test if there are external URLs and create source params as needed
	// TODO: Abstract this to allow MANY external calendars
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_external_url' ) && _ecp1_get_option( 'use_external_cals' ) ) {
		$event_source_params['external0'] = $event_source_params['_defaults'];
		$event_source_params['external0']['url'] = sprintf( "'%s'", urldecode( $calendar['ecp1_external_url'][0] ) );
		unset( $event_source_params['external0']['startParam'] );
		unset( $event_source_params['external0']['endParam'] );
		// Google Accounts can have incorrect time offsets so peg to blog settings
		// Set the external event data source to use red bubbles
		// TODO: Make such source options controlable from the admin: how?
		$event_source_params['external0']['currentTimezone'] = "'$raw_timezone'"; # Quoted see _defaults
		$event_source_params['external0']['color'] = "'#cc3333'"; # Quoted see _defaults
		$event_source_params['external0']['dataType'] = "'gcal'"; # TODO: This should come from the provider array
	}

	// Get rid of the defaults and write out an event sources array
	unset( $event_source_params['_defaults'] );
	$separator = '';
	$event_sources = '[';
	foreach( $event_source_params as $skey=>$params ) {
		$event_sources .= sprintf( "%s { _ecp1sn: '%s'", $separator, $skey );
		foreach( $params as $key=>$value )
			// The value IS NOT automatically quoted to allow for ANY param value
			// The parameters MUST be quoted above when added to the array
			$event_sources .= sprintf( ", %s: %s", $key, $value );
		$event_sources .= ' }';
		$separator = ',';
	}
	$event_sources .= ']';

	// Register a hook to print the static JS to load FullCalendar on #ecp1_calendar
	add_action( 'wp_print_footer_scripts', 'ecp1_print_fullcalendar_load' );

	// A string for i18n-ing the read more links
	$_read_more = htmlspecialchars( __( 'Read more...' ) );
	$_show_map = htmlspecialchars( __( 'Location Map' ) );
	$_load_map = htmlspecialchars( __( 'Loading map...' ) );
	$_back_to_event = htmlspecialchars( __( 'Back to Event' ) );
	$_large_map = htmlspecialchars( __( 'Large Map' ) );
	$_geocode_addresses = 'false';
	$_use_maps = 'false';
	$_map_show_func = 'false';
	$_map_delete_func = 'false';
	$_map_center_func = 'false';
	$_map_markers_func = 'false';
	$_map_addmarker_func = 'false';
	$_map_getzoom_func = 'false';
	$_init_maps_func = '';

	if ( _ecp1_get_option( 'use_maps' ) ) {
		$_use_maps = 'true';
		$mapinstance = ecp1_get_map_provider_instance();
		if ( ! is_null( $mapinstance ) ) {
			if ( $mapinstance->support_geocoding() )
				$_geocode_addresses = 'true';
			$_map_show_func = sprintf( 'function( args ) { %s( args ); }',
						$mapinstance->get_maprender_function() );
			$_map_delete_func = sprintf( 'function( args ) { %s( args ); }',
						$mapinstance->get_unload_function() );
			$_map_center_func = sprintf( 'function( args ) { return %s( args ); }',
						$mapinstance->get_centerpoint_function() );
			$_map_markers_func = sprintf( 'function( args ) { return %s( args ); }',
						$mapinstance->get_markerlist_function() );
			$_map_addmarker_func = sprintf( 'function( id, args ) { %s( id, args ); }',
						$mapinstance->get_addmarker_function() );
			$_map_getzoom_func = sprintf( 'function( args ) { return %s( args ); }',
						$mapinstance->get_mapzoom_function() );
			$_init_maps_func = sprintf( '%s()', $mapinstance->get_onload_function() ); // might not be used so need () here
		}
	}

	// Now build the actual JS that will be loaded
	$_ecp1_dynamic_calendar_script = <<<ENDOFSCRIPT
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	$('#ecp1_calendar div.fullcal').empty().fullCalendar({
		header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek' },
		timeFormat: { agenda: 'h:mmtt( - h:mmtt )', '': 'h(:mm)tt' },
		firstDay: $first_day,
		weekends: true,
		defaultView: '$default_view',
		eventSources: $event_sources,
		eventClick: ecp1_onclick,
		eventRender: ecp1_onrender
	});

	// Assign the global Read More / Show Map link variable for i18n
	_readMore = '$_read_more';
	_showMapStr = '$_show_map';
	_loadMapStr = '$_load_map';
	_showEventDetails = '$_back_to_event';
	_showLargeMap = '$_large_map';
	_geocodeAddr = $_geocode_addresses;
	_showMap = $_use_maps;
	_mapLoadFunction = $_map_show_func;
	_mapDeleteFunction = $_map_delete_func;
	_mapCenterFunction = $_map_center_func;
	_mapMarkersFunction = $_map_markers_func;
	_mapAddMarkerFunction = $_map_addmarker_func;
	_mapGetZoomFunction = $_map_getzoom_func;
	$_init_maps_func;
});
ENDOFSCRIPT;
	
	// Now return HTML that the above script will use
	$description = '' != $description ? '<p><strong>' . $description . '</strong></p>' : '';
	$timezone = '<p><em>Events occur at ' . $timezone . ' local time.</em></p>';
	return sprintf( '<div id="ecp1_calendar">%s<div class="fullcal">%s</div>%s</div>', $description, __( 'Loading...' ), $timezone );
}

// Function to print the dynamic calendar load script 
function ecp1_print_fullcalendar_load() {
	global $_ecp1_dynamic_calendar_script;
	if ( null != $_ecp1_dynamic_calendar_script ) {
		printf( '%s<!-- Every Calendar +1 Init -->%s<script type="text/javascript">/* <![CDATA[ */%s%s%s/* ]]> */</script>%s', "\n", "\n", "\n", $_ecp1_dynamic_calendar_script, "\n", "\n" );
	}
}

// Function that will return the necessary HTML blocks and queue some static
// JS for the document load event to render an event post page
function ecp1_render_event( $event ) {
	global $_ecp1_dynamic_event_script, $post;
	
	// Make sure the event provided is valid
	if ( ! is_array( $event ) )
		return sprintf( '<div id="ecp1_event" class="ecp1_error">%s</div>', __( 'Invalid event cannot display.' ) );
	
	// Register a hook to print the static JS to load FullCalendar on #ecp1_calendar
	add_action( 'wp_print_footer_scripts', 'ecp1_print_event_load' );
	
	// Extract the event fields or go to renderer defaults
	// $p variables are placeholders for the i18n titles
	$pwhen = __( 'When' );
	$pwhere = __( 'Where' );
	$psummary = __( 'Quick Info' );
	$pdetails = __( 'Details' );
	
	// String placeholder for the time period this event runs over
	$ecp1_time = __( 'Unknown' );
	if ( ! _ecp1_event_meta_is_default( 'ecp1_start_ts' ) || ! _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ) {
		$ecp1_time = ecp1_formatted_date_range( $event['ecp1_start_ts'][0], $event['ecp1_end_ts'][0],
							$event['ecp1_full_day'][0], $event['_meta']['calendar_tz'] );
	}
	
	// String placeholder for the summary text
	$ecp1_summary = $event['ecp1_summary'][1];
	if ( ! _ecp1_event_meta_is_default( 'ecp1_summary' ) )
		$ecp1_summary = wp_filter_post_kses( $event['ecp1_summary'][0] );
	
	// String placeholders for the location and map coords if enabled
	$ecp1_location = $event['ecp1_location'][1];
	if ( ! _ecp1_event_meta_is_default( 'ecp1_location' ) )
		$ecp1_location = htmlspecialchars( $event['ecp1_location'][0] );

	$ecp1_map_placeholder = ''; 
	if ( _ecp1_get_option( 'use_maps' ) && 'Y' == _ecp1_event_meta( 'ecp1_showmap' ) ) {
		$mapinstance = ecp1_get_map_provider_instance();
		if ( ! is_null( $mapinstance ) &&
			( ( ! _ecp1_event_meta_is_default( 'ecp1_coord_lat' ) && ! _ecp1_event_meta_is_default( 'ecp1_coord_lng' ) ) || // has lat/lng
			( ! _ecp1_event_meta_is_default( 'ecp1_location' ) && $mapinstance->support_geocoding() ) ) ) { // or have address + geocoding
				// Render a placeholder and setup some init scripts
				$ecp1_element_id = 'ecp1_event_map';
				$ecp1_map_placeholder = '<div id="' . $ecp1_element_id . '">' . __( 'Loading map...' ) . '</div>';
				$ecp1_init_func_call = $mapinstance->get_onload_function();
				$ecp1_render_func_call = $mapinstance->get_maprender_function();
				$options_hash = array( 'element' => "'$ecp1_element_id'" );
				
				// Decide between location string and lat/lng
				$ecp1_lat = _ecp1_event_meta( 'ecp1_coord_lat' );
				$ecp1_lng = _ecp1_event_meta( 'ecp1_coord_lng' );
				if ( is_numeric( $ecp1_lat ) && is_numeric( $ecp1_lng ) ) {
					$options_hash['lat'] = $ecp1_lat;
					$options_hash['lng'] = $ecp1_lng;
				} else {
					$options_hash['location'] = "'$ecp1_location'";
				}

				// Do we want placemarks? and if so default or a url?
				if ( 'Y' == _ecp1_event_meta( 'ecp1_showmarker' ) ) {
					if ( _ecp1_event_meta_is_default( 'ecp1_map_placemarker' ) ||
						! file_exists( ECP1_DIR . '/img/mapicons/' . _ecp1_event_meta( 'ecp1_map_placemarker' ) ) )
						$options_hash['mark'] = 'true';
					else // file given and exists
						$options_hash['mark'] = sprintf( '"%s"', plugins_url( '/img/mapicons/' . _ecp1_event_meta( 'ecp1_map_placemarker' ), dirname( __FILE__ ) ) );
				} else {
					$options_hash['mark'] = 'false';
				}

				// Map zoom is simple
				$options_hash['zoom'] = _ecp1_event_meta( 'ecp1_map_zoom' );

				$options_hash_str = '{ ';
				foreach( $options_hash as $_k=>$_v )
					$options_hash_str .= sprintf( '%s:%s, ', $_k, $_v );
				$options_hash_str = trim( $options_hash_str, ',' ) . ' }';

				// Dynamic script to run on document ready
				$_ecp1_dynamic_event_script = <<<ENDOFSCRIPT
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	var container = jQuery( '#ecp1_event_map' );
	if ( container.length > 0 ) {
		var pWidth = container.parent().parent().width() - 150 - 90 - 25; // thumb - title - buffer
		var pHeight = pWidth * 0.65;
		container.css( { width:pWidth, height:pHeight } );
	}
	$ecp1_init_func_call( function() { $ecp1_render_func_call( $options_hash_str ); } );
} );
ENDOFSCRIPT;
		}
	}

	// String placeholder for the event information (i.e. URL or Internal Description)
	// Because we can do it here we'll support BOTH values but the onclick event for 
	// the calendar uses ONSITE description in preference (which means people will come
	// to this post page and then be able to offsite click).
	$ecp1_info = '';
	$ecp1_desc = _ecp1_event_meta_is_default( 'ecp1_description' ) ? null : ecp1_the_content( $event['ecp1_description'][0] );
	$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? null : urldecode( $event['ecp1_url'][0] );
	if ( ! is_null( $ecp1_desc ) && ! is_null( $ecp1_url ) ) {
		// Both given so render as description<br/>Read more...
		$ecp1_info = sprintf( '<div>%s</div><div><a href="%s" target="_blank">%s</a></div>', $ecp1_desc, $ecp1_url, __( 'Read more ...' ) );
	} elseif ( ! is_null( $ecp1_desc ) ) {
		// Only a description
		$ecp1_info = sprintf( '<div>%s</div>', $ecp1_desc );
	} elseif ( ! is_null( $ecp1_url ) ) {
		// Only a URL
		$ecp1_info = sprintf( '<div><a href="%s" target="_blank">%s</a></div>', $ecp1_url, __( 'Read more...' ) );
	} // else: leave as empty string summary must be enough
	
	// If feature images are enabled by the them (aka Post Thumbnails) then show if there is on
	$feature_image = '';
	if ( function_exists( 'add_theme_support' ) && function_exists( 'get_the_post_thumbnail' ) ) {
		if ( has_post_thumbnail( $post->ID ) )
			$feature_image = get_the_post_thumbnail( $post->ID, 'thumbnail' );
	}
	
	$outstr = <<<ENDOFHTML
<div id="ecp1_event">
	<span id="ecp1_feature">$feature_image</span>
	<ul class="ecp1_event-details">
		<li><span class="ecp1_event-title"><strong>$pwhen:</strong></span>
				<span class="ecp1_event-text">$ecp1_time</span></li>
		<li><span class="ecp1_event-title"><strong>$pwhere:</strong></span>
				<span class="ecp1_event-text">
					<span id="ecp1_event_location">$ecp1_location</span><br/>
					$ecp1_map_placeholder
				</span></li>
		<li><span class="ecp1_event-title"><strong>$psummary:</strong></span>
				<span class="ecp1_event-text_wide">$ecp1_summary</span></li>
		<li><span class="ecp1_event-title"><strong>$pdetails:</strong></span>
				<span class="ecp1_event-text_wide">$ecp1_info</span></li>
	</ul>
</div>
ENDOFHTML;

	return $outstr;
}

// Function to print the dynamic event load script 
function ecp1_print_event_load() {
	global $_ecp1_dynamic_event_script;
	if ( null != $_ecp1_dynamic_event_script ) {
		printf( '%s<!-- Every Calendar +1 Init -->%s<script type="text/javascript">/* <![CDATA[ */%s%s%s/* ]]> */</script>%s', "\n", "\n", "\n", $_ecp1_dynamic_event_script, "\n", "\n" );
	}
}

?>
