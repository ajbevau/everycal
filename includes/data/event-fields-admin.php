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
add_action( 'admin_print_footer_scripts', 'ecp1_event_wysiwyg_editor_script' );

// Function that adds extra columns to the post type
function ecp1_event_edit_columns( $columns ) {

	$columns = array(
		'title' => 'What', # Default field title
		'ecp1_dates' => 'When', # Will show From:... <br/>To: ...
		'ecp1_location' => 'Where', # Where the event is happening
		'ecp1_summary' => 'In Brief', # Brief details
	);
	
	return $columns;
}

// Function that adds values to the custom columns
function ecp1_event_custom_columns( $column ) {
	global $ecp1_event_fields;
	_ecp1_parse_event_custom();
	$datef = get_option( 'date_format' );
	$timef = get_option( 'time_format' );
	
	// act based on the column that is being rendered
	switch ( $column ) {
		
		case 'ecp1_dates':
			$allday = $ecp1_event_fields['ecp1_full_day'][0];
			$start = $ecp1_event_fields['ecp1_start_ts'][0];
			$end = $ecp1_event_fields['ecp1_end_ts'][0];
			if ( '' != $start && is_numeric( $start ) ) {
				// Output the start date
				$outstr = sprintf( '<strong>%s:</strong> %s<br/>', __( 'Start' ), date( $datef . ' ' . $timef, $start ) );
				
				// If an end date was supplied use it
				if ( '' != $end && is_numeric( $end ) ) {
					$outstr .= sprintf( '<strong>%s:</strong> %s', __( 'End' ), date( $datef . ' ' . $timef, $end ) );
				} else {
					$outstr .= __( 'No end date given.' );
				}
				
				// Note that this event runs all day if it does
				if ( 'Y' == $allday )
					$outstr .= sprintf( '<br/>%s', __( 'Running all day' ) );
			} else {
				$outstr = __( 'No start date given.' );
			}
			
			printf( $outstr );
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
			printf( '%s', htmlspecialchars( $ecp1_event_fields['ecp1_summary'][0] ) );
			break;
		
	}
}

// Function that registers a meta form box on the ecp1_event create / edit page
function ecp1_event_meta_fields() {
	add_meta_box( 'ecp1_event_meta', 'Event Details', 'ecp1_event_meta_form', 'ecp1_event', 'advanced', 'high' );
}

