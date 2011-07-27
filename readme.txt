=== Every Calendar +1 for WordPress ===
Contributors: andrewbevitt
Donate link: http://andrewbevitt.com/
Tags: calendar, events
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.1-alpha

A WordPress plugin that integrates calendars, custom post types, maps, and offsite linking.

== Description ==

TODO: Create a better brief and full description in the readme.txt file.

== Installation ==

This plugin requires PHP5.

The best way to install this plugin is through your WordPress Admin. Alternatively upload all of the plugins files to the '/wp-content/plugins/everycalplus1' directory.

Once the plugin has been installed/uploaded you need to Activate this plugin in the 'Plugins' Admin panel.

To put a calendar onto one of your pages use the provided shortcode in the 'Calendar' admin panel (created by plugin).

== Frequently Asked Questions ==

= How do I allow contributors/authors to add events to a (someone elses) calendar? ==

Use a capability manager to assign user as a contributor role to the post and set the calendar contributor role to allow editing of published posts.

In Role Scoper:
1. Go to the calendar and assign the group or user to Contributors for this post
2. Go to Role Scoper -> Options -> RS Role Definitions
3. Assign Calendar Contributor the "Edit Published..." capability.

= Why this plugin? =

I wanted a WordPress calendar that did everything and I couldn't find one that did so I wrote my own.

= What external calendars are supported? =

For the initial release only Google Calendar will be supported. This is more because of limitations in Full Calendar
than anything else. The external calendar interface is pluggable so you can extend as you wish.

== Screenshots ==

1. description corresponds to screenshot-1.(png|jpg|jpeg|gif) in same dir.
2. description corresponds to screenshot-2.(png|jpg|jpeg|gif) in same dir.

== Changelog ==

= 0.1-alpha =
* Initial plugin creation

== Upgrade Notice ==

= 0.1-alpha0 =
This is the first alpha development release there is no reason to upgrade yet.
