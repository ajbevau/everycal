<?php
/**
 * Adds a plugin settings link to Options and renders the options form if required
 */

// Make sure we're included from within the plugin
require( 'check-ecp1-defined.php' );

// Load the maps interface so we know which map implementations exist
require_once( 'map-providers.php' );

// Add action hooks
add_action( 'admin_init', 'ecp1_settings_register' );
add_action( 'admin_menu', 'ecp1_add_options_page' );

// Init plugin options to white list our options
function ecp1_settings_register() {
	register_setting( 'ecp1_global_options', 'ecp1_global', 'ecp1_validate_options_page' );
}

// Add menu page
function ecp1_add_options_page() {
	add_options_page( __( 'Every Calendar +1 Options' ), __( 'EveryCal+1' ), 'manage_options', 'ecp1_global', 'ecp1_render_options_page' );
	add_submenu_page( 'edit.php?post_type=ecp1_calendar', 'Events', 'Events', 'publish_posts', 'ecp1_event' );
}

// Draw the option page
function ecp1_render_options_page() {
?>
	<div class="wrap">
		<h2><?php _e( 'Every Calendar +1 Options' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'ecp1_global_options' ); ?>
			<?php $options = get_option( 'exp1_global' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Enable Maps / Provider' );</th>
					<td>
						<input id="ecp1_global[use_maps]" name="ecp1_global[use_maps]" type="checkbox" value="1" <?php checked( '1', $options['use_maps'] ); ?> />
						<select id="ecp1_global[use_maps]" name="ecp1_global[map_provider]">
							<option value="0">TODO</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Allow Timezone Changes' ); ?></th>
					<td>
						<input id="ecp1_global[tz_change]" name="ecp1_global[tz_change]" type="checkbox" value="1" <?php checked( '1', $options['tz_change'] ); ?> /><br/>
						<em><?php _e( 'Note: by default calendars will use the WordPress Timezone setting.' ); ?></em>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function ecp1_validate_options_page( $input ) {
	return $input;
}

?>