// Function that generates a html section for adding inside a meta fields box
function ecp1_event_meta_form() {
	global $ecp1_event_fields;
	_ecp1_parse_event_custom();
	
	// Sanitize and do security checks
	$ecp1_summary = _ecp1_event_meta_is_default( 'ecp1_summary' ) ? '' : htmlspecialchars( $ecp1_event_fields['ecp1_summary'][0] );
	$ecp1_url = _ecp1_event_meta_is_default( 'ecp1_url' ) ? '' : urldecode( $ecp1_event_fields['ecp1_url'][0] );
	$ecp1_description = _ecp1_event_meta_is_default( 'ecp1_description' ) ? '' : htmlspecialchars( $ecp1_event_fields['ecp1_description'][0] );
	$ecp1_calendar = _ecp1_event_meta_is_default( 'ecp1_calendar' ) ? '-1' : $ecp1_event_fields['ecp1_calendar'][0];
	$ecp1_full_day = _ecp1_event_meta_is_default( 'ecp1_full_day' ) ? 'N' : $ecp1_event_fields['ecp1_full_day'][0];
	$ecp1_start_date = _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ? '' : date( 'Y-m-d', $ecp1_event_fields['ecp1_start_ts'][0] );
	$ecp1_start_time = _ecp1_event_meta_is_default( 'ecp1_start_ts' ) ? '' : $ecp1_event_fields['ecp1_start_ts'][0];
	$ecp1_end_date = _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ? '' : date( 'Y-m-d', $ecp1_event_fields['ecp1_end_ts'][0] );
	$ecp1_end_time = _ecp1_event_meta_is_default( 'ecp1_end_ts' ) ? '' : $ecp1_event_fields['ecp1_end_ts'][0];
	$ecp1_location = _ecp1_event_meta_is_default( 'ecp1_location' ) ? '' : $ecp1_event_fields['ecp1_location'][0];
	// TODO: Coords
	
	// Output the meta box with a custom nonce
	// TODO: Make WYSIWYG editor for description
	// DEBUG OUTPUT:
	printf( '<pre>%s</pre>', print_r( $ecp1_event_fields, true ) );
?>
	<input type="hidden" name="ecp1_event_nonce" id="ecp1_event_nonce" value="<?php echo wp_create_nonce( 'ecp1_event_nonce' ); ?>" />
	<div class="ecp1_meta">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="ecp1_summary"><?php _e( 'Calendar' ); ?></label></th>
				<td>
					<select id="ecp1_calendar" name="ecp1_calendar" class="ecp1_select">
<?php
	// Get a list of calendars that this user has permission to edit
	$cals = array( 1 => 't', 2 => 'a', 3 => 'n' );
	foreach( $cals as $post_id=>$title ) {
		printf( '<option value="%s"%s>%s</option>', $post_id, $post_id == $ecp1_calendar ? ' selected="selected"' : '', $title );
	}
?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_summary"><?php _e( 'Summary' ); ?></label></th>
				<td><textarea id="ecp1_summary" name="ecp1_summary" class="ecp1_med"><?php echo $ecp1_summary; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_description"><?php _e( 'Event Website' ); ?></label></th>
				<td>
					<input id="ecp1_url" name="ecp1_url" type="text" class="ecp1_w100" value="<?php echo $ecp1_url; ?>" />
					<br/><strong><?php _e( 'or full description' ); ?></strong><br/>
					<textarea id="ecp1_description" name="ecp1_description" class="ecp1_big"><?php echo $ecp1_description; ?></textarea>
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
				<th scope="row"><label for="ecp1_start_date"><?php _e( 'Start' ); ?></label></th>
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
	
	// Verify the nonce just incase
	if ( ! wp_verify_nonce( $_POST['ecp1_event_nonce'], 'ecp1_event_nonce' ) )
		return $post->ID;
	
	// Verify the user can actually edit posts
	if ( ! current_user_can( 'edit_post', $post->ID ) )
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
	
	// Convert the Start Date + Time into a single UNIX time
	$ecp1_start_ts = $ecp1_event_fields['ecp1_start_ts'][1];
	if ( isset( $_POST['ecp1_start_date'] ) ) {
		// Dates should be in YYYY-MM-DD format by UI request
		$ds = date_create( $_POST['ecp1_start_date'] );
		if ( FALSE === $ds ) 
			return $post->ID;
		date_time_set( $ds, 0, 1 ); // set to just after midnight if time not given
		
		// Do we have times?
		if ( isset( $_POST['ecp1_start_time-hour'] ) && isset( $_POST['ecp1_start_time-min'] ) && 
				isset( $_POST['ecp1_start_time-ante'] ) && ( '' != $_POST['ecp1_start_time-hour'] || '' != $_POST['ecp1_start_time-min'] ) ) {
			$meridiem = $_POST['ecp1_start_time-ante'];
			$hours = isset( $_POST['ecp1_start_time-hour'] ) ? $_POST['ecp1_start_time-hour'] : 0;
			$hours = 'AM' == $meridiem ? $hours : 12 + $hours;
			$mins = isset( $_POST['ecp1_start_time-min'] ) ? $_POST['ecp1_start_time-min'] : 0;
			date_time_set( $ds, $hours, $mins );
		}
		
		// Save as a timestamp and reset the post values
		$ecp1_start_ts = date_timestamp_get( $ds );
		unset( $input['ecp1_start_date'] );
		unset( $input['ecp1_start_time-hour'] );
		unset( $input['ecp1_start_time-min'] );
		unset( $input['ecp1_start_time-ante'] );
	}
	
	// Convert the End Date + Time into a single UNIX time
	$ecp1_end_ts = $ecp1_event_fields['ecp1_end_ts'][1];
	if ( isset( $_POST['ecp1_end_date'] ) ) {
		// Dates should be in YYYY-MM-DD format by UI request
		$ds = date_create( $_POST['ecp1_end_date'] );
		if ( FALSE === $ds ) 
			return $post->ID;
		date_time_set( $ds, 23, 59 ); // set to just before midnight if time not given
		
		// Do we have times?
		if ( isset( $_POST['ecp1_end_time-hour'] ) && isset( $_POST['ecp1_end_time-min'] ) && 
				isset( $_POST['ecp1_end_time-ante'] ) && ( '' != $_POST['ecp1_end_time-hour'] || '' != $_POST['ecp1_end_time-min'] ) ) {
			$meridiem = $_POST['ecp1_end_time-ante'];
			$hours = isset( $_POST['ecp1_end_time-hour'] ) ? $_POST['ecp1_end_time-hour'] : 0;
			$hours = 'AM' == $meridiem ? $hours : 12 + $hours;
			$mins = isset( $_POST['ecp1_end_time-min'] ) ? $_POST['ecp1_end_time-min'] : 0;
			date_time_set( $ds, $hours, $mins );
		}
		
		// Save as a timestamp and reset the post values
		$ecp1_end_ts = date_timestamp_get( $ds );
		unset( $input['ecp1_end_date'] );
		unset( $input['ecp1_end_time-hour'] );
		unset( $input['ecp1_end_time-min'] );
		unset( $input['ecp1_end_time-ante'] );
	}
	
	// If no times we're given then assume all day event
	if ( _ecp1_event_no_time_given() )
		$ecp1_full_day = 'Y';
	
	// Which calendar should this event go on?
	// TODO: Make sure this is a LOCAL calendar
	$cals = array( 1 => 't', 2 => 'a', 3 => 'n' );
	$ecp1_calendar = $ecp1_event_fields['ecp1_calendar'][1];
	if ( isset( $_POST['ecp1_calendar'] ) && array_key_exists( $_POST['ecp1_calendar'], $cals ) ) {
		$ecp1_calendar = $_POST['ecp1_calendar'];
	}
	
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
			( '' == $_POST['ecp1_start_time-hour'] && '' == $_POST['ecp1_start_time-min'] ) 				// or both blank
			) && 																							// AND
			(
			( ! isset( $_POST['ecp1_end_time-hour'] ) && ! isset( $_POST['ecp1_end_time-min'] ) ) ||		// neither end given
			( '' == $_POST['ecp1_end_time-hour'] && '' == $_POST['ecp1_end_time-min'] )						// or both blank
			)
		);
}

// Prints jQuery statements into the footer scripts to initialize
// a WYSIWYG editor on the custom event description field
function ecp1_event_wysiwyg_editor_script() {
?>
	<!-- Every Calendar +1 WYSIWYG Editor Init -->
	<script type="text/javascript">/* <![CDATA[ */
		jQuery(function($) {
			if ( $('#ecp1_description').length ) {
				var elemId = $('#ecp1_description').attr('id');
				tinyMCE.execCommand('mceAddControl', false, elemId);
			}
		});
	/* ]]> */</script>
<?php
}

?>