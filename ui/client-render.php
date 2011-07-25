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
	
	// Extract the calendar fields or go to defaults

	$first_day = get_option( 'start_of_week ' );	// 0=Sunday 6=Saturday (uses WordPress)
	if ( array_key_exists( 'ecp1_first_day', $calendar ) && is_numeric( $calendar['ecp1_first_day'] ) &&
			( 0 <= $calendar['ecp1_first_day'] && $calendar['ecp1_first_day'] <= 6 ) ) {
		$first_day = $calendar['ecp1_first_day'];
	}
	
	$description = '';	// Text based description make sure it's escaped
	if ( array_key_exists( 'ecp1_description', $calendar ) ) {
		$description = wp_filter_post_kses( $calendar['ecp1_description'] );
	}
	
	$timezone = get_option( 'timezone_string' );	// Timezone events in this calendar occur in
	if ( array_key_exists( 'ecp1_timezone', $calendar ) ) {
		// Only use the name if NOT WordPress Default
		if ( '_' != $calendar['ecp1_timezone'] ) {
			try {
				$dtz = new DateTimeZone( $calendar['ecp1_timezone'] );
				$offset = $dtz->getOffset( new DateTime( 'now' ) );
				$offset = 'UTC' . ( $offset < 0 ? ' - ' : ' + ' ) . ( abs( $offset/3600 ) );
				$ex = explode( '/', $dtz->getName() );
				$name = str_replace( '_', ' ', ( isset( $ex[2] ) ? $ex[2] : $ex[1] ) ); // Continent/Country/City
				$timezone = sprintf ( '%s (%s)', $name, $offset );
			} catch( Exception $tzmiss ) {
				// not a valid timezone
				$timezone = __( 'Unknown' );
			}
		}
	} elseif ( $timezone == null ) {
		$timezone = 'UTC';
	}
	
	$default_view = 'month';	// How the calendar displays by default
	if ( array_key_exists( 'ecp1_default_view', $calendar ) &&
			in_array( $calendar['ecp1_default_view'], array( 'month', 'week', 'day' ) ) ) {
		$default_view = $calendar['ecp1_default_view'];
	}
	
	// If the calendar has an external URL and they're enabled use it
	// otherwise by default will request events on this particular calendar
	$events_url = '/todo/noevents-here.php'; // TODO: Make this specific to calendar
	if ( array_key_exists( 'ecp1_external_url', $calendar ) && _ecp1_get_option( 'use_external_cals' ) ) {
		if ( '' != $calendar['ecp1_external_url'] ) { // a url has been given
			$events_url = urldecode( $calendar['ecp1_external_url'] );
		}
	}
	
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
		printf( '%s<!-- Every Calendar +1 Init -->%s<script type="text/javascript">%s%s%s</script>%s', "\n", "\n", "\n", $_ecp1_dynamic_calendar_script, "\n", "\n" );
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
	
	// Extract the event fields or go to defaults
	// $p variables are placeholders for the i18n titles
	$pwhen = __( 'When' );
	$pwhere = __( 'Where' );
	$psummary = __( 'Quick Info' );
	$pdetail = __( 'Details' );
	
	// String placeholder for the time this event runs for
	$ecp1_time = 'start - end';
	
	// String placeholder for the summary text
	$ecp1_summary = isset( $event['ecp1_summary'] ) ? $event['ecp1_summary'] : '';
	
	// String placeholders for the location and map coords if enabled
	$ecp1_location = isset( $event['ecp1_location'] ) ? $event['ecp1_location'] : '';
	$ecp1_map_placeholder = '';
	if ( _ecp1_get_option('use_maps') ) {
		$ecp1_map_placeholder = '<div id="ecp1_event_map">Loading map...</div>';
		$_ecp1_dynamic_event_script = <<<ENDOFSCRIPT
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	$('#ecp1_event #ecp1_event_map').append('<div>DYNAMIC</div>');
});
ENDOFSCRIPT;
	}

	// String placeholder for the event information (i.e. URL or Internal Description)
	// Because we can do it here we'll support BOTH values but the onclick event for 
	// the calendar uses ONSITE description in preference (which means people will come
	// to this post page and then be able to offsite click).
	$ecp1_info = '';
	$ecp1_desc = isset( $event['ecp1_description'] ) && '' != $event['ecp1_description'] ? wp_filter_post_kses( $event['ecp1_description'] ) : null;
	$ecp1_url = isset( $event['ecp1_url'] ) && '' != $event['ecp1_description'] ? urldecode( $event['ecp1_url'] ) : null;
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
		<li><span class="ecp1_event-title"><strong>$pwhen:</strong></span><span class="ecp1_event-text">$ecp1_time</span></li>
		<li><span class="ecp1_event-title"><strong>$pwhere:</strong></span>
			<span class="ecp1_event-text">
				<div id="ecp1_event_location">$ecp1_location</div>
				$ecp1_map_placeholder
			</span></li>
		<li><span class="ecp1_event-title"><strong>$psummary:</strong></span><span class="ecp1_event-text_wide">$ecp1_summary</span></li>
		<li><span class="ecp1_event-title"><strong>$pdetails:</strong></span><span class="ecp1_event-text_wide">$ecp1_info</span></li>
	</ul>
</div>
ENDOFHTML;

	return $outstr;
}

// Function to print the dynamic event load script 
function ecp1_print_event_load() {
	global $_ecp1_dynamic_event_script;
	if ( null != $_ecp1_dynamic_event_script ) {
		printf( '%s<!-- Every Calendar +1 Init -->%s<script type="text/javascript">%s%s%s</script>%s', "\n", "\n", "\n", $_ecp1_dynamic_event_script, "\n", "\n" );
	}
}

?>