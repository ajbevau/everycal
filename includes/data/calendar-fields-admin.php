<?php
/**
 * Defines admin hooks for managaging the meta fields for the calendar post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Make sure the plugin settings and calendar fields have been loaded
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );
require_once( ECP1_DIR . '/includes/data/calendar-fields.php' );

// Add filters and hooks to add columns and display them
add_filter( 'manage_edit-ecp1_calendar_columns', 'ecp1_calendar_edit_columns' );
add_action( 'manage_posts_custom_column', 'ecp1_calendar_custom_columns' );
add_action( 'admin_init', 'ecp1_calendar_meta_fields' );

// Function that adds extra columns to the post type
function ecp1_calendar_edit_columns( $columns ) {

	$columns = array(
		'title' => 'Name', # Default field title
		'ecp1_cal_description' => 'Description', # Will show description<br/>url
		'ecp1_tz' => 'Timezone', # Calendar timezone
	);
	
	return $columns;
}

// Function that adds values to the custom columns
function ecp1_calendar_custom_columns( $column ) {
	global $ecp1_calendar_fields, $post_type;
	
	// Only do this if post type is calendar
	if ( 'ecp1_calendar' != $post_type )
		return;

	// Make sure the calendar meta is loaded
	_ecp1_parse_calendar_custom();
	
	// act based on the column that is being rendered
	switch ( $column ) {
		
		case 'ecp1_cal_description':
			if ( ! _ecp1_calendar_meta_is_default( 'ecp1_description' ) ) 
				printf( '%s<br/>', htmlspecialchars( $ecp1_calendar_fields['ecp1_description'][0] ) );

			if ( _ecp1_calendar_meta_is_default( 'ecp1_external_url' ) )
				printf( '<strong>%s</strong>', __( 'Local calendar' ) );
			else
				printf( '<strong>%s</strong>: %s', __( 'From' ), urldecode( $ecp1_calendar_fields['ecp1_external_url'][0] ) );

			break;
		
		case 'ecp1_tz':
			if ( _ecp1_calendar_meta_is_default( 'ecp1_timezone' ) ) {
				printf( '%s', __( 'WordPress Default' ) );
			} else {
				printf( '%s', ecp1_timezone_display( $ecp1_calendar_fields['ecp1_timezone'][0] ) );
			}
			
			break;
		
	}
}

// Function that registers a meta form box on the ecp1_calendar create / edit page
function ecp1_calendar_meta_fields() {
	global $post_type;
	add_meta_box( 'ecp1_calendar_meta', 'Calendar Settings', 'ecp1_calendar_meta_form', 'ecp1_calendar', 'normal', 'high' );
}

// Function that generates a html section for adding inside a meta fields box
function ecp1_calendar_meta_form() {
	global $ecp1_calendar_fields;
	
	// Make sure the meta is loaded
	_ecp1_parse_calendar_custom();
	
	// Sanitize and do security checks
	$ecp1_desc = _ecp1_calendar_meta_is_default( 'ecp1_description' ) ? '' : htmlspecialchars( $ecp1_calendar_fields['ecp1_description'][0] );
	$ecp1_url = _ecp1_calendar_meta_is_default( 'ecp1_external_url' ) ? '' : urldecode( $ecp1_calendar_fields['ecp1_external_url'][0] );
	$ecp1_tz = _ecp1_calendar_meta_is_default( 'ecp1_timezone' ) ? '_' : $ecp1_calendar_fields['ecp1_timezone'][0];
	$ecp1_defview = _ecp1_calendar_meta_is_default( 'ecp1_default_view' ) ? '' : $ecp1_calendar_fields['ecp1_default_view'][0];
	$ecp1_first_day = _ecp1_calendar_meta_is_default( 'ecp1_first_day' ) ? '-1' : $ecp1_calendar_fields['ecp1_first_day'][0];
	
	// Output the meta box with a custom nonce
?>
	<input type="hidden" name="ecp1_calendar_nonce" id="ecp1_calendar_nonce" value="<?php echo wp_create_nonce( 'ecp1_calendar_nonce' ); ?>" />
	<div class="ecp1_meta">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="ecp1_description"><?php _e( 'Description' ); ?></label></th>
				<td><textarea id="ecp1_description" name="ecp1_description" class="ecp1_big"><?php echo $ecp1_desc; ?></textarea></td>
			</tr>
<?php
	// Check if external calendars are enabled
	if ( _ecp1_get_option( 'use_external_cals' ) ) {
?>
			<tr valign="top">
				<th scope="row"><label for="ecp1_external_url"><?php _e( 'External URL' ); ?></label></th>
				<td><input id="ecp1_external_url" name="ecp1_external_url" type="text" class="ecp1_url" value="<?php echo $ecp1_url; ?>" /></td>
			</tr>
<?php
	}
?>
			<tr valign="top">
				<th scope="row"><label for="ecp1_timezone"><?php _e( 'Timezone' ); ?></label></th>
				<td>
<?php
	// Check if local calendars can change event timezones
	$disabled_str = _ecp1_get_option( 'tz_change' ) ? 'class="ecp1_select"' : 'class="ecp1_select" disabled="disabled"';
	echo _ecp1_timezone_select( 'ecp1_timezone', $ecp1_tz, $disabled_str );
	if ( ! _ecp1_get_option( 'tz_change' ) )
		printf( '<em>%s</em>', __( 'Every Calendar +1 settings prevent change: WordPress TZ will be used.' ) );
	if ( '' == get_option( 'timezone_string' ) )
		printf( '<br/><strong>%s</strong>', __( 'If you are using your WordPress Timezone: Please consider setting a city in the WordPress timezone settings, otherwise Every Calendar will not be able to adjust your event times for day light savings.' ) );
?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_default_view"><?php _e( 'Default View' ); ?></label></th>
				<td>
					<input id="ecp1_default_view-month" type="radio"  name="ecp1_default_view" value="month" <?php checked( 'month', $ecp1_defview ); ?>/><label for="ecp1_default_view-month"><?php _e( 'Month' ); ?></label>
					<input id="ecp1_default_view-week" type="radio" name="ecp1_default_view" value="week" <?php checked( 'week', $ecp1_defview ); ?>/><label for="ecp1_default_view-week"><?php _e( 'Week' ); ?></label>
					<input id="ecp1_default_view-day" type="radio" name="ecp1_default_view" value="day" <?php checked( 'day', $ecp1_defview ); ?>/><label for="ecp1_default_view-day"><?php _e( 'Day' ); ?></label><br/>
					<label for="ecp1_first_day"><?php _e( 'First day of the week:' ); ?></label><select id="ecp1_first_day" name="ecp1_first_day"><option value="-1"><?php _e( 'WordPress Default' );?></option>
<?php
	// Loop over the days of the week
	foreach( array(
		0 => __( 'Sunday' ),
		1 => __( 'Monday' ),
		2 => __( 'Tuesday' ),
		3 => __( 'Wednesday' ),
		4 => __( 'Thursday' ),
		5 => __( 'Friday' ),
		6 => __( 'Saturday' ) ) as $id=>$day ) {
		printf( '<option value="%s"%s>%s</option>', $id, $id == $ecp1_first_day ? ' selected="selected"' : '', $day );
		}
?>
					</select>
				</td>
			</tr>
		</table>
	</div>
<?php
}

// Save the data when the meta box is submitted
add_action( 'save_post', 'ecp1_calendar_save' );
function ecp1_calendar_save() {
	global $post, $ecp1_calendar_fields;
	if ( 'revision' == $post->post_type )
		return; // don't update on revisions
	if ( 'ecp1_calendar' != $post->post_type )
		return; // don't update non calendars
	
	// Verify the nonce just incase
	if ( ! wp_verify_nonce( $_POST['ecp1_calendar_nonce'], 'ecp1_calendar_nonce' ) )
		return $post->ID;
	
	// Verify the user can actually edit posts
	if ( ! current_user_can( 'edit_ecp1_calendar', $post->ID ) )
		return $post->ID;
	
	// URL Encode the external URL
	$ecp1_external_url = '';
	if ( isset( $_POST['ecp1_external_url'] ) )
		$ecp1_external_url = urlencode( $_POST['ecp1_external_url'] ) ;
	
	// Escape any nasty in the description
	$ecp1_description = '';
	if ( isset( $_POST['ecp1_description'] ) )
		$ecp1_description = wp_filter_post_kses( $_POST['ecp1_description'] );
	
	// Verify the timezone is valid if not error out
	$ecp1_timezone = '';
	if ( isset( $_POST['ecp1_timezone'] ) ) {
		if ( '_' == $_POST['ecp1_timezone'] ) {
			$ecp1_timezone = $_POST['ecp1_timezone'];
		} else {
			try {
				$dtz = new DateTimeZone( $_POST['ecp1_timezone'] );
				$ecp1_timezone = $dtz->getName();
			} catch( Exception $tzmiss ) {
				return $post->ID;
			}
		}
	}

	// Verify month|week|day is the value for default view
	$ecp1_default_view = 'none';
	if ( isset( $_POST['ecp1_default_view'] ) &&
			in_array( $_POST['ecp1_default_view'], array( 'month', 'week', 'day' ) ) ) {
		$ecp1_default_view = $_POST['ecp1_default_view'];
	}
	
	// Week start day should be 0<=X<=6
	$ecp1_first_day = -1;
	if ( isset( $_POST['ecp1_first_day'] ) && is_numeric( $_POST['ecp1_first_day'] ) &&
			( 0 <= $_POST['ecp1_first_day'] && $_POST['ecp1_first_day'] <= 6 ) ) {
		$ecp1_first_day = intval( $_POST['ecp1_first_day'] );
	}
	
	// Create an array to save as post meta (automatically serialized)
	$save_fields = array();
	foreach( array_keys( $ecp1_calendar_fields ) as $key ) {
		if ( $$key != $ecp1_calendar_fields[$key][1] ) // i.e. not default
			$save_fields[$key] = $$key;
	}
	
	// Save the post meta information
	update_post_meta( $post->ID, 'ecp1_calendar', $save_fields );
}

?>
