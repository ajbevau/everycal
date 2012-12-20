<?php
/**
 * Every Calendar +1 Plugin Widget: List of event titles
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Also we need the plugin functions
require_once( ECP1_DIR . '/functions.php' );

/**
 * Widget for listing events by date
 *
 */
class ECP1_TitleListWidget extends WP_Widget {

	/**
	 * Widget constructor
	 * @see WP_Widget::WP_Widget
	 */
	function ECP1_TitleListWidget() {
		// Just call the parent with appropriate attributes for this class
		parent::WP_Widget('ecp1_titlelistwidget', 'EveryCal+1 Event Title List', array(
			'description' => 'EveryCal+1 Event Title List - An ordered list of event titles up to given number'
		));
	}

	/**
	 * Static constants for defining sorting order
	 */
	const SORT_NEXT_FIRST = 1;
	const SORT_LAST_FIRST = 2;

	/**
	 * Display the widget
	 * @see WP_Widget:widget
	 *
	 * @param $args Array of widget arguments from WordPress
	 * @param $instance Array of widget configured settings from admin
	 */
	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget; // from $args
		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		// Get an array of the events 
		$cal = get_post( $instance['calendar'], OBJECT );
		if ( is_null( $cal ) ) {
			printf( '<p>%s</p>', __( 'Could not load calendar!' ) );
			return;
		}

		// Load the calendar meta details
		_ecp1_parse_calendar_custom( $cal->ID ); // Get the calendar meta data
		$tz = ecp1_get_calendar_timezone();   // and the effective timezone
		$dtz = new DateTimeZone( $tz );
		$ex_cals = _ecp1_calendar_meta( 'ecp1_external_cals' ); // before loop
		$my_id = $cal->ID; // because event meta reparses its calendars meta

		// Get the time and 1 year in the future
		$now = new DateTime( NULL, new DateTimeZone( 'UTC' ) );
		$start = (int) $now->format( 'U' );
		$end = $start + 31536000; // 60 * 60 * 24 * 365

		// Get the $ecp1_event_fields pseudo arrays
		$events = array(); // write output to this array
		$db_events = EveryCal_Scheduler::GetEvents( $cal->ID, $start, $end );
		$iter_counter = 0;
		
		// Loop over each event and render an iCal block
		foreach( $db_events as $event ) {
			
		    // Make sure there are start and end times
		    if ( _ecp1_render_default( $event, 'ecp1_start_ts' ) || _ecp1_render_default( $event, 'ecp1_end_ts' ) )
				continue;

		    // The events timestamps will be a unix timestamp at the localtime of the
		    // calendar that event is published on. If this event is published on a
		    // diferent calendar then the timezone may need to be adjusted.
		    try {
				// Build UTC DateTime objects to begin with
				$estart = new DateTime( '@' . $event['ecp1_start_ts'] );
				$eend   = new DateTime( '@' . $event['ecp1_end_ts'] );

		        // Is this event on this calendar?
				if ( $event['ecp1_calendar'] == $cal->ID ) { // YES SAME CALENDAR
					$estart->setTimezone( $dtz );
					$eend->setTimezone( $dtz );
				} else { // NO DIFFERENT CALENDAR

		            // Get the source calendar timezone and check it's different
					$scaltz = new DateTimeZone( $event['_meta']['calendar_tz'] );
					if ( $dtz->getOffset( $now ) == $scaltz->getOffset( $now ) ) {
						$estart->setTimezone( $scaltz );
						$eend->setTimezone( $scaltz );
					} else { // OFFSET IS DIFFERENT
					
						// If this is a featured event then an option controls if we rewrite the time
						// if it's just a regular event then we always rewrite the time to local zone
						if ( 'Y' == $event['ecp1_featured'] ) { // feature event
							if ( '1' == _ecp1_get_option( 'base_featured_local_to_event' ) ) {
								// User has requested timezone to be rebased to local
								$estart->setTimezone( $scaltz );
								$eend->setTimezone( $scaltz );
							} else {
								// Times should be shown at this calendars timezone
								$estart->setTimezone( $dtz );
								$eend->setTimezone( $dtz );
							}
						} else { // non-featured event
							// Always use the events publish calendar timezone
							$estart->setTimezone( $dtz );
							$eend->setTimezone( $dtz );
						}
						
					}
					
				}
				
				// Get the event attributes for output
				$dateformat = 'j/M'; // get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$startstr = $estart->format( $dateformat );
				$titlestr = get_the_title( $event['post_id'] );
				$linkstr  = ecp1_permalink_event( $event );

				// Set the array element
				$events[] = array( 'when' => $startstr, 'title' => $titlestr, 'link' => $linkstr );

			// Some form of error occured (probably with the dates)
			} catch( Exception $datex ) {
				continue; // ignore bad timestamps they shouldn't happen
			}

			// Increment the counter and break the loop if done
			$iter_counter += 1;
			if ( $iter_counter >= $instance['count'] )
				break;

		} // End loop of events

