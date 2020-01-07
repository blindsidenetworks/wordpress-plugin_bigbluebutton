=== BigBlueButton ===
Contributors: blindsidenetworks, jfederico, yfngdu
Donate link: https://blindsidenetworks.com
Tags: blindsidenetworks, bigbluebutton, opensource, open source, web, conferencing, webconferencing
Requires at least: 5.1
Tested up to: 5.3.2
Requires PHP: 7.2
Stable tag: 3.0.0-beta.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This lightweight plugin integrates BigBlueButton functionality into Wordpress.

== Description ==

[BigBlueButton](http://bigbluebutton.org/ "BigBlueButton") is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers. It was developed and is maintained by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>.

For more information on setting up your own BigBlueButton server or for using an external hosting provider visit [http://bigbluebutton.org/support](http://bigbluebutton.org/support "http://bigbluebutton.org/support").

== Installation ==

Here's an overview of the installation.

   1. Log in as an admin and click on the Plugins menu on the sidebar.
   1. Click Add new.
   1. In the search bar enter "bigbluebutton" and click search plugins.
   1. When you find the plugin called BigBlueButton by Blindside Networks click the install now link.
   1. Activate the Plugin.
   1. Click on Rooms in the sidebar.
   1. Add a new room and publish it.
   1. Click on Rooms in the sidebar again and copy the token of your new room.
   1. Click on widgets under the Appearance menu.
   1. Find the Rooms Widget. Then click and drag it to either the right, content, or footer windows on the right of the screen depending on where you wish the BigBlueButton widget to appear.
   1. Enter the token you copied earlier into the widget and save.
   1. Click on Rooms in the sidebar and click on Server Settings.
   1. Fill out the URL of where the BigBlueButton server is running (be sure to add /bigbluebutton/ to the end of the URL) and its salt. Then click on Save.
   1. You are ready to begin creating meetings, and holding conferences.

== Frequently Asked Questions ==

**I've migrated my rooms. Now it says they're missing when I try to access them!**
Please try going to settings, and save permalinks. There is no need to change your permalink structure.

**I'm an admin and when I tried to enter a room, I couldn't enter as a moderator.**
Please try deactivating the plugin, and reactivating it again.

**How do I create meetings?**
After activating the BigBlueButton plugin, click on "Rooms" and "Add New" and give the meeting a title and fill out the room details.

**How can users join the meetings?**

Users join meetings using a join room form. This form can be shown in a site as a sidebar element or as a page/post.

For setting up in the sidebar, add the bigbluebutton widget, as you do with any other widget, dragging the box to the position you want it to be in.

By default each room will be on its own page, with the permalink listed in the Rooms page on the admin panel. Rooms can also be inserted into any post/page using the [bigbluebutton] shortcode with specified tokens, token1, token2 in the shortcode in the format, [bigbluebutton token="token1,token2"].

**How can users view recordings?**

By default, each room will have its own page, which will display the room description, join button, and the recordings.

To place recordings on a separate post/page, use the shortcode [bigbluebutton type="recording"], with the room tokens of the desired recordings. For example, the recordings for the tokens, token1, token2 can be displayed using [bigbluebutton type="recording" token="token1, token2"].

**Why sometimes the Name and Password are required, some others only the Name and others only the Password?**

The plugin gathers the much information it cans from Wordpress, but what will be taken depends of the configuration.

For registered users their registered name or username will be taken as Name.

For registered users whose role has ben set for requiring always a password, only the Password will be required.

For anonymous users the Name will be always required, but again the Password requirment will depend of the configuration. If Moderator/Attendee capability has been set for them no Password box will be shown in their join room form.

**How can I change permissions of the users?**

You should install and activate the "Members" plugin by Justin Tadlock and in the Dasboard under the "Users" > "Roles", update the permissions.

To allow another user to create and edit rooms, assign them a role which has the permissions, activate_plugins and edit_bbb_rooms, publish_bbb_rooms, delete_bbb_rooms, delete_published_bbb_rooms, and edit_published_bbb_rooms. The permission structure is similar for posts and pages.

To allow another user to create and edit room categories, assign them a role which has the permissions, activate_plugins and manage_categories. This does not give them permission to create rooms. They can only manage room categories.

To allow another user to join as moderator, viewer, or with a code, assign them to a role with one of the corresponding permissions, join_as_moderator_bbb_room, join_as_viewer_bbb_room, or join_with_password_bbb_room. By default, the owner of the room will always join their rooms as a moderator. The default does not apply to others' rooms.

To allow another user to manage recordings, assign them to a role which has the permissions, manage_bbb_room_recordings.

To allow another user to use shortcodes or the widget, assign them to a role which has the permissions, edit_bbb_rooms.

If there are no roles with the corresponding permissions, please create a custom role using the "Members" plugin and assign the permission to that role.

**Is there any way users can go directly into a meeting?**

Since version 1.3.4 it is possible to provide direct access to the meeting rooms by adding the meeting token ID to the shortcode: (eg. [bigbluebutton token="aa2817f3a1e1"]).

The joining form is the same, so with the right permission configuration users would be able to join meetings in one click.

**Why is it giving an error about creating a meeting room?**

Make sure you are using BigBlueButton 0.8 or higher. Ensure the server settings are configured correctly.

**How can I improve security?**

You should enable the curl extension in php.ini.

**I tried to preview my room, and nothing showed up!**

Rooms may not be viewed until they are published. Please try again after publication.

**I want to edit my recordings. How do I do that?**

If a user has the capability to manage recordings, they will see a pencil icon next to recording name and description. Click on the icon to start editing, and press enter to submit. A user can cancel editing by pressing the ESC key.

== Screenshots ==

1. Rooms are a Content Type that has its own view.
2. Rooms can be embedded into Posts, Pages and other Content Types using shortcodes.
3. Multiple rooms can be accessed frome the same Page or Post.
4. Server settings define where the meetings are hosted.
5. Rooms can be managed through the Administrator Dashboard.
6. Rooms can also be organized using Categories.

== Changelog ==


= 3.0.0-beta.4 =
* Bug. Fixed issue with plugin permission capability that prevented rooms to be accessed after upgrade.
* Bug. Fixed issue with logoutURL not being the request location.

= 3.0.0-beta.3 =
* Bug. Enable users who could previously edit rooms/recordings to have the same capabilities without role capability changes from an administrator.
* Bug. Fixed issue where warning message for updating the plugin would throw an error.
* Bug. Ensure capabilities associated with rooms are deleted when the plugin is uninstalled.
* Improvement. Removed lint configuration files.

= 3.0.0-beta.2 =
* Hot-fix: meetingId was lost on every update of the room.
* Bug. Fixed issue with incompatibility of css used by the recording table.
* Improvement. Improved handling of blank entry codes.

= 3.0.0-beta.1 =
* First inception of the 3.x release.

= 2.0.0 =
* Never released.

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

== Upgrade Notice ==

= 3.0.0 =
This plugin has been entirely rewritten. Although there has been efforts to make it backward compatible, data and settings migrations is required. **Please backup your database beforehand.**

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
