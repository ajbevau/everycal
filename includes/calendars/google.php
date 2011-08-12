<?php
/**
 * Every Calendar +1 WordPress Plugin
 *
 * Google Calendar Backend Plugin that syndicates Google Cal events
 * for further details see calendar-interface.php for the base class.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Load the abstract class that this implements
require_once( ECP1_DIR . '/includes/calendars/calendar-interface.php' );

// Google Calendar Implementation
class ECP1GoogleCalendar extends ECP1Calendar {

	// Takes the offset number of seconds that a cached set of events
	// is valid for, compares against the cached meta update timestamp
	// and returns boolean true if expired or false if still valid.
	public function cache_expired( $offset ) {
		$cached_at = $this->get_meta( 'req_ts', null );
		if ( null == $cached_at || ! is_numeric( $cached_at ) )
			return true; // no cache so technically expired

		return ( $cached_at + $offset < time() );
	}

	// Takes start and end unix timestamps and DateTimeZone object
	// Makes a request to the URL and then parses the JSON-C.
	// Return TRUE on success or FALSE on failure.
	public function fetch( $start, $end, $dtz ) {
		// Google links to basic Calendar Feeds by default swap to full
		$url = preg_replace( '/\/basic/', '/full', $this->calendar_url );

		// Google provides HTTP 304 codes so support not-modified
		$cached_at = $this->get_meta( 'req_ts', null );
		if ( null == $cached_at || ! is_numeric( $cached_at ) )
			$cached_at = 946684800; // Midnight 1st Jan 2000 UTC

		// Validate the start and end params and get objects for formatting
		try {
			$start     = new DateTime( "@$start" );
			$end       = new DateTime( "@$end" );
			$cached_at = new DateTime( "@$cached_at" );
		} catch( Exception $_e ) {
			return false; // error out NOW
		}

		// Ensure the timezone is valid or error out
		if ( ! $dtz instanceof DateTimeZone )
			return false;

		// Build a request object using the HTTP_Request2 class
		$request = new HTTP_Request2( $url, HTTP_Request2::METHOD_GET );
		$request->setHeader( 'If-Modified-Since', $cached_at->format( 'D, j M Y H:i:s') . ' GMT' );
		$request->setConfig( array(
			'follow_redirects' => true,
			'max_redirects' => 2, // Google occasionally sets a session
		) );

		// Add parameters to the URL (by reference so don't have to re-set)
		$url = & $request->getUrl();
		$url->setQueryVariables( array(
			'alt' => 'jsonc', // we want JSON-C data
			'ctz' => $dtz->getName(),
			'singleevents' => 'true', // expand repeats must be word true
			// Google asks for RFC3339 which is a ISO8601 profile
			'start-min' => $start->setTimezone( $dtz )->format( 'c' ),
			'start-max' => $end->setTimezone( $dtz )->format( 'c' ),
			'max-results' => 500, // be nice to the server
		) );

		// Caching result
		$result = true;

		// Send the request and process it
		try {
			$response = $request->send();
			$status = $response->getStatus();
			$cached_at = time();
			if ( 304 == $status ) {         // HTTP NOT MODIFED
				$this->add_meta( 'req_ts', $cached_at ); // cache still valid
			} else if ( 200 == $status ) {  // HTTP OK
				$this->add_meta( 'req_ts', $cached_at ); // new cache time
				$json = $this->parse_json( $response->getBody(), $dtz );	
				if ( null == $json ) {
					$result = false;
				} else {
					foreach( $json as $_gce ) {
						$this->add_event( $_gce['id'], $_gce['start'], $_gce['end'],
									$_gce['allday'], $_gce['title'], $_gce['alternateLink'],
									$_gce['location'], null, $_gce['details'] );
					}
					$this->save_to_cache(); // store the cache
				}
			} else {                        // HTTP 201? AND 400+
				$result = false;
			}
		} catch( HTTP_Request2_Exception $_he ) {
			$result = false;
		}

		return $result;
	}

	// Private function that parses a JSON-C string into event components
	// then returns the partial JSON array for adding to events locally.
	private function parse_json( $json, $dtz ) {
		$json = json_decode( $json, true );
		if ( null == $json )
			return null;

		// firstly we only want the [data] array
		if ( ! array_key_exists( 'data', $json ) || ! is_array( $json['data'] ) )
			return null;
		$json = $json['data'];
		$items = $json['totalResults'];

		// get rid of things we don't need
		$splice = explode( ',', 'kind,selfLink,canEdit,created,updated,creator,' .
					'anyoneCanAddSelf,guestsCanInviteOthers,guestsCanModify,' .
					'guestsCanSeeGuests,sequence,transparency,attendees,status' );

		// look at the [items] now
		if ( $items > 0 ) {
			if ( ! array_key_exists( 'items', $json ) || ! is_array( $json['items'] ) )
				return null;
			$json = $json['items'];

			for ( $i=0; $i < $items; $i++ ) {
				// clean up the array
				foreach( $splice as $key )
					unset( $json[$i][$key] );
				$json[$i]['start'] = $json[$i]['when'][0]['start'];
				$json[$i]['end'] = $json[$i]['when'][0]['end'];
				unset( $json[$i]['when'] );

				// now convert the start/end into something useful
				$json[$i]['allday'] = ( strpos( $json[$i]['start'], 'T' ) === FALSE ? 'Y' : 'N' ); 
				$json[$i]['start'] = strtotime( $json[$i]['start'] );
				$json[$i]['end'] = strtotime( $json[$i]['end'] );
				// if all day full calendar expects events to be 1s before midnight
				// and we want to move the UTC time by the offset of the timezone
				// because strtotime will give midnight @ GMT but want midnight at
				// the timezone the calendar made the request in.
				if ( 'Y' == $json[$i]['allday'] ) {
					try {
						$de = new DateTime( "@" . $json[$i]['start'] );
						$json[$i]['start'] -= $dtz->getOffset( $de );
						$de = new DateTime( "@" . $json[$i]['end'] );
						$json[$i]['end'] -= $dtz->getOffset( $de );
					} catch( Exception $_e ) {} // do nothing just use GMT
					$json[$i]['end'] -= 1; // 1s before midnight
				}

				// be safe and escape data for later
				$json[$i]['alternateLink'] = urlencode( $json[$i]['alternateLink'] );
			}
		} else {
			$json['items'] = array();
		}

		return $json;
	}

}

?>
