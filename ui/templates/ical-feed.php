<?php
/**
 * File that loads a list of events based on parameters and then
 * returns a JSON data set in FullCalendar format of those events.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// We need the global WP_Query and WPDB object in scope
// BEFORE load any of the helper functions and queries
global $wp_query, $wpdb;

// Load the helper functions
require_once( ECP1_DIR . '/functions.php' );

// We need the Every Calendar settings
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// We need to know about the event post type meta/custom fields
require_once( ECP1_DIR . '/includes/data/event-fields.php' );

// Load the DRY query list so we can use them
require_once( ECP1_DIR . '/ui/templates/_querylist.php' );

// WordPress will only pass ecp1_cal as a query_var if it passes
// the registered regex (letters, numbers, _ and -) this is loosely
// consistent with slugs as in sanitize_title_with_dashes but will
// break if someone changes the calendar slug manually
$cal = $wp_query->query_vars['ecp1_cal'];

// Reset the default WordPress query just in case
wp_reset_query();

if ( ! empty( $wp_query->query_vars[ECP1_TEMPLATE_TEST_ARG] ) &&
		'1' == $wp_query->query_vars[ECP1_TEMPLATE_TEST_ARG] ) {
	header( 'Content-Type: text/plain' );
} else {
	header( 'Content-Type: text/calendar' );
	header( 'Content-Disposition: attachement; filename="events.ics"' );
}

// Lookup the calendar post
$cal = get_page_by_path( $cal, OBJECT, 'ecp1_calendar' );
if ( is_null( $cal ) ) {
	_ecp1_template_error( __( 'No such calendar.' ), 404, __( 'Calendar Not Found' ) );
} else {

	_ecp1_parse_calendar_custom( $cal->ID ); // Get the calendar meta data
	$tz = ecp1_get_calendar_timezone();      // and the effective timezone
	$dtz = new DateTimeZone( $tz );
	$ex_cals = _ecp1_calendar_meta( 'ecp1_external_cals' ); // before loop
	$my_id = $cal->ID; // because event meta reparses its calendars meta

?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php echo get_option( 'blogname' ) . ' - ' . $cal->post_name; ?>//NONSGML EveryCal+1 Events//EN
METHOD:PUBLISH
X-WR-CALNAME:<?php echo $cal->post_title . "\n"; ?>
X-WR-CALDESC:<?php echo _ecp1_calendar_meta( 'ecp1_description' ) . "\n"; ?>
X-WR-TIMEZONE:<?php echo $dtz->getName() . "\n"; ?>
X-ORIGINAL-URL:<?php echo get_permalink( $cal->ID ) . "\n"; ?>
CALSCALE:GREGORIAN
<?php

	// Get the max before / max after ranges from the plugin settings
	// and use them as timestamps for the event lookup queries
	$start = time() - abs( _ecp1_get_option( 'ical_export_start_offset' ) );
	$end   = time() + abs( _ecp1_get_option( 'ical_export_end_offset' ) );

	// Get the ecp1_events that match the range
	// The parameters are at UTC and so are dates in database => no converting needed
	// Note: Using query_posts is supported here as this is meant to be the main loop
	// Note: The SQL has start comparission BEFORE end comparisson $end before $start
	// ROADMAP: Repeating events - probably will need to abstract this
	$event_ids = $wpdb->get_col( $wpdb->prepare( $$ECP1_QUERY['EVENTS'], $cal->ID, $end, $start ) );
			
	// Now look to see if this calendar supports featured events and if so load ids
	$feature_ids = array();
	if ( _ecp1_calendar_show_featured( $cal->ID ) )
		$feature_ids = $wpdb->get_col( $wpdb->prepare( $$ECP1_QUERY['FEATURED_EVENTS'], $end, $start ) );
	$event_ids = array_merge( $event_ids, $feature_ids );

	// If any events were found load them into the loop
	if ( count( $event_ids ) > 0 )
		query_posts( array( 'post__in' => $event_ids, 'post_type'=>'ecp1_event' ) );

	// Equiv of The Loop
	while ( have_posts() ) : the_post();
		_ecp1_parse_event_custom(); // load event meta

		// Check the custom fields make sense
		if ( _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ||
				_ecp1_event_meta_is_default( 'ecp1_end_ts' ) )
			continue; // need a start and finish so skip to next post

		try {
			$e  = _ecp1_event_meta( 'ecp1_start_ts', false );
			$es = new DateTime( "@$e" ); // requires PHP 5.2.0
			$e  = _ecp1_event_meta( 'ecp1_end_ts', false );
			$ee = new DateTime( "@$e" ); // 5.2.0 again
			$format = 'Ymd\THis\Z';

			// If this is a feature event (not from this calendar) then change the
			// start/end times to be event local not calendar local if setting = 1
			if ( _ecp1_event_meta( 'ecp1_calendar' ) != $cal->ID && in_array( get_the_ID(), $feature_ids ) &&
					// Base feature events at local calendar timezone or event local timezone?
					'1' == _ecp1_get_option( 'base_featured_local_to_event' ) ) {
				// Offset the start and end times by the event calendar offset
				$tz = ecp1_get_calendar_timezone(); // calendar is updated on _ecp1_parse_event_custom()
				$localdtz = new DateTimeZone( $tz );
				$e = _ecp1_event_meta( 'ecp1_start_ts', false ) + $localdtz->getOffset( new DateTime() );
				$es = new DateTime( "@$e" ); // requires PHP 5.2.0
				$e = _ecp1_event_meta( 'ecp1_end_ts', false ) + $localdtz->getOffset( new DateTime() );
				$ee = new DateTime( "@$e" ); // 5.2.0 again
				$format = 'Ymd\THis'; // local time not Z(ulu) UTC GMT
			}

			// The start and end times are expecting YYYYMMDDTHHMMSS w/ optional Z
			$estart = $es->format( $format );
			$eend   = $ee->format( $format );

			// The summary and location can be verbatim
			$elocation = _ecp1_event_meta( 'ecp1_location' );

			// Now for the tricky part: description needs to have URL and/or local
			// description text depending on what was set in the admin and the sumary
			// should be prefixed in either case
			$edescription = sprintf( "%s", _ecp1_event_meta( 'ecp1_summary' ) );
			$ecp1_desc = _ecp1_event_meta_is_default( 'ecp1_description' ) ? null : strip_tags( _ecp1_event_meta( 'ecp1_description' ) );
			$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? null : urldecode( _ecp1_event_meta( 'ecp1_url' ) );
			if ( ! is_null( $ecp1_desc ) || ! is_null( $ecp1_url ) )
				$edescription .= "\\n------------------";
			if ( ! is_null( $ecp1_url ) )
				$edescription .= sprintf( "\\n%s", $ecp1_url );
			else
				$edescription .= sprintf( "\\n%s", get_permalink( _ecp1_event_meta_id() ) );
			if ( ! is_null( $ecp1_desc ) )
				$edescription .= sprintf( "\\n%s", $ecp1_desc );
			$edescription = str_replace( array( "\r\n", "\r", "\n" ), array( "\\n", "\\n", "\\n" ), $edescription );

// Output this event in iCal
?>
BEGIN:VEVENT
DTSTART:<?php echo $estart . "\n"; ?>
DTEND:<?php echo $eend . "\n"; ?>
SUMMARY:<?php echo the_title() . "\n"; ?>
LOCATION:<?php echo $elocation . "\n"; ?>
DESCRIPTION:<?php echo $edescription . "\n"; ?>
END:VEVENT
<?php
		} catch( Exception $datex ) {
			continue; // ignore bad timestamps they shouldn't happen
		}
	endwhile;

	// If external calendar providers should be syndicated then send them too
	if ( '1' == _ecp1_get_option( 'ical_export_include_external' ) ) {

		// Loop over this calendars external calendars
		foreach( $ex_cals as $ex_cal ) {
			$calprov = ecp1_get_calendar_provider_instance( $ex_cal['provider'], $my_id, urldecode( $ex_cal['url'] ) );
			if ( null == $calprov )
				continue; // failed to load
			$continue = true;
			if ( $calprov->cache_expired( _ecp1_get_option( 'ical_export_external_cache_life' ) ) )
				$continue = $calprov->fetch( $start, $end, $dtz );

			if ( $continue ) { // fetched or not but is ok
				$evs = $calprov->get_events();
				foreach( $evs as $eventid=>$event ) {

					try {
						$e  = $event['start'];
						$es = new DateTime( "@$e" ); // requires PHP 5.2.0
						$e  = $event['end'];
						$ee = new DateTime( "@$e" ); // 5.2.0 again

						// The start and end times are expecting YYYYMMDDTHHMMSSZ
						$estart = $es->format( 'Ymd\THis\Z' );
						$eend   = $ee->format( 'Ymd\THis\Z' );

						// The summary and location can be verbatim
						$etitle = $event['title'];
						$elocation = $event['location'];

						// Now for the tricky part: description needs to have URL and/or local
						// description text depending on what was set in the admin and the sumary
						// should be prefixed in either case
						$edescription = '';
						$ecp1_summary = is_null( $event['summary'] ) ? null : strip_tags( $event['summary'] );
						$ecp1_desc = is_null( $event['description'] ) ? null : strip_tags( $event['description'] );
						$ecp1_url = is_null( $event['url'] ) ? null : urldecode( $event['url'] );
						if ( ! is_null( $ecp1_summary ) && ( ! is_null( $ecp1_desc ) || ! is_null( $ecp1_url ) ) )
							$edescription .= sprintf( "%s\\n------------------", $ecp1_summary );
						if ( ! is_null( $ecp1_desc ) )
							$edescription .= sprintf( "\\n%s", $ecp1_desc );
						if ( ! is_null( $ecp1_url ) )
							$edescription .= sprintf( "\\n%s", $ecp1_url );
						$edescription = str_replace( array( "\r\n", "\r", "\n" ), array( "\\n", "\\n", "\\n" ), $edescription );

// Output the external cached calendars
?>
BEGIN:VEVENT
DTSTART:<?php echo $estart . "\n"; ?>
DTEND:<?php echo $eend . "\n"; ?>
SUMMARY:<?php echo $etitle . "\n"; ?>
LOCATION:<?php echo $elocation . "\n"; ?>
DESCRIPTION:<?php echo $edescription . "\n"; ?>
END:VEVENT
<?php
					} catch( Exception $datex ) {
						continue; // ignore bad timestamps they shouldn't happen
					}

				} // foreach event
			} // events found
		} // foreach external calendar
	} // include externals

	// Reset the query now the loop is done
	wp_reset_query();
?>
END:VCALENDAR
<?php

} // calendar was found

?>
