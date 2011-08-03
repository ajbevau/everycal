<?php
/**
 * File that loads a list of events based on parameters and then
 * returns a JSON data set in FullCalendar format of those events.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// We need the Every Calendar settings
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// We need to know about the event post type meta/custom fields
require_once( ECP1_DIR . '/includes/data/event-fields.php' );

// We need the global WP_Query and WPDB object
global $wp_query, $wpdb;

// A shortcut function for erroring out as plaintext
function _ecp1_template_event_json_error( $msg=null, $http_code=200, $http_msg='Every Calendar +1 Plugin Error' ) {
	if ( ! is_null( $msg ) ) {
		header( 'Content-Type:text/html' );
		header( sprintf( 'HTTP/1.1 %s %s', $http_code, $http_msg ), 1 );
		header( sprintf( 'Status: %s %s', $http_code, $http_msg ), 1 );
		printf( '<!DOCTYPE html><html><body><p>%s</p></body></html>', $msg );
	}
}

// WordPress will only pass ecp1_cal as a query_var if it passes
// the registered regex (letters, numbers, _ and -) this is loosely
// consistent with slugs as in sanitize_title_with_dashes but will
// break if someone changes the calendar slug manually

// Define a query to get event post ids
$_events_query = <<<ENDOFQUERY
SELECT	p.ID
FROM	$wpdb->posts p
	INNER JOIN $wpdb->postmeta c ON c.post_id=p.ID AND c.meta_key='ecp1_event_calendar'
	INNER JOIN $wpdb->postmeta s ON s.post_id=p.ID AND s.meta_key='ecp1_event_start'
	INNER JOIN $wpdb->postmeta e ON e.post_id=p.ID AND e.meta_key='ecp1_event_end'
WHERE	p.post_status='publish' AND
	c.meta_value=%d AND
	s.meta_value<=%d AND
	e.meta_value>=%d
ORDER BY
	s.meta_value, p.post_name ASC;
ENDOFQUERY;

// Define a query to get feature events
$_featured_query = <<<ENDOFFEATURE
SELECT  p.ID
FROM    $wpdb->posts p
	INNER JOIN $wpdb->postmeta f ON f.post_id=p.ID AND f.meta_key='ecp1_event_is_featured'
	INNER JOIN $wpdb->postmeta s ON s.post_id=p.ID AND s.meta_key='ecp1_event_start'
	INNER JOIN $wpdb->postmeta e ON e.post_id=p.ID AND e.meta_key='ecp1_event_end'
WHERE   p.post_status='publish' AND
	f.meta_value='Y' AND
	s.meta_value<=%d AND
	e.meta_value>=%d
ORDER BY
	s.meta_value, p.post_name ASC;
ENDOFFEATURE;

// Get and validate the input parameters
if ( empty( $wp_query->query_vars['ecp1_start'] ) || empty( $wp_query->query_vars['ecp1_end'] ) ) {
	_ecp1_template_event_json_error( __( 'Please specify a start and end timestamp for the lookup range' ),
						412, __( 'Missing Parameters' ) );
} else {

	$cal   = $wp_query->query_vars['ecp1_cal'];
	$start = preg_match( '/^\-?[0-9]+$/', $wp_query->query_vars['ecp1_start'] ) ? 
			(int) $wp_query->query_vars['ecp1_start'] : null;
	$end   = preg_match( '/^\-?[0-9]+$/', $wp_query->query_vars['ecp1_end'] ) ?
			(int) $wp_query->query_vars['ecp1_end'] : null;

	if ( is_null( $start ) || is_null( $end ) ) {
		_ecp1_template_event_json_error( __( 'Please specify the start and end as timestamps' ),
							412, __( 'Incorrect Parameter Format' ) );
	} elseif ( $end < $start ) {
		_ecp1_template_event_json_error( __( 'The end date must be after the start date' ),
							412, __( 'Incorrect Parameter Format' ) );
	} else {

		// Encode as JSON (unless ECP1_TEMPLATE_TEST_ARG is '1')
		if ( ! empty( $wp_query->query_vars[ECP1_TEMPLATE_TEST_ARG] ) &&
				'1' == $wp_query->query_vars[ECP1_TEMPLATE_TEST_ARG] ) {
			header( 'Content-Type:text/plain' );
		} else {
			header( 'Content-Type:application/json' );
		}

		// Reset the default WordPress query just in case
		wp_reset_query();

		// Lookup the calendar post
		$cal = get_page_by_path( $cal, OBJECT, 'ecp1_calendar' );
		if ( is_null( $cal ) ) {
			_ecp1_template_event_json_error( __( 'No such calendar.' ), 404, __( 'Calendar Not Found' ) );
		} else {

			_ecp1_parse_calendar_custom( $cal->ID ); // Get the calendar meta data
			$tz = ecp1_get_calendar_timezone();      // and the effective timezone
			$dtz = new DateTimeZone( $tz );

			// Get the ecp1_events that match the range
			// The parameters are at UTC and so are dates in database => no converting needed
			// Note: Using query_posts is supported here as this is meant to be the main loop
			// Note: The SQL has start comparission BEFORE end comparisson $end before $start
			// ROADMAP: Repeating events - probably will need to abstract this
			$event_ids = $wpdb->get_col( $wpdb->prepare( $_events_query, $cal->ID, $end, $start ) );
			
			// Now look to see if this calendar supports featured events and if so load ids
			if ( _ecp1_calendar_show_featured( $cal->ID ) )
				$event_ids = array_merge( $event_ids, $wpdb->get_col( $wpdb->prepare( $_featured_query, $end, $start ) ) );

			// If any events were found load them into the loop
			if ( count( $event_ids ) > 0 )
				query_posts( array( 'post__in' => $event_ids, 'post_type'=>'ecp1_event' ) );

			// An array of JSON parameters for the event
			$events_json = array();
			$_e_index = 0;

			// Equiv of The Loop
			while ( have_posts() ) : the_post();
				_ecp1_parse_event_custom(); // load event meta

				// Check the custom fields make sense
				if ( _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ||
						_ecp1_event_meta_is_default( 'ecp1_end_ts' ) )
					continue; // need a start and finish so skip to next post

				// Events are stored as timestamps at UTC so we need to
				// format them with the calendar timezone before sending;
				// offsets vary for each date depending on things like DST
				try {
					$e  = _ecp1_event_meta( 'ecp1_start_ts', false );
					$es = new DateTime( "@$e" ); // requires PHP 5.2.0
					$e  = _ecp1_event_meta( 'ecp1_end_ts', false );
					$ee = new DateTime( "@$e" ); // 5.2.0 again

					$events_json[$_e_index] = array(
						'title'  => the_title( '', '', false ),
						'start'  => $es->setTimezone( $dtz )->format( 'c' ), # ISO8601 automatically handling DST and
						'end'    => $ee->setTimezone( $dtz )->format( 'c' ), # the other seasonal variations in offset
						'allDay' => 'Y' == _ecp1_event_meta( 'ecp1_full_day', false ) ? true : false,
					); 
					
					// If the event has a summary then put it in
					if ( ! _ecp1_event_meta_is_default( 'ecp1_summary' ) )
						$events_json[$_e_index]['description'] = _ecp1_event_meta( 'ecp1_summary' );

					// Create a Where string like event post page
					if ( ! _ecp1_event_meta_is_default( 'ecp1_location' ) )
						$events_json[$_e_index]['location'] = sprintf( '%s',  _ecp1_event_meta( 'ecp1_location' ) );

					// Does this event want maps to be shown?
					$events_json[$_e_index]['showmap'] = 'Y' == _ecp1_event_meta( 'ecp1_showmap' ) ? true : false;
					if ( $events_json[$_e_index]['showmap'] ) { // only send map zoom and marker if show map
						if ( ! _ecp1_event_meta_is_default( 'ecp1_map_zoom' ) )
							$events_json[$_e_index]['zoom'] = (int) _ecp1_event_meta( 'ecp1_map_zoom' );
						if ( 'Y' == _ecp1_event_meta( 'ecp1_showmarker' ) ) {
							if ( ! _ecp1_event_meta_is_default( 'ecp1_map_placemarker' ) && file_exists( ECP1_DIR . '/img/mapicons/' . _ecp1_event_meta( 'ecp1_map_placemarker' ) ) )
								$events_json[$_e_index]['mark'] = plugins_url( '/img/mapicons/' . _ecp1_event_meta( 'ecp1_map_placemarker' ), dirname( dirname( __FILE__ ) ) );
							else
								$events_json[$_e_index]['mark'] = true;
						} else {
							$events_json[$_e_index]['mark'] = false;
						}
					}

					// If there are Lat/Lng send them for the event
					if ( ! _ecp1_event_meta_is_default( 'ecp1_coord_lat' ) && ! _ecp1_event_meta_is_default( 'ecp1_coord_lng' ) ) {
						$events_json[$_e_index]['lat'] = (float) _ecp1_event_meta( 'ecp1_coord_lat' );
						$events_json[$_e_index]['lng'] = (float) _ecp1_event_meta( 'ecp1_coord_lng' );
					}

					// Now for the tricky part: if an event only has a URL then set URL to that
					// if event only has a description set URL to the event post page; and if
					// neither then don't set the URL option
					$ecp1_desc = _ecp1_event_meta_is_default( 'ecp1_description' ) ? null : get_permalink( $post->ID );
					$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? null : urldecode( _ecp1_event_meta( 'ecp1_url' ) );
					if ( ! is_null( $ecp1_desc ) && ! is_null( $ecp1_url ) ) {
						// Both given so render as link to post page
						$events_json[$_e_index]['url'] = $ecp1_desc;
					} elseif ( ! is_null( $ecp1_desc ) ) {
						// Only a description: link to post page
						$events_json[$_e_index]['url'] = $ecp1_desc;
					} elseif ( ! is_null( $ecp1_url ) ) {
						// Only a URL: link straight to it
						$events_json[$_e_index]['url'] = $ecp1_url;
					}

					// If feature images are enabled by the them (aka Post Thumbnails) then show if there is one
					if ( function_exists( 'add_theme_support' ) && function_exists( 'get_the_post_thumbnail' ) ) {
						$attrs = array( 'title' => the_title( '', '', false ), 'alt' => __( 'Event Logo' ) );
						if ( has_post_thumbnail( $post->ID ) )
							$events_json[$_e_index]['imageelem'] = get_the_post_thumbnail( $post->ID, 'thumbnail', $attrs );
					}
					
					// Successfully added an event increment the counter
					$_e_index += 1;
				} catch( Exception $datex ) {
					continue; // ignore bad timestamps they shouldn't happen
				}
			endwhile;

			// Reset the query now the loop is done
			wp_reset_query();

			// JSON Encode the results
			printf( json_encode( $events_json ) );

		} // calendar was found

	} // start and end both valid

} // start and end both given

?>
