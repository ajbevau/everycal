<?php
/**
 * Defines the meta fields for the calendar post type
 */

// Make sure we're included from within the plugin
require( '../check-ecp1-defined.php' );

// Add filters and hooks to add columns and display them
add_filter( 'manage_edit-ecp1_calendar_columns', 'ecp1_calendar_edit_columns' );
add_action( 'manage_posts_custom_column', 'ecp1_calendar_custom_columns' );
add_action( 'admin_init', 'ecp1_calendar_meta_fields' );

// Function that adds extra columns to the post type
function tf_events_edit_columns( $columns ) {

	$columns = array(
		'title' => 'Name', # Default field title
		'ecp1_cal_description' => 'Description' # Will show description<br/>url
	);
	
	return $columns;
}

// Function that adds values to the custom columns
function ecp1_calendar_custom_columns( $column ) {
	global $post;
	$custom = get_post_custom();
	
	// act based on the column that is being rendered
	switch ( $column ) {
		
		case 'ecp1_cal_description':
			$ecp1_excerpt = get_the_excerpt();
			if ( '' != $ecp1_excerpt )
				printf( '<p>%s</p>', $ecp1_exerpt );
			
			$ecp1_url = $custom['ecp1_external_url'];
			if ( '' != $ecp1_url )
				printf( '<p>%s: %s</p>', __( 'From' ), $ecp1_url );
			break;
		
	}
}

// Function that registers a meta form box on the ecp1_calendar create / edit page
function ecp1_calendar_meta_fields() {
	add_meta_box( 'ecp1_calendar_meta', 'Calendar Settings', 'ecp1_calendar_meta_form', 'ecp1_calendar' );
}

// Function that generates a html section for adding inside a meta fields box
function ecp1_calendar_meta_form() {
	// Get the post and its custom data
	global $post;
	$custom = get_post_custom( $post->ID );
	$ecp1_url = $custom['ecp1_external_url'];
	
	// Sanitize and do security checks
	if ( '' != $ecp1_url )
		$ecp1_url = urldecode( $ecp1_url );
	
	// Output the meta box
?>
	<input type="hidden" name="ecp1_calendar_nonce" id="ecp1_calendar_nonce" value="<?php echo wp_create_nonce( 'ecp1_calendar_nonce' ); ?>" />
	<div class="ecp1_meta">
		<label for="ecp1_external_url"><?php _e( 'External URL' ); ?></label>
		<input id="ecp1_external_url" name="ecp1_external_url" type="text" class="ecp1_url" value="<?php echo $ecp1_url; ?>" />
	</div>
<?php
}




// Save the data when the meta box is submitted
add_action( 'save_post', 'ecp1_calendar_save' );
function ecp1_calendar_save() {
	global $post;
	
	// Verify the nonce just incase
	if ( ! wp_verify_nonce( $_POST['ecp1_calendar_nonce'], 'ecp1_calendar_nonce' ) )
		return $post->ID;
	
	// Verify the user can actually edit posts
	if ( ! current_user_can( 'edit_post', $post->ID ) )
		return $post->ID;
	
	// URL Encode the external URL and save
	$ecp1_url = '';
	if ( isset( $_POST['ecp1_external_url'] ) )
		$ecp1_url = urlencode( strip_tags( $_POST['ecp1_external_url'] ) );
	update_post_meta( $post->ID, 'ecp1_external_url', $ecp1_url );
}

?>