		// Loop over the events and display them in a list
		printf( '<ol class="%s">', esc_attr( $instance['list_class'] ) );
		foreach( $events as $event ) {
			printf( '<li class="%s"><span>%s:</span> <a href="%s" title="Visit event page">%s</a></li>',
				esc_attr( $instance['item_class'] ),
				$event['when'], $event['link'], $event['title'] );
		}
		print( '</ol>' );
		echo $after_widget; // from $args
	}

	/**
	 * Called to update/save the widget settings
	 * @see WP_Widget::update
	 *
	 * The following parameters are POSTED using the form() below
	 *  calendar: The EveryCal calendar for events
	 *  title: The widget title text
	 *  count: The number of events to display
	 *  sort_order: Newest first or oldest first
	 *  list_class: CSS Class for list element
	 *  item_class: CSS Class for list item elements
	 *
	 * @param $new_instance New instance of values from the form
	 * @param $old_instance Previous instance values
	 * @return Complete set of values for the widget 
	 */
	function update( $new_instance, $old_instance ) {
		$ins = $old_instance;
		$cal_new = intval( $new_instance['calendar'] );
		// Ensure the calendar exists
		$cals = get_posts( array( 'post_type' => 'ecp1_calendar', 'include' => array( $cal_new ) ) );
		if ( count( $cals ) > 0 )
			$ins['calendar'] = $cal_new;
		$ins['title'] = sanitize_text_field( $new_instance['title'] );
		$ins['count'] = intval( $new_instance['count'] );
		$ins['sort_order'] = intval( $new_instance['sort_order'] );
		$ins['list_class'] = sanitize_text_field( $new_instance['list_class'] );
		$ins['item_class'] = sanitize_text_field( $new_instance['item_class'] );
		return $ins;
	}

	/**
	 * Render a form for configuring the widget
	 * @see WP_Widget::form
	 *
	 * See update() for a description of the form fields.
	 *
	 * @param $instance Current widget settings
	 */
	function form( $instance ) {
		// Set some defaults if this is a new widget
		$defaults = array(
			'calendar' => 0,
			'title' => __( 'Upcoming events' ),
			'count' => 5,
			'list_class' => 'ecp1_list',
			'item_class' => 'ecp1_list_item',
			'sort_order' => self::SORT_NEXT_FIRST,
		);

		// Parse the arguments in instance and merge with defaults
		$instance = wp_parse_args( (array) $instance, $defaults );

		// Get a list of all the valid calendars
		$cal_options = array();
		$cals = _ecp1_current_user_calendars();
		foreach( $cals as $cal )
			$cal_options[$cal->ID] = esc_html( $cal->post_title );

		// Create the form for the fields
		$fields = array(
			'calendar' => array( 'label' => __( 'Calendar' ), 'type' => 'select', 'options' => $cal_options ),
			'title' => array( 'label' => __( 'Title' ), 'type' => 'text', 'length' => 100 ),
			'count' => array( 'label' => __( 'Number of events' ), 'type' => 'text', 'length' => 2 ),
			'sort_order' => array( 'label' => __( 'Order' ), 'type' => 'select', 'options' => array(
				self::SORT_NEXT_FIRST => __( 'Next event first' ),
				self::SORT_LAST_FIRST => __( 'Next event last' ) ) ),
			'list_class' => array( 'label' => __( 'Ordered list CSS class' ), 'type' => 'text', 'length' => 20 ),
			'item_class' => array( 'label' => __( 'List item CSS class' ), 'type' => 'text', 'length' => 20 )
		);
		print( '<ul class="ecp1_admin_form_list">' );
		foreach( $fields as $key=>$dtl ) {
			$id = $this->get_field_id( $key );
			$name = $this->get_field_name( $key );
			printf( '<li><label for="%s">%s</label>', esc_attr( $id ), esc_html( $dtl['label'] ) );
			if ( $dtl['type'] == 'text' ) {
				printf( '<input type="text" maxlength="%d" id="%s" name="%s" value="%s" />',
					array_key_exists( 'length', $dtl ) ? $dtl['length'] : 255,
					esc_attr( $id ), esc_attr( $name ), esc_attr( $instance[$key] ) );
			} else if ( $dtl['type'] == 'select' ) {
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach( $dtl['options'] as $v=>$l )
					printf( '<option value="%s"%s>%s</option>', $v, intval( $instance[$key] ) == intval( $v ) ? ' selected="selected"' : '', $l );
				print( '</select>' );
			}
			print( '</li>' );
		}
		print( '</ul>' );
		printf( '<p>%s</p>', __( 'Note: this list will only include events occuring in the next 12 months.' ) );
	}

}

// Don't close the php interpreter
/*?>*/
