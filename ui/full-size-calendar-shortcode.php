<?php
/**
 * Registers a shortcode for a full sized (i.e. whole page) calendar
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Register the shortcode type and callback
add_shortcode( 'largecalendar', 'ecp1_full_size_calendar' );

// [ largecalendar name="calendar name" ]
function ecp1_full_size_calendar( $atts ) {
	// Extract the attributes or assign default values
	extract( shortcode_atts( array(
		'name' => null, # checked below must not be null
	), $atts ) );
	
	// Make sure a name has been provided
	if ( is_null( $name ) )
		return sprintf( '<span class="ecp1_error">%s</span>', __( 'Unknown calendar: could not display.' ) );
	
	// Finally return the complete string (TODO)
	return ecp1_render_calendar( null );
}

?>