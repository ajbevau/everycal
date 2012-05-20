<?php
/**
 * Registers a shortcode for a list of events as the calendar
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Load the calendar and events post type fields so we can get the meta
require_once( ECP1_DIR . '/includes/data/calendar-fields.php' );
require_once( ECP1_DIR . '/includes/data/event-fields.php' );

// Load the DRY query list so we can use them
require_once( ECP1_DIR . '/ui/templates/_querylist.php' );

// Register the shortcode type and callback
add_shortcode( 'eventlist', 'ecp1_event_list_calendar' );

// Placeholder for any dynamic script this calendar will show
$_ecp1_event_list_calendar_script = null;

// [ eventlist name="calendar name" starting="date to start from" until="end date of list" ]
// Defaults:	starting = now
//		until = 1st Jan 2038 - close enough to overflow
function ecp1_event_list_calendar( $atts ) {
	global $_ecp1_event_list_calendar_script;

	// Register a hook to print the static JS to load FullCalendar on #ecp1_calendar
	add_action( 'wp_print_footer_scripts', 'ecp1_print_eventlist_load' );

	// Extract the attributes or assign default values
	extract( shortcode_atts( array(
		'name' => null, # checked below must not be null
		'starting' => time(), # default to now
		'until' => 2145916800, # 1st Jan 2038 at midnight UTC
	), $atts ) );
	
	// Make sure a name has been provided
	if ( is_null( $name ) )
		return sprintf( '<span class="ecp1_error">%s</span>', __( 'Unknown calendar: could not display.' ) );

	// Try and parse the starting date time string
	if ( ! is_numeric( $starting ) ) {
		$test = strtotime( $starting );
		if ( false !== $test ) // PHP 5.1.0
			$starting = $test;
		else
			return sprintf( '<span class="ecp1_error">%s</span>', __( 'Could not parse event list start time.' ) );
	}

	// Try and parse the until date time if given
	if ( ! is_numeric( $until ) ){
		$test = strtotime( $until );
		if ( false !== $test ) // PHP 5.1.0
			$until = $test;
		else
			return sprintf( '<span class="ecp1_error">%s</span>', __( 'Could not parse event list end time.' ) );
	}
	
	// Lookup the Post ID for the calendar with that name
	// Note: Pages are just Posts with post_type=page so the built in function works
	$cal_post = get_page_by_title( $name, OBJECT, 'ecp1_calendar' );
	_ecp1_parse_calendar_custom( $cal_post->ID );
	$raw_timezone = ecp1_get_calendar_timezone();
	$timezone = ecp1_timezone_display( $raw_timezone );

	// Lookup the events for the calendar and then render in a nice template
	$outstring = '<ol>';
	$events = _ecp1_event_list_get( $cal_post->ID, $starting, $until );
	_ecp1_parse_calendar_custom( $cal_post->ID );
	$feature_text = _ecp1_calendar_meta( 'ecp1_feature_event_textcolor' );
	$feature_back = _ecp1_calendar_meta( 'ecp1_feature_event_color' );
	foreach( $events as $event ) {
		// Featured events have their UTC times modified to be local to calendar TZ
		$ewhen = ecp1_formatted_date_range( $event['start'], $event['end'], $event['allday'],
				( $event['feature'] ? 'UTC' : $raw_timezone ) );
		
		$stylestring = '';
		if ( $event['feature'] )
			$stylestring = ' style="display:block;background:' . $feature_back . ';color:' . $feature_text . ';"';
		if ( $event['custom_colors'] )
			$stylestring = ' style="display:block;background:' . $event['bg_color'] . ';color:' . $event['text_color'] . ';"';

		$outstring .= sprintf('
<li class="ecp1_event">
	<span class="ecp1_feature">%s</span>
	<ul class="ecp1_event-details">
		<li><span style="display:block;"><a %s href="%s"><strong>%s</strong></a></span></li>
		<li><span class="ecp1_event-title"><strong>%s:</strong></span>
				<span class="ecp1_event-text">%s</span></li>
		<li><span class="ecp1_event-title"><strong>%s:</strong></span>
				<span class="ecp1_event-text">
					<span id="ecp1_event_location">%s</span>
				</span></li>
		<li><span class="ecp1_event-title"><strong>%s:</strong></span>
				<span class="ecp1_event-text_wide">%s</span></li>
	</ul>
</li>',
				$event['image'], $stylestring,
				urldecode( $event['url'] ), htmlentities( $event['title'] ),
				__( 'When' ), $ewhen,
				__( 'Where' ), $event['location'],
				__( 'Summary' ), $event['summary'] );
	}
	$outstring .= '</ol>';

	// Now return HTML
	$rss_addr = 'TODO:RSS ADDR'; // TODO
	$ical_addr = get_site_url() . '/ecp1/' . urlencode( $cal_post->post_name ) . '/events.ics';
	$icalfeed = sprintf( '<a href="%s" title="%s"><img src="%s" alt="ICAL" /></a>',
				$ical_addr, __( 'Subscribe to Calendar Feed' ),
				plugins_url( '/img/famfamfam/date.png', dirname( dirname( __FILE__ ) ) ) );
	$_close_feed_popup = htmlspecialchars( __( 'Back to Event List' ) ); // strings for i18n
	$_feed_addrs = array(
		__( 'iCal / ICS' ) => $ical_addr,
		__( 'Outlook WebCal' ) => preg_replace( '/http[s]?:\/\//', 'webcal://', $ical_addr ),
		__( 'RSS' ) => $rss_addr,
	);
	$_feed_addrs_js = '{';
	foreach( $_feed_addrs as $title=>$link )
		$_feed_addrs_js .= sprintf( "'%s':'%s',", htmlspecialchars( $title ), $link );
	$_feed_addrs_js = trim( $_feed_addrs_js, ',' ) . '}';

	$_ecp1_event_list_calendar_script .= <<<ENDOFSCRIPT
var _feedLinks = $_feed_addrs_js;
jQuery(document).ready(function($) {
	// $() will work as an alias for jQuery() inside of this function
	$('#ecp1_calendar_list div.feeds a').click(function() {
		var popup = $( '<div></div>' )
				.attr( { id:'_ecp1-feed-popup' } ).css( { display:'none', 'z-index':9999 } );
		var pw = $( window ).width();
		var ph = $( document ).height();
		var ps = $( document ).scrollTop(); ps = ( ps+175 ) + 'px auto 0 auto';

		var fL = $( '<ul></ul>' );
		for ( key in _feedLinks ) {
			fL.append( $( '<li></li>' )
					.append( $( '<span></span>' )
						.text( key ) )
					.append( $( '<a></a>' )
						.attr( { href:_feedLinks[key], title:key } )
						.text( _feedLinks[key] ) ) );
		}

		popup.css( { width:pw, height:ph, display:'block' } )
			.append( $( '<div></div>' )
				.addClass( 'inner' )
				.css( { background:'#ffffff', padding:'1em', width:800, height:200, margin:ps } )
				.append( jQuery( '<div></div>' )
					.css( { textAlign:'right' } )
					.append( jQuery( '<a></a>' )
						.css( { cursor:'pointer' } )
						.text( '$_close_feed_popup' )
						.click( function( event ) {
							event.stopPropagation();
							jQuery( '#_ecp1-feed-popup' ).remove();
						} ) ) )
				.append( jQuery( '<div></div>' )
					.css( { textAlign:'left', width:800, height:150, paddingTop:25 } )
					.append( fL ) ) );

		$('body').append(popup);
		return false;
	} );
} );

ENDOFSCRIPT;
	$feeds = '<div class="feeds">' . $icalfeed . '</div>';
	// Text based description make sure it's escaped
	$description = '';
	if ( ! _ecp1_calendar_meta_is_default( 'ecp1_description' ) )
		$description = wp_filter_post_kses( _ecp1_calendar_meta( 'ecp1_description' ) );
	$description = '' != $description ? '<p><strong>' . $description . '</strong></p>' : '';
	$feature_msg = '';
	if ( _ecp1_calendar_show_featured( _ecp1_calendar_meta_id() ) &&
			'1' == _ecp1_get_option( 'base_featured_local_to_event' ) ) {
		// calendar shows feature events and feature events are shown in their
		// location local timezone -> show the note so people know different
		$feature_msg = sprintf( '<div style="padding:0 5px;color:%s;background-color:%s"><em>%s</em></div>',
				_ecp1_calendar_meta( 'ecp1_feature_event_textcolor' ),
				_ecp1_calendar_meta( 'ecp1_feature_event_color' ),
				htmlspecialchars( _ecp1_get_option( 'base_featured_local_note' ) ) );
	}

	$timezone = sprintf( '<div><div style="padding:0 5px;"><em>%s</em></div>%s</div>',
			sprintf( __( 'Events occur at %s local time.' ), $timezone ), $feature_msg );
	return sprintf( '<div id="ecp1_calendar_list">%s%s<div class="fullcal">%s</div>%s</div>', $feeds, $description, $outstring, $timezone );
}



// Looks up the event list and sorts it then returns an array
// ROADMAP: Use this function to render an RSS feed of events for the calendar
function _ecp1_event_list_get( $cal, $starting, $until ) {
	global $wpdb;

	// We don't want calendar meta for the global page/post but for the one given by title
	_ecp1_parse_calendar_custom( $cal ); // Load the calendar meta into global $ecp1_calendar_fields

	// Timezone and external calendar sources
	$tz = ecp1_get_calendar_timezone();   // the effective timezone
	$dtz = new DateTimeZone( $tz );
	$ex_cals = _ecp1_calendar_meta( 'ecp1_external_cals' ); // before loop
	$my_id = $cal; // because event meta reparses its calendars meta

	// Get the ecp1_events that match the range
	// The parameters are at UTC and so are dates in database => no converting needed
	// Note: THIS LOOKS THE SAME AS ical-feed.php BUT NOT IN THE LOOP
	// Note: The SQL has start comparission BEFORE end comparisson $until before $starting
	// ROADMAP: Repeating events - probably will need to abstract this
	$event_ids = $wpdb->get_col( $wpdb->prepare( _ecp1_tq( 'EVENTS' ), $my_id, $until, $starting ) );
	$event_cache = array();

	// Now look to see if this calendar supports featured events and if so load ids
	$feature_ids = array();
	if ( _ecp1_calendar_show_featured( $my_id ) )
		$feature_ids = $wpdb->get_col( $wpdb->prepare( _ecp1_tq( 'FEATURED_EVENTS' ), $until, $starting ) );
	$event_ids = array_merge( $event_ids, $feature_ids );

	// If any events were found load them into the array for render
	$event_posts = array();
	if ( count( $event_ids ) > 0 )
		$event_posts = get_posts( 
			array( 'post__in' => $event_ids, 'post_type'=>'ecp1_event',
				'numberposts' => -1, 'suppress_filters' => false )
		);

	// Loop through the events and setup the event in the cache
	foreach( $event_posts as $epost ) {
		_ecp1_parse_event_custom( $epost->ID );
		
		// Check the custom fields make sense
		if ( _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ||
				_ecp1_event_meta_is_default( 'ecp1_end_ts' ) )
			continue; // need a start and finish so skip to next post

		try {
			$e  = _ecp1_event_meta( 'ecp1_start_ts', false );
			$es = new DateTime( "@$e" ); // requires PHP 5.2.0
			$e  = _ecp1_event_meta( 'ecp1_end_ts', false );
			$ee = new DateTime( "@$e" ); // 5.2.0 again

			// If this is a feature event (not from this calendar) then change the
			// start/end times to be event local not calendar local if setting = 1
			if ( _ecp1_event_meta( 'ecp1_calendar' ) != $my_id && in_array( $epost->ID, $feature_ids ) &&
					// Base feature events at local calendar timezone or event local timezone?
					'1' == _ecp1_get_option( 'base_featured_local_to_event' ) ) {
				// Offset the start and end times by the event calendar offset
				$tz = ecp1_get_calendar_timezone(); // calendar is updated on _ecp1_parse_event_custom()
				$localdtz = new DateTimeZone( $tz );
				$e = _ecp1_event_meta( 'ecp1_start_ts', false ) + $localdtz->getOffset( new DateTime() );
				$es = new DateTime( "@$e" ); // requires PHP 5.2.0
				$e = _ecp1_event_meta( 'ecp1_end_ts', false ) + $localdtz->getOffset( new DateTime() );
				$ee = new DateTime( "@$e" ); // 5.2.0 again
			}

			// The start and end times
			$estart  = $es->format( 'U' );
			$eend    = $ee->format( 'U' );
			$eallday = _ecp1_event_meta( 'ecp1_full_day' );

			// Description and permalink/external url
			$efeature = ( _ecp1_event_meta( 'ecp1_calendar' ) != $my_id && in_array( $epost->ID, $feature_ids ) );
			$ecp1_desc = _ecp1_event_meta_is_default( 'ecp1_description' ) ? '' : strip_tags( _ecp1_event_meta( 'ecp1_description' ) );
			$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? get_permalink( $epost->ID ) : urldecode( _ecp1_event_meta( 'ecp1_url' ) );

			// If feature images are enabled by the them (aka Post Thumbnails) then show if there is one
			$feature_image = false;
			if ( function_exists( 'add_theme_support' ) && function_exists( 'get_the_post_thumbnail' ) ) {
				if ( has_post_thumbnail( $epost->ID ) )
					$feature_image = get_the_post_thumbnail( $epost->ID, 'thumbnail' );
			}
			
			// Are we overwriting the calendar colors with this event?
			$bg_color = '';
			$text_color = '';
			$overwrite_colors = 'Y' == _ecp1_event_meta( 'ecp1_overwrite_color' );
			if ( $overwrite_colors ) {
				$bg_color = _ecp1_event_meta( 'ecp1_local_color' );
				$text_color = _ecp1_event_meta( 'ecp1_local_textcolor' );
			}

			// Setup the event cache
			$event_cache[] = array(
				'start' => $estart, 'end' => $eend, 'allday' => $eallday,
				'feature' => $efeature, 'image' => $feature_image,
				'custom_colors' => $overwrite_colors, 'bg_color' => $bg_color, 'text_color' => $text_color,
				'title' => $epost->post_title, 'location' => _ecp1_event_meta( 'ecp1_location' ),
				'summary' => _ecp1_event_meta( 'ecp1_summary' ), 'description' => $ecp1_desc, 'url' => $ecp1_url );
		} catch( Exception $e ) {
			continue; // ignore bad timestamps they shouldn't happen
		}
	}

	// Lookup any external calendars and their events
	foreach( $ex_cals as $ex_cal ) {
		$calprov = ecp1_get_calendar_provider_instance( $ex_cal['provider'], $my_id, urldecode( $ex_cal['url'] ) );
		if ( null == $calprov )
			continue; // failed to load
		$continue = true;
		if ( $calprov->cache_expired( _ecp1_get_option( 'ical_export_external_cache_life' ) ) )
			$continue = $calprov->fetch( $start, $end, $dtz );

		if ( $continue ) { // fetched or not but is ok
			$evs = $calprov->get_events( $starting, $until );
			foreach( $evs as $eventid=>$event ) {
				try {
					$e  = $event['start'];
					$estart = new DateTime( "@$e" ); // requires PHP 5.2.0
					$e  = $event['end'];
					$eend = new DateTime( "@$e" ); // 5.2.0 again

					// Create this event in the cache
					$event_cache[] = array(
						'start' => $estart->format( 'U' ), 'end' => $eend->format( 'U' ), 'allday' => $event['all_day'],
						'title' => $event['title'], 'location' => $event['location'], 'summary' => $event['summary'],
						'description' => $event['description'], 'url' => $event['url'], 'feature' => false, 'image' => false );
				} catch( Exception $e ) {
					continue; // ignore bad timestamps they shouldn't happen
				}
			}
		}
	}

	// Sort the event_cache array by start date
	usort( $event_cache, '_ecp1_event_list_compare' );

	// Return the finalised array
	return $event_cache;
}

// Comparisson function for event_cache array entries
function _ecp1_event_list_compare( $a, $b ) {
	if ( ! array_key_exists( 'start', $a ) ) return 1;
	if ( ! array_key_exists( 'start', $b ) ) return -1;
	return ( $a['start'] < $b['start'] || ( $a['start'] == $b['start'] && $a['end'] < $b['end'] ) ) ? -1 : 1;
}

// Function to print the dynamic calendar load script
function ecp1_print_eventlist_load() {
	global $_ecp1_event_list_calendar_script;
	if ( null != $_ecp1_event_list_calendar_script ) {
		printf( '%s<!-- Every Calendar +1 Init -->%s<script type="text/javascript">/* <![CDATA[ */%s%s%s/* ]]> */</script>%s', "\n", "\n", "\n", $_ecp1_event_list_calendar_script, "\n", "\n" );
	}
}


?>
