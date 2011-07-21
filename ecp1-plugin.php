<?php
/*
Plugin Name: Every Calendar +1 for WordPress
Plugin URI: http://andrewbevitt.com/code/wp/everycalplus1
Description: A WordPress Calendar plugin with custom types and maps support.
Version: 0.1-alpha
Author: Andrew Bevitt
Author URI: http://andrewbevitt.com
License: GPL2

Copyright 2011  Andrew Bevitt  (email: mycode@andrewbevitt.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Allow plugin files to load by defining scope of plugin
define( 'ECP1_PLUGIN', 'YESHERE' );

// Define the Custom Post Type
require_once( 'includes/custom-post-type.php' );

// If displaying the administration dashboard load admin UI
if ( is_admin() ) {
	include_once( 'includes/custom-post-admin.php' );
	include_once( 'includes/plugin-settings-page.php' );
	// TODO
}

?>
