<?php
/**
 * Every Calendar +1 Plugin Helper Functions
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// Returns all calendars the user can edit
function _ecp1_current_user_calendars() {
	return get_posts( array( 'post_type'=>'ecp1_calendar', 'suppress_filters'=>false, 'numberposts'=>-1, 'nopaging'=>true ) );
}

?>
