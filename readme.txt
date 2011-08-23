=== Every Calendar +1 for WordPress ===
Contributors: andrewbevitt
Donate link: http://andrewbevitt.com/
Tags: calendar, events
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.1.5

A WordPress plugin that integrates calendars, custom post types, maps, and offsite linking.

== Description ==

Every Calendar +1 is a pluggable interface for displaying locally entered events (as a custom post type) and displaying syndicated calendar feeds. You can use any calendar feed that is supported by the FullCalendar jQuery library (at time of writing this was only Google Calendar). The event colours are customisable for each event source and the plugin supports a pluggable maps interface for event locations (initially the plugin only provides a Google Maps implementation but many more can be added).

The plugin creates two custom post types:
1) Events
2) Calendars

A Calendar Post can contain as many event posts as you like and can also syndicate as many external calendars as you like.

Events can be labeled as feature events: feature events will be displayed on any calendar the administratos configure as a Featured Calendar. This is a great way to have local site calendars (for say a regional office) and a global calendar which shows feature events from the local sites.

Roles and Capbilities: If you can edit a calendar and have edit_others_posts for events then you can edit any event in that calendar. Otherwise you can only edit your own as per normal.

This plugin was written because I could not find a plugin that provided great events management, calendar integration and worked reliably.

There is a planned development roadmap (grep -R ROADMAP *):
* Add support for extra calendar providers
* Perform better security checks on events and write a map_meta_cap function for calendar checks
* Provide a syndication feed for events in a calendar: XML, JSON, ICS, etc... (priority)
* Add repeating events (priority)
* Add extra shortcodes for different types of calendars
* Add widget support
* Dynamic UI in admin when clicking checkboxes
* Tags for events and calendars of tagged events

== Installation ==

This plugin requires PHP5 >= 5.2.0 that's over 2 years old now so upgrade if you haven't yet.

The best way to install this plugin is through your WordPress Admin. Alternatively upload the zip file to your plugins directory.

Once the plugin has been installed/uploaded you need to Activate this plugin in the 'Plugins' Admin panel.

To put a calendar onto one of your pages use the provided shortcode in the 'Calendar' admin panel (created by plugin).

== Frequently Asked Questions ==

= How do I allow contributors/authors to add events to a (someone elses) calendar? =

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

= 0.1.5 =
* Include comments on the events post
* Clear the loop actions on the iCAL and JSON feeds (fix http://wordpress.org/support/topic/plugin-every-calendar-1-for-wordpress-events-dont-show-up-on-calendar)

= 0.1.4 =
* Added MySQL support for GMT timezone conversion - fixes permalink bug where MySQL timezone is not GMT

= 0.1.3 =
* Client side CSS for popup links

= 0.1.2 =
* Added better permalinks for the event post type: /event/%year%/%month%/%day%/event-name
* First tagged stable release on the WordPress Plugin Directory
* Added screenshots to the repository

= 0.1.1 =
* Fixed bugs where PHP 5.3 API changed from PHP 5.2 now compatible with PHP 5.2
* Tidy up the readme file

= 0.1 =
* First major release with documented functionaility

= 0.1-beta =
* Functional support for event and calendar types but no maps or external feeds

= 0.1-alpha =
* Initial plugin creation

== Upgrade Notice ==

All versions of Every Calendar +1 are backward compatible at present.

= 0.1-alpha0 =
This is the first alpha development release there is no reason to upgrade yet.
