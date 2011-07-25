<?php
/**
 * Adds a plugin settings link to Options and renders the options form if required
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Make sure we have loaded the admin settings for the post types
require_once( ECP1_DIR . '/includes/custom-post-admin.php' );

// Load the maps interface so we know which map implementations exist
require_once( ECP1_DIR . '/includes/map-providers.php' );

// Load the external calendars interface so we know which ones exist
require_once( ECP1_DIR . '/includes/external-calendar-providers.php' );

// Load the plugin settings and helper functions
require_once( ECP1_DIR . '/includes/data/ecp1-settings.php' );

// Add action hooks
add_action( 'admin_init', 'ecp1_settings_register' );
add_action( 'admin_menu', 'ecp1_add_options_page' );

// Init plugin options to white list our options
function ecp1_settings_register() {
	register_setting( ECP1_OPTIONS_GROUP, ECP1_GLOBAL_OPTIONS, 'ecp1_validate_options_page' );
}

// Add menu page
function ecp1_add_options_page() {
	// Add the settings / options menu item
	$page = add_options_page( __( 'Every Calendar +1 Options' ), __( 'EveryCal+1' ), 'manage_options', ECP1_GLOBAL_OPTIONS, 'ecp1_render_options_page' );
	add_action( 'admin_print_styles-' . $page, 'ecp1_enqueue_admin_css' );
	
	// Add new event to the calendar and remove new calendar for neatness
	add_submenu_page( 'edit.php?post_type=ecp1_calendar', _x( 'New Event', 'ecp1_event'), _x( 'New Event', 'ecp1_event' ), 'publish_posts', 'post-new.php?post_type=ecp1_event' );
	remove_submenu_page( 'edit.php?post_type=ecp1_calendar', 'post-new.php?post_type=ecp1_calendar' );
}

// Draw the option page
function ecp1_render_options_page() {
?>
	<div class="wrap">
		<h2><?php _e( 'Every Calendar +1 Options' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( ECP1_OPTIONS_GROUP ); ?>
			<?php $options = _ecp1_get_options(); ?>
			<table class="form-table">
				<tr valign="top">
				<tr valign="top">
					<th scope="row"><?php _e( 'Allow Timezone Changes' ); ?></th>
					<td>
						<input id="<?php echo ECP1_GLOBAL_OPTIONS; ?>[tz_change]" name="<?php echo ECP1_GLOBAL_OPTIONS; ?>[tz_change]" type="checkbox" value="1" <?php checked( '1', $options['tz_change'] ); ?> />
						<em><?php _e( 'Note: by default calendars will use the WordPress Timezone setting.' ); ?></em>
					</td>
				</tr>
					<th scope="row"><?php _e( 'Enable Maps / Provider' ); ?></th>
					<td>
						<input id="<?php echo ECP1_GLOBAL_OPTIONS; ?>[use_maps]" name="<?php echo ECP1_GLOBAL_OPTIONS; ?>[use_maps]" type="checkbox" value="1" <?php checked( '1', $options['use_maps'] ); ?> />
						<select id="<?php echo ECP1_GLOBAL_OPTIONS; ?>[map_provider]" name="<?php echo ECP1_GLOBAL_OPTIONS; ?>[map_provider]">
<?php
	// For each map provider create an entry
	$map_providers = ecp1_map_providers();
	foreach( $map_providers as $slug=>$details ) 
		printf( '<option value="%s"%s>%s</option>', $slug, $slug == $options['map_provider'] ? ' selected="selected"' : '', $details['name'] );
?>
						</select>
					</td>
				</tr>
					<th scope="row"><?php _e( 'Calendar Providers' ); ?></th>
					<td>
<?php
	// For each provider display a checkbox do rows of 3
	$cal_providers = ecp1_calendar_providers();
	$display_counter = 0;
	foreach( $cal_providers as $name=>$details ) {
		if ( 0 == $display_counter)
			printf( '<div class="ecp1_checkbox_row">' );
?>
		<span class="ecp1_checkbox_block">
			<input id="<?php printf( '%s[cal_providers][%s]', ECP1_GLOBAL_OPTIONS, $name ); ?>" name="<?php printf( '%s[cal_providers][%s]', ECP1_GLOBAL_OPTIONS, $name ); ?>" type="checkbox" value="1"<?php echo _ecp1_calendar_provider_enabled( $name ) ? ' checked="checked"' : '' ?> />
			<label for="<?php printf( '%s[cal_providers][%s]', ECP1_GLOBAL_OPTIONS, $name ); ?>"><?php echo $details['name']; ?></label>
		</span>
<?php
		$display_counter += 1;
		if ( 3 == $display_counter) { // i.e. 3 displayed
			printf( '</div>' );
			$display_counter = 0;
		}
	}
?>
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
	// Check the map provider is valid
	if ( isset( $input['map_provider'] ) ) {
		$map_providers = ecp1_map_providers();
		if ( ! array_key_exists( $input['map_provider'], $map_providers ) ) {
			// Not a valid map selected: use default (none)
			$input['map_provider'] = _ecp1_option_get_default( 'map_provider' );
		}
	} else {
		// Use default map provider
		$input['map_provider'] = _ecp1_option_get_default( 'map_provider' );
	}
	
	// If the use_map/timezone setting is given then set to true otherwise to false
	$boolean_options = array( 'use_maps', 'tz_change' );
	foreach( $boolean_options as $key ) {
		if ( isset( $input[$key] ) && '1' == $input[$key] ) {
			$input[$key] = 1;
		} else {
			$input[$key] = 0;
		}
	}
	
	// Rebuild the _external_cal_providers CSV list from the checkboxes
	$external_providers = '';
	$cal_providers = ecp1_calendar_providers();
	foreach( $cal_providers as $name=>$details ){
		if ( isset( $input['cal_providers'][$name] ) && '1' == $input['cal_providers'][$name] )
			$external_providers .= $name . ',';
	}
	$input['_external_cal_providers'] = trim( $external_providers, ',' );
	unset( $input['cal_providers'] );

	// If a external calendar provider is enabled then allow otherwise deny
	$input['use_external_cals'] = strlen( $external_providers ) > 0 ? 1 : 0;
	
	// Return the sanitized array
	return $input;
}

?>
