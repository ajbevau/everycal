<?php
/**
 * Defines admin hooks for managaging the meta fields for the event post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Make sure the plugin settings and event fields have been loaded
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );
require_once( ECP1_DIR . '/includes/data/event-fields.php' );

// Add filters and hooks to add columns and display them
add_filter( 'manage_edit-ecp1_event_columns', 'ecp1_event_edit_columns' );
add_action( 'manage_posts_custom_column', 'ecp1_event_custom_columns' );
add_action( 'admin_init', 'ecp1_event_meta_fields' );

// Function that adds extra columns to the post type
function ecp1_event_edit_columns( $columns ) {

	$columns = array(
		'title' => 'What', # Default field title
		'ecp1_dates' => 'When', # Will show From:... <br/>To: ...
		'ecp1_location' => 'Where', # Where the event is happening
		'ecp1_summary' => 'In Brief', # Brief details
		'author' => 'Author',
	);
	
	return $columns;
}

// Function that adds values to the custom columns
function ecp1_event_custom_columns( $column ) {
	global $ecp1_event_fields, $post_type;

	// Only do this if we're loading events
	if ( 'ecp1_event' != $post_type )
		return;

	// Make sure the meta event is loaded and get time fomatting ready
	_ecp1_parse_event_custom();
	$datef = get_option( 'date_format' );
	$timef = get_option( 'time_format' );
	$tz = new DateTimeZone( $ecp1_event_fields['_meta']['calendar_tz'] );
	
	// act based on the column that is being rendered
	switch ( $column ) {

		case 'ecp1_dates':
			try {
				$allday = $ecp1_event_fields['ecp1_full_day'][0];
				$start = $ecp1_event_fields['ecp1_start_ts'][0];
				$end = $ecp1_event_fields['ecp1_end_ts'][0];

				if ( '' != $start && is_numeric( $start ) ) {
					// Output the start date
					$start = new DateTime( "@$start" );
					$outstr = sprintf( '<strong>%s:</strong> %s<br/>', __( 'Start' ), 
							$start->setTimezone( $tz )->format( $datef . ' ' . $timef ) );
				
					// If an end date was supplied use it
					if ( '' != $end && is_numeric( $end ) ) {
						$end = new DateTime( "@$end" );
						$outstr .= sprintf( '<strong>%s:</strong> %s', __( 'End' ), 
								$end->setTimezone( $tz )->format( $datef . ' ' . $timef ) );
					} else {
						$outstr .= __( 'No end date given.' );
					}
				
					// Note that this event runs all day if it does
					if ( 'Y' == $allday )
						$outstr .= sprintf( '<br/>%s', __( 'Running all day' ) );
				} else {
					$outstr = __( 'No start date given.' );
				}
			} catch( Exception $tserror ) {
				$outstr = __( 'Invalid date stored in database, please correct it.' );
			}
			
			printf( '%s<br/>%s', $outstr, ecp1_timezone_display( $tz->getName() ) );
			break;
		
		case 'ecp1_location':
			$outstr = htmlspecialchars( $ecp1_event_fields['ecp1_location'][0] );
			if ( ! _ecp1_event_meta_is_default( 'ecp1_coord_lat' ) || ! _ecp1_event_meta_is_default( 'ecp1_coord_lng' ) ) {
				$outstr .= sprintf('<br/><em>%s:</em> %s', __( 'Lat' ), $ecp1_event_fields['ecp1_coord_lat'][0] );
				$outstr .= sprintf('<br/><em>%s:</em> %s', __( 'Long' ), $ecp1_event_fields['ecp1_coord_lng'][0] );
			}
			
			printf( $outstr );
			break;
		
		case 'ecp1_summary':
			if ( ! _ecp1_event_meta_is_default( 'ecp1_featured' ) && 'Y' == _ecp1_event_meta( 'ecp1_featured' ) )
				printf( '<strong>%s</strong><br/>', __( 'Feature Event' ) );
			printf( '%s', htmlspecialchars( $ecp1_event_fields['ecp1_summary'][0] ) );
			break;
		
	}
}

// Function that registers a meta form box on the ecp1_event create / edit page
function ecp1_event_meta_fields() {
	add_meta_box( 'ecp1_event_meta', 'Event Details', 'ecp1_event_meta_form', 'ecp1_event', 'normal', 'high' );
}

// Function that generates a html section for adding inside a meta fields box
function ecp1_event_meta_form() {
	global $ecp1_event_fields;

	// Load a list of calendars this user has access to
	$calendars = _ecp1_current_user_calendars();

	// The user must be able to edit the calendar to put events on it
	// Note the calendar edit level can be a contributor who's calendar
	// post edits would need to be on their own cals + review approved
	if ( 0 == count( $calendars ) ) {
		printf( '<div class="ecp1_error">%s</div>', __( 'No calendars found! Please create a calendar first.' ) );
		return;
	}
	
	// Load the event meta data from the database
	_ecp1_parse_event_custom();
	
	// Sanitize and do security checks
	$ecp1_summary = _ecp1_event_meta_is_default( 'ecp1_summary' ) ? '' : $ecp1_event_fields['ecp1_summary'][0];
	$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? '' : urldecode( $ecp1_event_fields['ecp1_url'][0] );
	$ecp1_description = _ecp1_event_meta_is_default( 'ecp1_description' ) ? '' : $ecp1_event_fields['ecp1_description'][0];
	$ecp1_calendar = _ecp1_event_meta_is_default( 'ecp1_calendar' ) ? '-1' : $ecp1_event_fields['ecp1_calendar'][0];
	$ecp1_full_day = _ecp1_event_meta_is_default( 'ecp1_full_day' ) ? 'N' : $ecp1_event_fields['ecp1_full_day'][0];
	$ecp1_featured = _ecp1_event_meta_is_default( 'ecp1_featured' ) ? 'N' : $ecp1_event_fields['ecp1_featured'][0];
	$ecp1_location = _ecp1_event_meta_is_default( 'ecp1_location' ) ? '' : $ecp1_event_fields['ecp1_location'][0];
	// TODO: Coords

	$ecp1_start_date = _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ? '' : date( 'Y-m-d', $ecp1_event_fields['ecp1_start_ts'][0] );
	$ecp1_start_time = _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ? '' : $ecp1_event_fields['ecp1_start_ts'][0];
	$ecp1_end_date = _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ? '' : date( 'Y-m-d', $ecp1_event_fields['ecp1_end_ts'][0] );
	$ecp1_end_time = _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ? '' : $ecp1_event_fields['ecp1_end_ts'][0];

	// The current calendars timezone
	$tz = new DateTimeZone( $ecp1_event_fields['_meta']['calendar_tz'] );

	// Load the start date/time if possible
	$ecp1_start_date = $ecp1_start_time = '';
	if ( ! _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ) {
		try {
			$d = new DateTime( '@' . $ecp1_event_fields['ecp1_start_ts'][0] );
			$d->setTimezone( $tz );
			$ecp1_start_date = $d->format( 'Y-m-d' );
			$ecp1_start_time = $d->getTimestamp() + $d->getOffset(); // format( 'U' ) and timestamp are NOT offset by TZ
		} catch( Exception $serror ) {
			$ecp1_start_date = $ecp1_start_time = '';
			printf( '<div class="ecp1_error">%s</div>', __( 'ERROR: Could not parse start date/time please re-enter.' ) );
		}
	}

	// Load the end date/time if possible
	$ecp1_end_date = $ecp1_end_time = '';
	if ( ! _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ) {
		try {
			$d = new DateTime( '@' . $ecp1_event_fields['ecp1_end_ts'][0] );
			$d->setTimezone( $tz );
			$ecp1_end_date = $d->format( 'Y-m-d' );
			$ecp1_end_time = $d->getTimestamp() + $d->getOffset(); // format( 'U' ) and timestamp are NOT offset by TZ
		} catch( Exception $eerror ) {
			$ecp1_end_date = $ecp1_end_time = '';
			printf( '<div class="ecp1_error">%s</div>', __( 'ERROR: Could not parse end date/time please re-enter.' ) );
		}
	}

	// If the calendar selected is not editable by the user then they're cheating
	if ( ! _ecp1_event_meta_is_default( 'ecp1_calendar' ) && ! current_user_can( 'edit_' . ECP1_CALENDAR_CAP, $ecp1_calendar ) )
		wp_die( __( 'You can not change event details on a calendar you are not allowed to edit.' ) );
	
	// Output the meta box with a custom nonce
?>
	<input type="hidden" name="ecp1_event_nonce" id="ecp1_event_nonce" value="<?php echo wp_create_nonce( 'ecp1_event_nonce' ); ?>" />
	<div class="ecp1_meta">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="ecp1_calendar"><?php _e( 'Calendar' ); ?></label></th>
				<td>
					<select id="ecp1_calendar" name="ecp1_calendar" class="ecp1_select">
						<option value=""></option>
<?php
	// Iterate over the calendar list and print options
	foreach( $calendars as $cal ) {
		printf( '<option value="%s"%s>%s</option>', $cal->ID, $cal->ID == $ecp1_calendar ? ' selected="selected"' : '', $cal->post_title );
	}
?>
					</select>
					<span class="ecp1_floater_r">
						<input type="checkbox" id="ecp1_featured" name="ecp1_featured" value="1" <?php checked( 'Y', $ecp1_featured ); ?> />
						<label for="ecp1_featured"><strong><?php _e( 'Feature Event?' ); ?></strong></label>
						<br/><?php _e( 'Feature events can appear on other calendars (e.g. a global calendar).' ); ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_summary"><?php _e( 'Summary' ); ?></label></th>
				<td><textarea id="ecp1_summary" name="ecp1_summary" class="ecp1_med"><?php echo $ecp1_summary; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_url"><?php _e( 'Event Website' ); ?></label></th>
				<td>
					<input id="ecp1_url" name="ecp1_url" type="text" class="ecp1_w100" value="<?php echo $ecp1_url; ?>" />
					<br/><strong><?php _e( 'and / or full description' ); ?></strong><br/>
					<!-- Copied from WordPress wp-admin/edit-form-advanced.php -->
					<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
					<?php the_editor( $ecp1_description, 'ecp1_description' ); ?>
					<table id="post-status-info" cellspacing="0"><tbody><tr>
							<td id="wp-word-count"><?php printf( __( 'Word count: %s' ), '<span class="word-count">0</span>' ); ?></td>
							<td class="autosave-info">
							<span class="autosave-message">&nbsp;</span>
<?php
	if ( 'auto-draft' != $post->post_status ) {
			echo '<span id="last-edit">';
			if ( $last_id = get_post_meta( $post_ID, '_edit_last', true ) ) {
					$last_user = get_userdata( $last_id );
					printf( __( 'Last edited by %1$s on %2$s at %3$s' ), esc_html( $last_user->display_name ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
			} else {
					printf( __( 'Last edited on %1$s at %2$s' ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
			}
			echo '</span>';
	}
?>
							</td>
					</tr></tbody></table>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_start_date"><?php _e( 'Start' ); ?></label></th>
				<td>
					<input id="ecp1_start_date" name="ecp1_start_date" type="text" class="ecp1_datepick" value="<?php echo $ecp1_start_date; ?>" />
					<?php echo _ecp1_time_select_trio( 'ecp1_start_time', $ecp1_start_time ); ?>
					<label for="ecp1_full_day"><?php _e( 'Full day event?' ); ?></label>
						<input id="ecp1_full_day" name="ecp1_full_day" type="checkbox" value="1" <?php checked( 'Y', $ecp1_full_day ); ?>/><br/>
					<em>Please enter date as YYYY-MM-DD or use the date picker</em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_end_date"><?php _e( 'Finish' ); ?></label></th>
				<td>
					<input id="ecp1_end_date" name="ecp1_end_date" type="text" class="ecp1_datepick" value="<?php echo $ecp1_end_date; ?>" />
					<?php echo _ecp1_time_select_trio( 'ecp1_end_time', $ecp1_end_time ); ?><br/>
					<em>Please enter date as YYYY-MM-DD or use the date picker</em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_location"><?php _e( 'Location' ); ?></label></th>
				<td>
					<input id="ecp1_location" name="ecp1_location" type="text" class="ecp1_w100" value="<?php echo $ecp1_location; ?>" />
<?php
	// If maps are supported then we need to render a container div and some controls
	if ( _ecp1_get_option( 'use_maps' ) ) {
		// TODO: Want to be able to pick a point / geocode etc...
		printf( '<br/>TODO: Add support for map rendering here + geocoding...' );
	}
?>
				</td>
			</tr>
		</table>
	</div>
<?php
	// DEBUG OUTPUT:
	//printf( '<pre>%s</pre>', print_r( $ecp1_event_fields, true ) );
}

// Returns a string of HH:MM:AM/PM select boxes for time entry
function _ecp1_time_select_trio( $base_key, $select_value_ts ) {
	$select_hours = $select_mins = $select_meridiem = '';
	if ( '' != $select_value_ts ) {
		$select_hours = date( 'g', $select_value_ts );
		$select_mins = date( 'i', $select_value_ts );
		$select_meridiem = date( 'A', $select_value_ts );
	}
	
	$outstr = sprintf( '<select id="%s-hour" name="%s-hour"><option value=""></option>', $base_key, $base_key );
	for( $i=1; $i<=12; $i++ )
		$outstr .= sprintf( '<option value="%s"%s>%s</option>', $i, $i == $select_hours ? ' selected="selected"' : '', $i );
	$outstr .= sprintf( '</select><select id="%s-min" name="%s-min"><option value=""></option>', $base_key, $base_key );
	for( $i=0; $i<=59; $i++ ) {
		$display_i = $i < 10 ? '0' . $i : $i;
		$outstr .= sprintf( '<option value="%s"%s>%s</option>', $display_i, $display_i == $select_mins ? ' selected="selected"' : '', $display_i );
	}
	$outstr .= sprintf( '</select><select id="%s-ante" name="%s-ante">', $base_key, $base_key );
	foreach( array( 'AM' => __( 'AM' ), 'PM' => __( 'PM' ) ) as $ante=>$title )
		$outstr .= sprintf( '<option value="%s"%s>%s</option>', $ante, $ante == $select_meridiem ? ' selected="selected"' : '', $title );
	$outstr .= '</select>';
	return $outstr;
}

// Save the data when the meta box is submitted
add_action( 'save_post', 'ecp1_event_save' );
function ecp1_event_save() {
	global $post, $ecp1_event_fields;
	if ( 'revision' == $post->post_type )
		return; // don't update on revisions
	if ( 'ecp1_event' != $post->post_type )
		return; // don't update non-events
	
	// Verify the nonce just incase
	if ( ! wp_verify_nonce( $_POST['ecp1_event_nonce'], 'ecp1_event_nonce' ) )
		return $post->ID;
	
	// Verify the user can actually edit posts
	if ( ! current_user_can( 'edit_' . ECP1_EVENT_CAP, $post->ID ) )
		return $post->ID;

	// Escape any nasty in the summary (it's meant to be HTML free)
	$ecp1_summary = $ecp1_event_fields['ecp1_summary'][1];
	if ( isset( $_POST['ecp1_summary'] ) )
		$ecp1_summary = wp_filter_nohtml_kses( $_POST['ecp1_summary'] );
	
	// URL Encode the external URL
	$ecp1_url = $ecp1_event_fields['ecp1_url'][1];
	if ( isset( $_POST['ecp1_url'] ) )
		$ecp1_url = urlencode( $_POST['ecp1_url'] ) ;
	
	// Escape any nasty in the description
	$ecp1_description = $ecp1_event_fields['ecp1_description'][1];
	if ( isset( $_POST['ecp1_description'] ) )
		$ecp1_description = wp_filter_post_kses( $_POST['ecp1_description'] );
	
	// Is this a full day event?
	$ecp1_full_day = $ecp1_event_fields['ecp1_full_day'][1];
	if ( isset( $_POST['ecp1_full_day'] ) && '1' == $_POST['ecp1_full_day'] ) {
		$ecp1_full_day = 'Y';
	}

	// Is this a featured event?
	$ecp1_featured = $ecp1_event_fields['ecp1_featured'][1];
	if ( isset( $_POST['ecp1_featured'] ) && '1' == $_POST['ecp1_featured'] ) {
		$ecp1_featured = 'Y';
	}

	// Which calendar should this event go on?
	$ecp1_calendar = isset( $_POST['ecp1_calendar'] ) ? $_POST['ecp1_calendar'] : $ecp1_event_fields['ecp1_calendar'][1];
	if ( $ecp1_event_fields['ecp1_calendar'][1] != $ecp1_calendar ) {
		// If the calendar was set then check the user can edit it
		if ( ! current_user_can( 'edit_' . ECP1_CALENDAR_CAP, $ecp1_calendar ) )
			$ecp1_calendar = $ecp1_event_fields['ecp1_calendar'][1];
	}
	
	// Load the calendar so we can convert times to UTC
	_ecp1_parse_calendar_custom( $ecp1_calendar );
	$calendar_tz = new DateTimeZone( ecp1_get_calendar_timezone() ); // UTC if error
	
	// Convert the Start Date + Time into a single UNIX time
	$ecp1_start_ts = $ecp1_event_fields['ecp1_start_ts'][1];
	if ( isset( $_POST['ecp1_start_date'] ) ) {
		// Dates should be in YYYY-MM-DD format by UI request
		$ds = date_create( $_POST['ecp1_start_date'], $calendar_tz );
		if ( FALSE === $ds ) // used procedural so don't have to catch exception
			return $post->ID;
		$ds->setTime( 0, 0, 1 ); // set to just after midnight if time not given
		
		// Do we have times?
		if ( isset( $_POST['ecp1_start_time-hour'] ) && isset( $_POST['ecp1_start_time-min'] ) && 
				isset( $_POST['ecp1_start_time-ante'] ) && ( '' != $_POST['ecp1_start_time-hour'] || '' != $_POST['ecp1_start_time-min'] ) ) {
			$meridiem = isset( $_POST['ecp1_start_time-ante'] ) ? $_POST['ecp1_start_time-ante'] : 'AM';
			$hours = isset( $_POST['ecp1_start_time-hour'] ) ? $_POST['ecp1_start_time-hour'] : 0;
			$hours = 'AM' == $meridiem ? (12 == $hours ? 0 : $hours) : 12 + $hours; // convert to 24hr for setting time
			$mins = isset( $_POST['ecp1_start_time-min'] ) ? $_POST['ecp1_start_time-min'] : 0;
			$ds->setTime( $hours, $mins, 0 ); // 0 to undo the 1s above
		}
		
		// Save as a timestamp and reset the post values
		$ecp1_start_ts = $ds->getTimestamp(); // UTC (i.e. without offset)
		unset( $input['ecp1_start_date'] );
		unset( $input['ecp1_start_time-hour'] );
		unset( $input['ecp1_start_time-min'] );
		unset( $input['ecp1_start_time-ante'] );
	}
	
	// Convert the End Date + Time into a single UNIX time
	$ecp1_end_ts = $ecp1_event_fields['ecp1_end_ts'][1];
	if ( isset( $_POST['ecp1_end_date'] ) ) {
		// Dates should be in YYYY-MM-DD format by UI request
		$ds = date_create( $_POST['ecp1_end_date'], $calendar_tz );
		if ( FALSE === $ds ) // used procedural so don't have to catch exception
			return $post->ID;
		$ds->setTime( 23, 59, 59 ); // set to just before midnight if time not given
		
		// Do we have times?
		if ( isset( $_POST['ecp1_end_time-hour'] ) && isset( $_POST['ecp1_end_time-min'] ) && 
				isset( $_POST['ecp1_end_time-ante'] ) && ( '' != $_POST['ecp1_end_time-hour'] || '' != $_POST['ecp1_end_time-min'] ) ) {
			$meridiem = isset( $_POST['ecp1_end_time-ante'] ) ? $_POST['ecp1_end_time-ante'] : 'AM';
			$hours = isset( $_POST['ecp1_end_time-hour'] ) ? $_POST['ecp1_end_time-hour'] : 0;
			$hours = 'AM' == $meridiem ? (12 == $hours ? 0 : $hours) : 12 + $hours; // convert to 24hr time
			$mins = isset( $_POST['ecp1_end_time-min'] ) ? $_POST['ecp1_end_time-min'] : 0;
			$ds->setTime( $hours, $mins, 0 ); // 0 to undo the 59s above
		}
		
		// Save as a timestamp and reset the post values
		$ecp1_end_ts = $ds->getTimestamp(); // UTC (i.e. without offset)
		unset( $input['ecp1_end_date'] );
		unset( $input['ecp1_end_time-hour'] );
		unset( $input['ecp1_end_time-min'] );
		unset( $input['ecp1_end_time-ante'] );
	}
	
	// If no times we're given then assume all day event
	if ( _ecp1_event_no_time_given() )
		$ecp1_full_day = 'Y';
	
	// The location as human address and lat/long coords
	// TODO: Add support for coord lat / lng
	$ecp1_location = $ecp1_event_fields['ecp1_location'][1];
	if ( isset( $_POST['ecp1_location'] ) )
		$ecp1_location = $_POST['ecp1_location'];
	$ecp1_coord_lat = '';
	$ecp1_coord_lng = '';
	
	// Create an array to save as post meta (automatically serialized)
	$save_fields_group = array();
	$save_fields_alone = array();
	foreach( array_keys( $ecp1_event_fields ) as $key ) {
		if ( $$key != $ecp1_event_fields[$key][1] ) { // only where the value is NOT default
			if ( ! array_key_exists( $key, $ecp1_event_fields['_meta']['standalone'] ) ) {
				// for all fields NOT in _meta['standalone'] save as a serialized array
				$save_fields_group[$key] = $$key;
			} else {
				// for fields in _meta['standalone'] store to be saved separately
				// remember _meta['standalone'] = array( $ecp1_event_fields key => postmeta table key )
				// basically rename the fields key to the database key and write value for saving
				$save_fields_alone[$ecp1_event_fields['_meta']['standalone'][$key]] = $$key;
			}
		}
	}
	
	// Save the post meta information
	update_post_meta( $post->ID, 'ecp1_event', $save_fields_group );
	foreach( $save_fields_alone as $key=>$value )
		update_post_meta( $post->ID, $key, $value );
}

// Returns true if NO time given on start and finish date
function _ecp1_event_no_time_given() {
	// this is ugly but it does the job
	return (
			(
			( ! isset( $_POST['ecp1_start_time-hour'] ) && ! isset( $_POST['ecp1_start_time-min'] ) ) ||	// neither start given
			( '' == $_POST['ecp1_start_time-hour'] && '' == $_POST['ecp1_start_time-min'] ) 		// or both blank
			) && 																							// AND
			(
			( ! isset( $_POST['ecp1_end_time-hour'] ) && ! isset( $_POST['ecp1_end_time-min'] ) ) ||		// neither end given
			( '' == $_POST['ecp1_end_time-hour'] && '' == $_POST['ecp1_end_time-min'] )				// or both blank
			)
		);
}

?>
