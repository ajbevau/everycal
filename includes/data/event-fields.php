<?php
/**
 * Defines the meta fields for the event post type
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Make sure the options fields / functions have been loaded
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// An array of meta field names and default values
$ecp1_event_fields = array( 
	'ecp1_summary' => array( '', ''), // value, default
	'ecp1_description' => array( '', ''),
	'ecp1_url' => array( '', ''),
	'ecp1_start_ts' => array( '', '' ),
	'ecp1_end_ts' => array( '', '' ),
	'ecp1_calendar' => array( '', '' ),
	'ecp1_location' => array( '', '' ),
	'ecp1_coord_lat' => array( '', '' ),
	'ecp1_coord_lng' => array( '', '' ),
);

// Function to parse the custom post fields into the fields above
function _ecp1_parse_event_custom() {
	global $post, $ecp1_event_fields;
	$custom = get_post_meta( $post->ID, 'ecp1_event', true );

	// parse the custom meta fields into the value keys
	if ( is_array( $custom ) ) {
		foreach( array_keys( $ecp1_event_fields ) as $key ) {
			if ( isset( $custom[$key] ) )
				$ecp1_event_fields[$key][0] = $custom[$key];
			else
				$ecp1_event_fields[$key][0] = $ecp1_event_fields[$key][1];
		}
	} elseif ( '' == $custom ) { // it does not exist yet (reset to defaults so empty settings don't display previous events details)
		foreach( $ecp1_event_fields as $key=>$values )
			$ecp1_event_fields[$key][0] = $ecp1_event_fields[$key][1];
	} else { // if the setting exists but is something else
		printf( '<pre>%s</pre>', __( 'Every Calendar +1 plugin found non-array meta fields for this event.' ) );
	} 
}

// Function that returns true if value is default
function _ecp1_event_meta_is_default( $meta ) {
	global $ecp1_event_fields;
	if ( ! isset( $ecp1_event_fields[$meta] ) )
		return false;
	
	return $ecp1_event_fields[$meta][1] == $ecp1_event_fields[$meta][0];
}

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
	);
	
	return $columns;
}

// Function that adds values to the custom columns
function ecp1_event_custom_columns( $column ) {
	global $ecp1_event_fields;
	_ecp1_parse_event_custom();

	
	// act based on the column that is being rendered
	switch ( $column ) {
		
		case 'ecp1_dates':
			printf('dates');
			break;
		
		case 'ecp1_location':
			printf('location');
			break;
		
		case 'ecp1_summary':
			printf('summary');
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
	$ecp1_summary = 'summary';
	$ecp1_description = 'description';
	$ecp1_start_date = 'none';
	$ecp1_start_time = 'none';
	
	// Output the meta box with a custom nonce
	// DEBUG OUTPUT:
	printf( '<pre>%s</pre>', print_r( $ecp1_event_fields, true ) );
?>
	<input type="hidden" name="ecp1_event_nonce" id="ecp1_event_nonce" value="<?php echo wp_create_nonce( 'ecp1_event_nonce' ); ?>" />
	<div class="ecp1_meta">
		<table class="form-table">
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
		</table>
	</div>
<?php
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
	$ecp1_summary = '';
	if ( isset( $_POST['ecp1_summary'] ) )
		$ecp1_summary = strip_tags( $_POST['summary'] );
	
	// URL Encode the external URL
	$ecp1_url = '';
	if ( isset( $_POST['ecp1_url'] ) )
		$ecp1_url = urlencode( $_POST['ecp1_url'] ) ;
	
	// Escape any nasty in the description
	$ecp1_description = '';
	if ( isset( $_POST['ecp1_description'] ) )
		$ecp1_description = $_POST['ecp1_description'];
	
	// Convert the Start Date + Time into a single UNIX time
	$ecp1_start_ts = '';
	if ( isset( $_POST['ecp1_start_date'] ) && isset( $_POST['ecp1_start_time'] ) ) {
		// TODO: Check date / time valid
	}
	
	// Convert the End Date + Time into a single UNIX time
	$ecp1_end_ts = '';
	if ( isset( $_POST['ecp1_end_date'] ) && isset( $_POST['ecp1_end_time'] ) ) {
		// TODO: Check date / time valid
	}
	
	// Which calendar should this event go on?
	$ecp1_calendar = '';
	if ( isset( $_POST['ecp1_calendar'] ) ) {
		// TODO: Make sure this is a LOCAL calendar
	}
	
	// The location as human address and lat/long coords
	$ecp1_location = '';
	$ecp1_coord_lat = '';
	$ecp1_coord_lng = '';
	
	// Create an array to save as post meta (automatically serialized)
	$save_fields = array();
	foreach( array_keys( $ecp1_event_fields ) as $key ) {
		if ( $$key != $ecp1_event_fields[$key][1] ) // i.e. not default
			$save_fields[$key] = $$key;
	}
	
	// Save the post meta information
	update_post_meta( $post->ID, 'ecp1_event', $save_fields );
}

?>