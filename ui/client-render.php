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

// Function that applies WordPress content filters to the given string
function ecp1_the_content( $content ) {
	// $c = apply_filters( 'the_content', $content );
	// We can't call the apply_filters directly because this function
	// is called from within a filter hooked to 'the_content' which
	// will create an infinite recursive loop and segfaults
	// TODO: Is there a better way to get these functions?
	$c = wptexturize( $content );
	$c = convert_smilies( $c );
	$c = convert_chars( $c );
	$c = wpautop( $c );
	$c = shortcode_unautop( $c );
	$c = prepend_attachment( $c );
	$c = str_replace(']]>', ']]&gt;', $c);
	return $c;
}

// Function that will return the necessary HTML blocks and queue some static
// JS for the document load event to render a FullCalendar instance
function ecp1_render_calendar( $calendar ) {
	global $_ecp1_dynamic_calendar_script;
	
	// Make sure the calendar provided is valid
	if ( ! is_array( $calendar ) )
		return sprintf( '<div id="ecp1_calendar" class="ecp1_error">%s</div>', __( 'Invalid calendar cannot display.' ) );
	
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
	$timezone = get_option( 'timezone_string' );	// Use the WordPress default if available
	// TODO: look at get_option( 'gmt_offset' );
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_timezone' ) ) {
		try {
			$dtz = new DateTimeZone( $calendar['ecp1_timezone'][0] );
			$offset = $dtz->getOffset( new DateTime( 'now' ) );
			$offset = 'UTC' . ( $offset < 0 ? ' - ' : ' + ' ) . ( abs( $offset/3600 ) );
			$ex = explode( '/', $dtz->getName() );
			$name = str_replace( '_', ' ', ( isset( $ex[2] ) ? $ex[2] : $ex[1] ) ); // Continent/Country/City
			$timezone = sprintf ( '%s (%s)', $name, $offset );
		} catch( Exception $tzmiss ) {
			// not a valid timezone
			$timezone = __( 'Unknown' );
		}
	} elseif ( $timezone == null ) {
		$timezone = 'UTC';
	}
	
	$default_view = 'month';	// How the calendar displays by default
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_default_view' ) &&
			in_array( $calendar['ecp1_default_view'][0], array( 'month', 'week', 'day' ) ) ) {
		$default_view = $calendar['ecp1_default_view'][0];
	}
	
	// If the calendar has an external URL and they're enabled use it
	// otherwise by default will request events on this particular calendar
	$events_url = '/todo/noevents-here.php'; // TODO: Make this specific to calendar
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_external_url' ) && _ecp1_get_option( 'use_external_cals' ) )
		$events_url = urldecode( $calendar['ecp1_external_url'][0] );
	
	// Register a hook to print the static JS to load FullCalendar on #ecp1_calendar
	add_action( 'wp_print_footer_scripts', 'ecp1_print_fullcalendar_load' );
	
	// Now build the actual JS that will be loaded
	// TODO: Add eventClick function
	// TODO: Event Colors + Featured Events Source
	// TODO: Add Event Sources
	// TODO: Add currentTimezone to events hash
	$_ecp1_dynamic_calendar_script = <<<ENDOFSCRIPT
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	$('#ecp1_calendar div.fullcal').empty().fullCalendar({
		header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
		timeFormat: { agenda: 'h:mmtt( - h:mmtt	)', '': 'h(:mm)tt' },
		firstDay: $first_day,
		weekends: true,
		defaultView: '$default_view',
		allDaySlot: false,
		events: {
			url: '$events_url'
		}
	});
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
	global $_ecp1_dynamic_event_script;
	
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
	$ecp1_time = 'Unknown';
	if ( ! _ecp1_event_meta_is_default( 'ecp1_start_ts' ) || ! _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ) {
		// Use the default WordPress dateformat timeformat strings
		$datef = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		// If this is an all day event and the time component is start=00:01 end=23:59 
		// Then there is no useful information in the time fields so don't display them
		if ( 'Y' == $event['ecp1_full_day'][0] && '0001' == date( 'Hi', $event['ecp1_start_ts'][0] ) && '2359' == date( 'Hi', $event['ecp1_end_ts'][0] ) )
			$datef = get_option( 'date_format' );
		
		// If the dates are the same and full day just say that
		if ( 'Y' == $event['ecp1_full_day'][0] && date( 'Ymj', $event['ecp1_start_ts'][0] ) == date( 'Ymj', $event['ecp1_end_ts' ][0] ) )
			$ecp1_time = sprintf( '%s %s', date( $datef, $event['ecp1_start_ts'][0] ), __( '(all day)' ) );
		else 
			$ecp1_time = sprintf( '%s - %s %s', date( $datef, $event['ecp1_start_ts'][0] ), 
						date( $datef, $event['ecp1_end_ts'][0] ), 'Y' == $event['ecp1_full_day'][0] ? __( '(all day)' ) : '' );
	}
	
	// String placeholder for the summary text
	$ecp1_summary = $event['ecp1_summary'][1];
	if ( ! _ecp1_event_meta_is_default( 'ecp1_summary' ) )
		$ecp1_summary = wp_filter_post_kses( $event['ecp1_summary'][0] );
	
	// String placeholders for the location and map coords if enabled
	$ecp1_location = $event['ecp1_location'][1];
	if ( ! _ecp1_event_meta_is_default( 'ecp1_location' ) ) {
		$ecp1_location = htmlspecialchars( $event['ecp1_location'][0] );
		$ecp1_map_placeholder = '';
		if ( _ecp1_get_option( 'use_maps' ) ) {
			$ecp1_map_placeholder = '<div id="ecp1_event_map">Loading map...</div>';
			$_ecp1_dynamic_event_script = <<<ENDOFSCRIPT
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	$('#ecp1_event #ecp1_event_map').empty().append('<div>DYNAMIC</div>');
});
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
	
	$outstr = <<<ENDOFHTML
<div id="ecp1_event">
	<ul class="ecp1_event-details">
		<li><span class="ecp1_event-title"><strong>$pwhen:</strong></span>
				<span class="ecp1_event-text">$ecp1_time</span></li>
		<li><span class="ecp1_event-title"><strong>$pwhere:</strong></span>
				<span class="ecp1_event-text"><div id="ecp1_event_location">$ecp1_location</div>$ecp1_map_placeholder</span></li>
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
