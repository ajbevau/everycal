<?php
/**
 * Every Calendar Scheduler Interface
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// We need to know about calendars and events
require_once( ECP1_DIR . '/includes/data/event-fields.php' );
require_once( ECP1_DIR . '/includes/data/calendar-fields.php' );

// Also need to know about expressions and exceptions
require_once( ECP1_DIR . '/includes/repeat-expression.php' );
require_once( ECP1_DIR . '/includes/repeat-exception.php' );


/**
 * EveryCal+1 Scheduler Class
 * Manages the event schedule for once off and repeating events
 * by caching look ups of repeating events and reading forward
 * when the range is requested.
 *
 * You do not need to instantiate this class ALL functions are static.
 */
class EveryCal_Scheduler
{

	/**
	 * CountCache Function
	 * Returns a count of the future cached repeats of the given event.
	 *
	 * @param $event_id The event to could the cache for
	 * @return Number of future cached events (0 if not repeating)
	 */
	public static function CountCache( $event_id )
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'ecp1_cache';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE start >= CURDATE() AND post_id = %s", $event_id ) );
		if ( ! $count ) $count = 0;
		return $count;
	}
	
	/**
	 * BuildCache Function
	 * Creates cached repeats for all events in the calendar between
	 * the given start and end date range. The input timestamps are
	 * used as if at UTC/GMT/ZULU while this function only really
	 * required a number as the input if you do not use GMT times
	 * the cache range will be out by your locations offset.
	 *
	 * @param $cal_id The Calendar to build the cache for
	 * @param $start The start timestamp (int) to build forward from
	 * @param $end The end timestamp (int) to build forward until
	 * @return True or False indicating success of caching operation
	 */
	public static function BuildCache( $cal_id, $start, $end )
	{
		// Get all the events that are attached to this calendar
		// Call BuildEventCache for each of the event ids
		// TODO: Implement the BuildCache(calendar) function
		return true;
	}
	
	/**
	 * BuildCache Function for Event (private)
	 *
	 * @param $event_id The Event to build the cache for
	 * @param $start The start timestamp (int) to build forward from
	 * @param $end The end timestamp (int) to build forward until
	 * @return True or False indicating success of caching operation
	 */
	private static function BuildEventCache( $event_id, $start, $end )
	{
		// Lookup the event start and end times and the repeat from
		// and repeat to times then use the events repeat expression
		// to get the repeat dates but only if the event repeats
		// TODO: Add these to the event fields and options array
		// TODO: Take into account repeat limitations i.e. 10 times etc...
		_ecp1_parse_event_custom( $event_id );
		if ( 'Y' != _ecp1_event_meta( 'ecp1_repeating' ) )
			return false; // not a repeating event
		
		// Construct actual DateTime objects from the parameters
		$startdt = null;
		$enddt = null;
		try {
			$startdt = new DateTime( "@".$start ); // PHP 5.2.0
			$enddt = new DateTime( "@".$end );
		} catch( Exception $dateex ) {
			return false; // errors in input parameters
		}
		
		// Check that the cache time is NOT longer than the setting maximum
		if ( $end - $start > _ecp1_get_option( 'max_repeat_cache_block' ) )
			return false;
		
		// Get the repeating timestamps
		$repeatfrom = null;
		$repeatuntil = null;
		try {
			$repeatfrom = new DateTime( "@" . _ecp1_event_meta( 'ecp1_repeat_from' ) );
			// Only lookup the end if the event doesn't repeat forever
			if ( 'Y' != _ecp1_event_meta( 'ecp1_repeats_forever' ) )
				$repeatuntil = new DateTime( "@" . _ecp1_event_meta( 'ecp1_repeat_until' ) );
		} catch( Exception $dateex ) {
			return false; // cannot cache this event don't know its repeat range
		}
		
		// The event must repeat in the time range we're caching
		// however it is not an error to try and build the cache
		if ( ( $repeatuntil != null && $repeatuntil < $startdt ) || $repeatfrom > $enddt )
			return true;
		// else i.e. ( repeatuntil == null || repeatuntil >= start ) && repeatfrom <= end
		
		// Lookup the repeat expression and create an instance of the controller
		$repeater = null;
		try {
			$repeater = new RepeatExpression( _ecp1_event_meta( 'ecp1_crontab' ) );
		} catch( Exception $cronex ) {
			return false; // not a valid expression so can't cache
		}
		
		// Use the repeater to get all the instances of repeat start times
		foreach( $repeater->GetRepeatsBetween( $startdt, $enddt ) as $repeat ) {
			if ( ! self::StoreEventRepeat( $event_id, $repeat ) )
				return false;
		}
		
		// Caching operation was successful
		return true;
	}
	
	/**
	 * StoreEventRepeat Function (private)
	 * Stores a calculated entry in the event repeat cache.
	 *
	 * @param $event_id The event to store this entry for
	 * @param $repeat_start The start GMT/UTC/Zulu time for the repeat
	 * @return True of False success of the save operation
	 */
	private static function StoreEventRepeat( $event_id, $repeat_start )
	{
		// TODO: Implement the StoreEventRepeat function
		return true;
	}
	
	/**
	 * GetEvents Function
	 * Looks up the events in the given calendar that run in the 
	 * time range specified by the start and end parameters. The
	 * function assumes that the input timestamps are GMT/UTC/Zulu
	 * (which is the internal stored timezone). If you give times
	 * at another timezone you may have missing events.
	 *
	 * @param $cal_id The Calendar to build the cache for
	 * @param $start The start timestamp (int) to build forward from
	 * @param $end The end timestamp (int) to build forward until
	 * @return Array of event arrays with repeats expanded
	 */
	public static function GetEvents( $cal_id, $start, $end )
	{
		// Get all the events that are attached to this calendar
		// TODO: Implement the GetEvents function
		// For each event lookup the exceptions to see if there are any
		// exceptions at the repeat_start; if there are then they may
		// change any detail of the event, or even cancel this repeat.
		foreach( $cal_events as $event ) {
			$event_id = $event['id'];
 			$event_repeats = $event['repeats'];
			foreach( $event_repeats as $event_repetition ) {
				$repeat_start = $event_repetition['start'];
				$exceptions = EveryCal_Exception::Find( $event_id, $repeat_start );
				if ( null != $exceptions ) {
					foreach( $exceptions as $exception )
						$exception->ApplyChanges( $event_repetition );
				}
			}
		}
		return array();
	}
	
	/**
	 * EventUpdate Function
	 * Rebuilds the cache for a given event wherever repeats for that event
	 * have already been cached. Only future repeats (i.e. ones) not already
	 * past will be updated. The ecp1_repeat_cache_start_point event meta
	 * value stores the start date last time the cache was built and should
	 * be updated by this function.
	 *
	 * @param $event_id The event to update the repeats for
	 * @return True or False if the update was successful
	 */
	public static function EventUpdate( $event_id )
	{
		// TODO: Implement the EventUpdate function
		return true;
	}

}

?>
