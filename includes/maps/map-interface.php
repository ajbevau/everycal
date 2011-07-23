<?php
/**
 * Abstract implementation of the ECP1 Maps pluggable interface.
 */

// Make sure we're included from within the plugin
require( ECP1_DIR . '/includes/check-ecp1-defined.php' );

// The abstract maps class
abstract class ECP1MapInterface {
	
	// Administration interface HTML block that map will be rendered in
	protected $admin_html_block = '';
	
}

?>