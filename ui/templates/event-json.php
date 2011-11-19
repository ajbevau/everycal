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

// Get and validate the input parameters
if ( ! isset( $wp_query->query_vars['ecp1_start'] ) || ! isset( $wp_query->query_vars['ecp1_end'] ) ) {
	_ecp1_template_error( __( 'Please specify a start and end timestamp for the lookup range' ),
						412, __( 'Missing Parameters' ) );
} else {

	$start = preg_match( '/^\-?[0-9]+$/', $wp_query->query_vars['ecp1_start'] ) ? 
			(int) $wp_query->query_vars['ecp1_start'] : null;
	$end   = preg_match( '/^\-?[0-9]+$/', $wp_query->query_vars['ecp1_end'] ) ?
			(int) $wp_query->query_vars['ecp1_end'] : null;

	if ( is_null( $start ) || is_null( $end ) ) {
		_ecp1_template_error( __( 'Please specify the start and end as timestamps' ),
							412, __( 'Incorrect Parameter Format' ) );
	} elseif ( $end < $start ) {
		_ecp1_template_error( __( 'The end date must be after the start date' ),
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

		// Remove any actions on loop
		remove_all_actions( 'loop_start' );
		remove_all_actions( 'the_post' );
		remove_all_actions( 'loop_end' );

		// Lookup the calendar post
		$cal = get_page_by_path( $cal, OBJECT, 'ecp1_calendar' );
		if ( is_null( $cal ) ) {
			_ecp1_template_error( __( 'No such calendar.' ), 404, __( 'Calendar Not Found' ) );
		} else {

			_ecp1_parse_calendar_custom( $cal->ID ); // Get the calendar meta data
			$tz = ecp1_get_calendar_timezone();      // and the effective timezone
			$dtz = new DateTimeZone( $tz );

			// Get the ecp1_events that match the range
			// The parameters are at UTC and so are dates in database => no converting needed
			// Note: Using query_posts is supported here as this is meant to be the main loop
			// Note: The SQL has start comparission BEFORE end comparisson $end before $start
			// ROADMAP: Repeating events - probably will need to abstract this
			$event_ids = $wpdb->get_col( $wpdb->prepare( _ecp1_tq( 'EVENTS' ), $cal->ID, $end, $start ) );
			
			// Now look to see if this calendar supports featured events and if so load ids
			// and the event colors: can't load later because the calendar meta is updated 
			// during _ecp1_parse_event_custom to be specific to THAT event
			$feature_ids = array();
			$feature_color = $feature_textcolor = '#000000';
			if ( _ecp1_calendar_show_featured( $cal->ID ) ) {
				$feature_ids = $wpdb->get_col( $wpdb->prepare( _ecp1_tq( 'FEATURED_EVENTS' ), $end, $start ) );
				$feature_color = _ecp1_calendar_meta( 'ecp1_feature_event_color' );
				$feature_textcolor = _ecp1_calendar_meta( 'ecp1_feature_event_textcolor' );
			}

			// Create a unique merged set of post ids to load
			$event_ids = array_merge( $event_ids, $feature_ids );

			// If any events were found load them into the loop
			if ( count( $event_ids ) > 0 )
				query_posts( array( 'post__in' => $event_ids, 'post_type'=>'ecp1_event', 'nopaging'=>true ) );

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
					$es->setTimezone( $dtz );
					$e  = _ecp1_event_meta( 'ecp1_end_ts', false );
					$ee = new DateTime( "@$e" ); // 5.2.0 again
					$ee->setTimezone( $dtz );

					$events_json[$_e_index] = array(
						'title'  => the_title( '', '', false ),
						'start'  => $es->format( 'c' ), # ISO8601 automatically handling DST and
						'end'    => $ee->format( 'c' ), # the other seasonal variations in offset
						'allDay' => 'Y' == _ecp1_event_meta( 'ecp1_full_day', false ) ? true : false,
					); 

					// If custom colors were specified for this event show them
					// NOTE: feature event colors will overwrite these if they're set
					if ( 'Y' == _ecp1_event_meta( 'ecp1_overwrite_color' ) ) {
						if ( ! _ecp1_event_meta_is_default( 'ecp1_local_textcolor' ) )
							$events_json[$_e_index]['textColor'] = _ecp1_event_meta( 'ecp1_local_textcolor' );
						if ( ! _ecp1_event_meta_is_default( 'ecp1_local_color' ) )
							$events_json[$_e_index]['color'] = _ecp1_event_meta( 'ecp1_local_color' );
					}

					// If this is a feature event (not from this calendar) then give it the feature colors
					// and optionally also change the start/end times to be event local not calendar local
					if ( _ecp1_event_meta( 'ecp1_calendar' ) != $cal->ID && in_array( get_the_ID(), $feature_ids ) ) {
						$events_json[$_e_index]['color'] = $feature_color;
						$events_json[$_e_index]['textColor'] = $feature_textcolor;

						// Base feature events at local calendar timezone or event local timezone?
						if ( '1' == _ecp1_get_option( 'base_featured_local_to_event' ) ) {
							$tz = ecp1_get_calendar_timezone(); // updated on _ecp1_parse_event_custom()
							$localdtz = new DateTimeZone( $tz );
							$es->setTimezone( $localdtz );
							$ee->setTimezone( $localdtz );
							$events_json[$_e_index]['start'] = $es->format( 'c' );
							$events_json[$_e_index]['end'] = $ee->format( 'c' );
						}
					}

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
