=== BigBlueButton ===
Contributors: blindsidenetworks, jfederico
Donate link: http://www.blindsidenetworks.com/integrations/wordpress/
Tags: blindsidenetworks, bigbluebutton, opensource, open source, web, conferencing, webconferencing
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


This plugin integrates BigBlueButton functionality into Wordpress.

== Description ==

[BigBlueButton](http://bigbluebutton.org/ "BigBlueButton") is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers. It was developed and is maintained by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>.

For more information on setting up your own BigBlueButton server or for using an external hosting provider visit [http://bigbluebutton.org/support](http://bigbluebutton.org/support "http://bigbluebutton.org/support").

== Installation ==

The easiest way to install is to watch this [installation video](http://www.youtube.com/watch?v=8Tle9BEKfFo "installation video") on YouTube. Here's an overview of the installation.

   1. Log in as an admin and click on the Plugins menu on the sidebar.
   2. Click Add new.
   3. In the search bar enter "bigbluebutton" and click search plugins.
   4. When you find the plugin called BigBlueButton by Blindside Networks click the install now link.
   5. Activate the Plugin.
   6. Click on widgets under the Appearance menu.
   7. Find the BigBlueButton or BigBlueButton Rooms Widget. Then click and drag it to either the sidebar or footer windows on the right of the screen depending on where you wish the BigBlueButton widget(s) to appear.
   8. Click on BigBlueButton under the settings menu.
   9. Fill out the endpoint URL of where the BigBlueButton server is running (be sure to add /bigbluebutton/ to the end of the URL) and its shared secret (salt). Then click on save changes.
   10. To create meetings, click on "Rooms" in the top-level of the admin page and add new rooms to create meeting sessions.
   11. Add the title and room details as per wish.
   12. You are ready to hold conferences.
   

== Frequently Asked Questions ==

**How do I create meetings?**
After activating the BligBlueButton plugin, click on "Rooms" when "Add New" and give the meeting a title and fill out the room details.

**How do users join the meetings?**

Users join meetings using a joining form. This form can be shown in a site as a sidebar element or as a page/post.

For setting up in the sidebar, add the bigbluebutton widget, as you do with any other, dragging the box to the position you want to.

For setting the joining form up as a page/post, add the shortcode [bigbluebutton] right where you want the form to appear in the page/post. If there are pre-created meetings in wordpress, their names should appear in a listbox from which users can select. If there is only one pre-created meeting the listbox will not be shown and one button with the name of the meeting will appear instead.

**What parts of the widgets can I customize?**

Since version 1.4.2 it is possible to add parameters for the shortcode which are: type, title, token and join.

Type(default rooms): "rooms" or "recordings", this will show the shortcode chossen to be displayed
Title(default Rooms): Any title as per administrators setup.
Token: add the meeting rooms token that you want to see in the joining form, but if left empty, all the meetings that were created will show up
Join(default true): if true, the useres can directly join the meeting session, if false, the users View a webpage where there is an potion to join the meeting.

An example of adding a joining form with two meetings and a title of "Rooms" is:
[bigbluebutton type="rooms" title="Rooms" token="93410c6104b7, a79cb08c9fc9" join="true"]

**Why sometimes the Name and Password are required, some others only the Name and others only the Password?**

The plugin gatters the much information it cans from Wordpress, but what will be taken depends of the configuration.

For registered users their registered name or username will be taken as Name. The BigBlueButton role (moderator/attendee) can be assigned automatically depending of the permission settings. This way a registered user in a role which permissions has been previously set would not be required nether for Name nor Password.

For registered users whose role has ben set for requiring always a password, only the Password will be required.

For anonymous users the Name will be always required, but again the Password requirment will depend of the configuration. If Moderator/Attendee role has ben set for them no Password box will be shown in their joining form.

**How can I change permissions of the users?**

You should install and activate the "Members" plugin by Justin Tadlock and in the Dasboard under the "Users" > "Roles", update the permissions.

**Is there any way users can go directly into a meeting?**

Since version 1.3.4 it is possible to provide direct access to the meeting rooms by adding the meeting token ID to the shortcode: (eg. [bigbluebutton token="aa2817f3a1e1"]).

The joining form is the same, so with the right permission configuration users would be able to join meetings in one click.

**How can I show the recordings?**

The only way to show recordings to users is using the shortcode [bigbluebutton_recordings] or by adding the parameter type in the shortcode [bigbluebutton type="recordings"]  in a page/post.

**Why is it giving an error about creating a meeting room?**

Make sure you are using BigBlueButton 0.8 or higher.

**What is this error: "Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running."?**

You must make sure that your url ends with "/bigbluebutton/" at the end.

So as an example:

* Wrong - "http://example.com/"
* Correct - "http://example.com/bigbluebutton/"

**How can I improve security?**

You should enable the curl extension in php.ini.

**When click "Preview Changes" through a meeting room, what do I get if I get the error "Oops, This Page Could Not Be Found!"?

Try chnaging the Permalinks structure through the Dashboard from Settings > Permalinks > Choose any other permalink > Save Changes.

== Screenshots ==

1. Login form for anonymous users.
2. Login form for registered users.
3. General settings.
4. Create a meeting room.
5. Setting up meeting room details(1).
6. Setting up meeting room details(2).
7. Recordings in a front end page.


== Changelog ==

= 2.0.0 =
* Added feature. Custom Post Type and taxonomies to create and join rooms. *
* Added feature. Custom parameters of type, title, token and join in the shortcode [bigbluebutton]. *
* Added feature. Support of wordpress permissions for assigning roles in BigBlueButton. *
* Updated. Simplified UI for configuring the plugin. *

= 1.4.4 =
* Fixed issue. Date format in recording was updated.

= 1.4.3 =
* Updated. Tested on WP 4.8.1 and updated version tag.

= 1.4.2 =
* Updated. Updated tag.
* Fixed issue. Fixed issue with permissions to roles rised after the change to custom roles in the previous release.

= 1.4.1 =
* Updated. Tested on WP 4.3 and updated tag.
* Fixed issue. Fixed issue with custom roles not being considered for matching with BigBlueButton roles.
* Added feature. An static voicebridge can be passed as a parameter using the shortcodes e.g. voicebridge="99999".
* Added feature. An specific set of meetings can be included in the form by adding the list of tokens to the shortcode. e.g. tokens="12345,54321".

= 1.4.0 =
* Updated. Tested on WP 4.1 and updated tag.
* Fixed issue. Fixed two potential security vulnerabilities.
* Fixed issue. Increased the interval for polling BBB meetings when waiting for moderator is used.
* Fixed issue. Relative links for polling request and spinning wheel image where not working in some deployments.

= 1.3.10 =
* Updated. Tested on WP 4.0 and updated tag.

= 1.3.9 =
* Fixed issue. The password form in the widget was not rendered correctly.

= 1.3.8 =
* Fixed issue. The login form was rendered out of the limits when the widget was used in a narrow side column.

= 1.3.7 =
* Fixed issue. Password required option not working for registered users. Only for Anonymous.
* Fixed issue. Form was rendered out of bounds when using the widget.

= 1.3.6 =
* Added feature. Form presentation can be customized using css.
* Added feature. Two demo meetings are created by default when the plugin is installed.
* Fixed issue. Polling image not showing up on multisite deployments.
* Fixed issue. Token generation causes an error when php < 5.3 or no openssl available.

= 1.3.5 =
* Fixed issue. Meeting could not been deleted.
* Fixed issue. Meeting could not been added when using non updated MySQL versions.

= 1.3.4 =
* Fixed issue. List of recordings did not show the correct duration on 32-bit servers.
* Fixed issue. When using short codes, the bigbluebutton content appeared at the very top on the page or post.
* Fixed issue. For anonymous users the join meeting form was always shown, even though they were allowed to sign without password.
* Fixed issue. Recording link broken when the recording is not published
* Fixed issue. Anonymous user were not able to join meetings without password, even though the settings were correct
* Changed meetingId. Wordpress meetingID is no longer the BBB meetingID. Instead a short 13 characters internal token is used to generate the real meetingID.
* Added feature. Title on recording list can be set up using a shortcode parameter [bigbluebutton_recordings title='Example'].
* Added feature. Included classes and ids to the html tags for enable designers to add style.
* Added feature. Password are random generated when not included in create form.
* Added feature. Extended shortcode can receive token id and submit message [bigbluebutton token="a7ccc7f752f65" submit"Meet me there!"]. [token] can be taken from the list of meeting rooms created, when set the join button will link to the specific meeting. [submmit] will override the text in the join button.

= 1.3.3 =
* Fixed issue. On admin UI users were prevented to join meetings using the meeting list.
* Fixed issue. When installing and/or updating a version the activate methods were not properly working.
* Changed permissions. Administrator can now set moderator or attendee as default bbb-roles to the different wp-roles available.
* Changed permissions. Administrator can now set -manageRecordings and -participate permissions to any of the wp-roles available.
* Changed interface on joining form. If there is only one meeting the selection box is not shown.
* Changed logouturl. The logout url is now the page from where the create/join call was made instead of the main page.
* Added feature. Plugin can be used on multisite deployments.

= 1.3.2 =
* Fixed an issue on update control that prevented 1.0.1 deployments to be properly updated.
* Fixed an issue that prevented the plugin to work on webservers running php 5.2.
* Fixed an issue that prevented meetings to be created in recording mode.
* Added a warning to the welcome message on the bigbluebutton chat box when the meeting is recorded.
* A generic welcome message can be set as parameter using the shortcode [bigbluebutton welcome='<br>Custom message<br>%%CONFNAME%%'].

= 1.3.1 =
* Changed version control. 1:major version (remains),2:minor version (former release version),3:release version.
* Added shortcode [bigbluebutton] to render an access form into a page or post.
* Meetings can be configured to be recorded (optional).
* Configuration form shows the list of recordings available for the Wordpress server.
* Admin users can publish/unpublish and delete recordings from the BigBlueButton server.
* Added shortcode [bigbluebutton_recordings] to render the list of recordings into a page or post.
* Performance improvements
      (Important: This release does not support multi sites and is not localized)

= 1.0.2 =
* Wait for moderator is now meeting specific.
* Added confirmation messages when ending or deleting a meeting.
* Performance improvements.

= 1.0.1 =
* Updated to use version 1.2 of the php api.
* Uses time stamps for the meeting version, which results in better performance.
* Includes some bug fixes.

= 1.0.0 =
* Added the initial files.

== Upgrade Notice ==

= 2.0.0 =
This version is a major shift from the original approach pursued in version 1.x. Although it is fully backward compatible, we recommend to have more grasp on how to take advantage of the new features and capabilities before upgrading.

= 1.4.4 =
This version includes a fix for recording dates shown in UTC. They are now displayed in the local timezone.

= 1.4.3 =
Tested on WP 4.8.1 and updated version tag.

= 1.3.4 =
This version fixes some presentation issues. Style and javascript functions can added using classes and ids included on html elements.

= 1.3.3 =
This version enable administrators to set permissions for accessing meetings. It also fixes few issues on deployments.

= 1.3.2 =
This version fixes an issue on deployments made on webservers with php 5.2, a bug on the update control and a bug that prevented meetings to be created in recording mode.

= 1.3.1 =
This version provides support for playback recordings, better performance, and allows shortcode at posts and pages.

= 1.0.2 =
This version provides better performance, and the wait for moderator option is now meeting specific.

= 1.0.1 =
This version provides better performance, and includes some bug fixes.

= 1.0.0 =
This version is the official release of the bigbluebutton plugin.
