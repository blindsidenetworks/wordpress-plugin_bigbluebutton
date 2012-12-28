=== BigBlueButton ===
Contributors: blindsidenetworks, jfederico
Donate link: http://www.blindsidenetworks.com/integrations/wordpress/
Tags: blindsidenetworks, bigbluebutton, opensource, web, conferencing,
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


This plugin integrates BigBlueButton functionality into Wordpress. 

== Description ==

[BigBlueButton](http://bigbluebutton.org/ "BigBlueButton") is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers.

**For more information on setting up your own BigBlueButton server or for using an external hosting provider visit [http://bigbluebutton.org/support](http://bigbluebutton.org/support "http://bigbluebutton.org/support")**

== Installation ==

The easiest way to install is to watch this [installation video](http://www.youtube.com/watch?v=8Tle9BEKfFo "installation video") on YouTube. Here's an overview of the installation.

   1. Log in as an admin and click on the Plugins menu on the sidebar.
   1. Click Add new.
   1. In the search bar enter "bigbluebutton" and click search plugins.
   1. When you find the plugin called BigBlueButton by Blindside Networks click the install now link.
   1. Activate the Plugin.
   1. Click on widgets under the Appearance menu.
   1. Find the BigBlueButton Widget. Then click and drag it to either the right, content, or footer windows on the right of the screen depending on where you wish the BigBlueButton widget to appear.
   1. Click on BigBlueButton under the settings menu.
   1. Fill out the URL of where the BigBlueButton server is running (be sure to add /bigbluebutton/ to the end of the URL) and its salt. Then click on save changes.
   1. You are ready to begin creating meetings, and holding conferences.

== Frequently Asked Questions ==


**Why is it giving an error about creating a meeting room?**

Make sure you are using BigBlueButton 0.7 or higher.

**What is this error: "Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running."?**

You must make sure that your url ends with "/bigbluebutton/" at the end. 

So as an example:

* Wrong - "http://example.com/"
* Correct - "http://example.com/bigbluebutton/" 

**How can I improve security?**

You should enable the curl extension in php.ini. 

== Changelog ==

= 1.3.2 =
* Fixed an issue on update control that prevented 1.0.1 deployments to be properly updated
* Fixed an issue that prevented the plugin to work on webservers running php 5.2
* Added a warning to the welcome message on the bigbluebutton chat box when the meeting is recorded
 
= 1.3.1 =
* Changed version control. 1:major version,2:wordpress version supported,3:minor version
* Added shortcode [bigbluebutton] to render an access form into a page or post
* Meetings can be configured to be recorded (optional)
* Configuration form shows the list of recordings available for the Wordpress server
* Admin users can publish/unpublish and delete recordings from the BigBlueButton server
* Added shortcode [bigbluebutton_recordings] to render the list of recordings into a page or post
* Performance improvements
      (Important: This release does not support multi sites and is not localized)

= 1.0.2 =
* Wait for moderator is now meeting specific
* Added confirmation messages when ending or deleting a meeting
* Performance improvements

= 1.0.1 =
* Updated to use version 1.2 of the php api.
* Uses time stamps for the meeting version, which results in better performance.
* Includes some bug fixes.

= 1.0.0 =
* Added the initial files.

== Upgrade Notice ==

= 1.3.2 =
This version fixes an issue on deployments made on webservers with php 5.2 and a bug on the update control.

= 1.3.1 =
This version provides support for playback recordings, better performance, and allows shortcode at posts and pages.

= 1.0.2 =
This version provides better performance, and the wait for moderator option is now meeting specific.

= 1.0.1 =
This version provides better performance, and includes some bug fixes.

= 1.0.0 =
This version is the official release of the bigbluebutton plugin.