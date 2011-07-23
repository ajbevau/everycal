<?php
/**
 * Defines the meta fields for the calendar post type
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Make sure the options fields / functions have been loaded
require_once( 'ecp1-settings.php' );

// An array of meta field names and default values
$ecp1_calendar_fields = array( 
	'ecp1_description' => array( '', ''), // value, default
	'ecp1_external_url' => array( '', 'N/A'),
	'ecp1_timezone' => array( '', '_'),
);

// Function to parse the custom post fields into the fields above
function _ecp1_parse_calendar_custom() {
	global $post, $ecp1_calendar_fields;
	$custom = get_post_meta( $post->ID, 'ecp1_calendar', true );

	// parse the custom meta fields into the value keys
	if ( is_array( $custom ) ) {
		foreach( array_keys( $ecp1_calendar_fields ) as $key ) {
			if ( isset( $custom[$key] ) )
				$ecp1_calendar_fields[$key][0] = $custom[$key];
			else
				$ecp1_calendar_fields[$key][0] = $ecp1_calendar_fields[$key][1];
		}
	} elseif ( '' == $custom ) { // it does not exist yet (reset to defaults so empty settings don't display previous calendars details)
		foreach( $ecp1_calendar_fields as $key=>$values )
			$ecp1_calendar_fields[$key][0] = $ecp1_calendar_fields[$key][1];
	} else { // if the setting exists but is something else
		printf( '<pre>%s</pre>', __( 'Every Calendar +1 plugin found non-array meta fields for this calendar.' ) );
	} 
}

// Function that returns true if value is default
function _ecp1_calendar_meta_is_default( $meta ) {
	global $ecp1_calendar_fields;
	if ( ! isset( $ecp1_calendar_fields[$meta] ) )
		return false;
	
	return $ecp1_calendar_fields[$meta][1] == $ecp1_calendar_fields[$meta][0];
}

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
	global $ecp1_calendar_fields;
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
				try {
					$dtz = new DateTimeZone( $ecp1_calendar_fields['ecp1_timezone'][0] );
					printf ( '%s', $dtz->getName() );
				} catch( Exception $tzmiss ) {
					// not a valid timezone
					printf ( '<span class="ecp1_error">%s</span>', __( 'Timezone is invalid' ) );
				}				
			}
			
			break;
		
	}
}

// Function that registers a meta form box on the ecp1_calendar create / edit page
function ecp1_calendar_meta_fields() {
	add_meta_box( 'ecp1_calendar_meta', 'Calendar Settings', 'ecp1_calendar_meta_form', 'ecp1_calendar', 'advanced', 'high' );
}

// Function that generates a html section for adding inside a meta fields box
function ecp1_calendar_meta_form() {
	global $ecp1_calendar_fields;
	_ecp1_parse_calendar_custom();
	
	// Sanitize and do security checks
	$ecp1_desc = _ecp1_calendar_meta_is_default( 'ecp1_description' ) ? '' : htmlspecialchars( $ecp1_calendar_fields['ecp1_description'][0] );
	$ecp1_url = _ecp1_calendar_meta_is_default( 'ecp1_external_url' ) ? '' : urldecode( $ecp1_calendar_fields['ecp1_external_url'][0] );
	$ecp1_tz = _ecp1_calendar_meta_is_default( 'ecp1_timezone' ) ? '_' : $ecp1_calendar_fields['ecp1_timezone'][0];
	
	// Output the meta box with a custom nonce
?>
	<input type="hidden" name="ecp1_calendar_nonce" id="ecp1_calendar_nonce" value="<?php echo wp_create_nonce( 'ecp1_calendar_nonce' ); ?>" />
	<div class="ecp1_meta">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="ecp1_description"><?php _e( 'Description' ); ?></label></th>
				<td><textarea id="ecp1_description" name="ecp1_description" class="ecp1_big"><?php echo $ecp1_desc; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_external_url"><?php _e( 'External URL' ); ?></label></th>
				<td><input id="ecp1_external_url" name="ecp1_external_url" type="text" class="ecp1_url" value="<?php echo $ecp1_url; ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ecp1_timezone"><?php _e( 'Timezone' ); ?></label></th>
				<td>
<?php
	// Check if local calendars can change event timezones
	$disabled_str = _ecp1_get_option( 'tz_change' ) ? null : 'disabled="disabled"';
	echo _ecp1_timezone_select( 'ecp1_timezone', $ecp1_tz, $disabled_str );
	if ( ! is_null( $disabled_str ) )
		printf( '<em>%s</em>', __( 'Every Calendar +1 settings prevent TZ change.' ) );
?>
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
	
	// Verify the nonce just incase
	if ( ! wp_verify_nonce( $_POST['ecp1_calendar_nonce'], 'ecp1_calendar_nonce' ) )
		return $post->ID;
	
	// Verify the user can actually edit posts
	if ( ! current_user_can( 'edit_post', $post->ID ) )
		return $post->ID;
	
	// URL Encode the external URL
	$ecp1_external_url = '';
	if ( isset( $_POST['ecp1_external_url'] ) )
		$ecp1_external_url = urlencode( $_POST['ecp1_external_url'] ) ;
	
	// Escape any nasty in the description
	$ecp1_description = '';
	if ( isset( $_POST['ecp1_description'] ) )
		$ecp1_description = $_POST['ecp1_description'];
	
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
