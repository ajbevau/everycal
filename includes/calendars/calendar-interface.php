<?php
/**
 * Every Calendar +1 WordPress Plugin
 *
 * External calendar feed caching and syndication interface.
 *
 * The events cache is stored as postmeta on the calendar post.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// The plugin bundles PEAR HTTP_Request2 for making requests
// But if it is installed locally we will use that instead
$_localPear = ECP1_DIR . '/pear';
set_include_path( get_include_path() . PATH_SEPARATOR . $_localPear );
require_once( ECP1_DIR . '/pear/HTTP/Request2.php' );

// The abstract maps class
abstract class ECP1Calendar {

	// Abstract function that takes an offset in seconds: 
	//   ecp1_ical_cache_time = seconds to cache ical before refetch
	//
	// Returns TRUE of FALSE if the cache for the URL has expired.
	// If your implementation doesn't do caching then return true.
	abstract public function cache_expired( $offset );

	// Abstract function that takes makes a request to the URL
	// for events. Parameters are start and end unix timestamps
	// and a DateTimeZone object.
	//
	// This function should buffer the events to $this->events
	// using the $this->add_event function (see below).
	//
	// You can assume HTTP_Request2 exists when implementing.
	//
	// Should return TRUE on success or FALSE on error.
	abstract public function fetch( $start, $end, $dtz );

	// Calendar Constructor
	// Takes the Calendar Post ID and External Calendar URL
	// If you overload this constructor YOU should call
	//   parent::__construct( $id, $url )
	// to set the instance variables and load cache.
	public function __construct( $id, $url ) {
		$this->postid = $id;
		$this->calendar_url = $url;
		$this->load_from_cache(); // database reads are cheap
	}

	// Internal store of the calendar post ID
	private $postid = null;

	// Internal store of the URL these events are from
	protected $calendar_url = null;

	// Internal buffer of events
	// This is an array of arrays keyed by a hash of the URL
	private $events = array();

	// Function that HASHES the URL
	private function url_hash() {
		return sha1( 'ecp1-' . $this->postid . '/' . urlencode( $this->calendar_url ) );
	}

	// Function that clears the set of events and any meta.
	protected function clear() {
		$this->events = array();
	}

	// Function that loads the cached (if any) events for this URL
	private function load_from_cache() {
		if ( ! is_array( $this->events ) )
			$this->events = array();

		// Use the WordPress functions to load the post meta
		// which will automatically unserialize the array
		$load = get_post_meta( $this->postid, 'ecp1_cache_' . $this->url_hash(), true );
		if ( '' != $load && is_array( $load ) ) // meta was set and loaded as array
			$this->events = array_merge( $this->events, $load );
	}

	// Function that saves the events (if any) to calendar postmeta
	public function save_to_cache() {
		if ( ! is_array( $this->events ) || 0 == count( $this->events ) )
			return;
		update_post_meta( $this->postid, 'ecp1_cache_' . $this->url_hash(), $this->events );
	}

	// Function that allows storing of ANY meta information about
	// the URLs events, the feed, settings, whatever is needed.
	// The intended purpose here was for cache control: create a
	// meta key that is the last updated parameter and check it
	// before fetching.
	protected function add_meta( $key, $value ) {
		if ( ! is_array( $this->events ) )
			$this->events = array();
		if ( ! array_key_exists( '_meta', $this->events ) )
			$this->events['_meta'] = array();

		$this->events['_meta'][$key] = $value;
	}

	// Function that looks up a meta key and returns the value.
	// The optional $default parameter is the value that will
	// be returned if the key does not exist (default is null).
	protected function get_meta( $key, $default=null ) {
		if ( ! is_array( $this->events ) )
			return $default;
		if ( ! array_key_exists( '_meta', $this->events ) || ! array_key_exists( $key, $this->events['_meta'] ) )
			return $default;
		return $this->events['_meta'][$key];
	}

	// Function that adds events to the array
	protected function add_event( $event_id, $start, $end, $all_day, $title, $event_url=null,
					$location=null, $summary=null, $description=null ) {
		if ( ! is_array( $this->events ) )
			$this->events = array();

		// Create an associative array 
		$this->events[$event_id] = array(
			'start' => $start, 'end' => $end, 'all_day' => $all_day,
			'title' => $title, 'url' => $event_url, 'location' => $location,
			'summary' => $summary, 'description' => $description );
	}

	// Function that returns all events fetched or cached
	public function get_events() {
		if ( ! is_array( $this->events ) )
			return null;
		$_events = $this->events;
		unset( $_events['_meta'] );
		return $_events;
	}

}

?>
