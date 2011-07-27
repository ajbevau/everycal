<?php
/**
 * File that loads a list of events based on parameters and then
 * returns a JSON data set in FullCalendar format of those events.
 */

// Load the WordPress environment so we can make queries etc...
//require( )
include "wp-load.php";
global $wpdb;
header('Content-Type:application/json');
/*$events = array();
$result = new WP_Query('post_type=event&posts_per_page=-1');
foreach($result->posts as $post) {
  $events[] = array(
    'title'   => $post->post_title,
    'start'   => get_post_meta($post->ID,'_start_datetime',true),
    'end'     => get_post_meta($post->ID,'_end_datetime',true),
    'allDay'  => (get_post_meta($post->ID,'_all_day',true) ? 'true' : 'false'),
    );
}
echo json_encode($events);
exit;*/


?>
[{a:'b'},{a:'c'},{a:'afa'}]