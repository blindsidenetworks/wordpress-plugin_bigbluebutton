<?php
/*
Copyright 2012 Blindside Networks
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
Versions:
   1.0  --  Updated by Jesus Federico
                    (email : federico DOT jesus [a t ] g m ail DOT com)
*/
///================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require 'includes/bbb_api.php';
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
session_start();
$endpointname = 'mt_bbb_endpoint';
$secretname = 'mt_bbb_secret';
$actionname = 'action';
$recordingidname = 'recordingID';
$slugname = 'slug';
$join = 'join';
$password = 'password';

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
//Retrieves the bigbluebutton url, and salt from the seesion
if (!isset($_SESSION[$secretname]) || !isset($_SESSION[$endpointname])) {
    header('HTTP/1.0 400 Bad Request. BigBlueButton_CPT Url or Salt are not accessible.');
} elseif (!isset($_GET[$actionname])) {
    header('HTTP/1.0 400 Bad Request. [action] parameter was not included in this query.');
} else {
    $secretvalue = $_SESSION[$secretname];
    $endpointvalue = $_SESSION[$endpointname];
    $action = $_GET[$actionname];
    switch ($action) {
        case 'publish'://join plublis/unpublish together
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingidname])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingid = $_GET[$recordingidname];
                echo BigBlueButton::doPublishRecordings($recordingid, 'true', $endpointvalue, $secretvalue);
            }
            break;
        case 'unpublish':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingidname])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingid = $_GET[$recordingidname];
                echo BigBlueButton::doPublishRecordings($recordingid, 'false', $endpointvalue, $secretvalue);
            }
            break;
        case 'delete':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingidname])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingid = $_GET[$recordingidname];
                echo BigBlueButton::doDeleteRecordings($recordingid, $endpointvalue, $secretvalue);
            }
            break;
        case 'ping':
            $username = bigbluebutton_set_user_name();
            $meetingid = bigbluebutton_set_meeting_id($_POST[$slugname]);
            $password = bigbluebutton_set_password_broker($_POST[$slugname]);
            $response = BigBlueButton::getMeetingXML($meetingid, $endpointvalue, $secretvalue);
            if((strpos($response,"true") !== false)){
              echo BigBlueButton::getJoinURL($meetingid, $username, $password , $secretvalue, $endpointvalue);
            }
            break;
        case 'join':
            if((!isset($_POST[$slugname]))){
                header('HTTP/1.0 400 Bad Request. [slug] parameter was not included in this query.');
            }else if((!isset($_POST[$join]))){
                header('HTTP/1.0 400 Bad Request. [join] parameter was not included in this query.');
            }else{
              $post = get_page_by_path($_POST[$slugname], OBJECT, 'bbb-room');
              if($_POST[$join] === "true"){
                $username = bigbluebutton_set_user_name();
                $meetingid = bigbluebutton_set_meeting_id($_POST[$slugname]);
                $password = bigbluebutton_set_password_broker($_POST[$slugname]);
                $meetingname = get_the_title($post->ID);
                $welcomestring = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
                $moderatorpassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
                $attendeepassword = get_post_meta($post->ID, '_bbb_attendee_password', true);
                $isrecorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
                $logouturl = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $waitforadminstart = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
                $metadata = array(
                 'meta_origin' => 'WordPress',
                 'meta_origintag' => 'wp_plugin-bigbluebutton_custom_post_type ',
                 'meta_originservername' => home_url(),
                 'meta_originservercommonname' => get_bloginfo('name'),
                 'meta_originurl' => $logouturl,
                );
                $response = BigBlueButton::createMeetingArray($meetingid, $meetingname, $welcomestring, $moderatorpassword,$attendeepassword, $secretvalue, $endpointvalue, $logouturl, $isrecorded ? 'true' : 'false', $duration = 0, $voiceBridge = 0, $metadata);

                if (!$response || $response['returncode'] == 'FAILED') {
                    echo "Sorry an error occured while creating the meeting room.";
                }else {
                    $joinurl = BigBlueButton::getJoinURL($meetingid, $username, $password, $secretvalue, $endpointvalue);
                    $ismeetingrunning = BigBlueButton::isMeetingRunning($meetingid, $endpointvalue, $secretvalue);
                    if (($ismeetingrunning && ($moderatorpassword == $password || $attendeepassword == $password))
                         || $response['moderatorPW'] == $password
                         || ($response['attendeePW'] == $password && !$waitforadminstart)) {
                          echo $joinurl;
                    }
                    elseif ($attendeepassword == $password) {
                        echo 'wait';
                    }
                    else{
                      if($password==''){
                        echo 'Incorrect Password';
                      }elseif($username=''){
                        echo 'Username';
                      }
                    }
                }
              }else {
                if($post !== null){
                  echo get_permalink();
                }else {
                  echo "Sorry the page could not be viewed";
                }
              }
            }
            break;
        default:
            header('Content-Type: text/plain; charset=utf-8');
            echo BigBlueButton::getServerVersion($endpointvalue);
    }
}

/**
* Sets the password of the meeting
**/
function bigbluebutton_set_password_broker($slug){
  $post = get_page_by_path($slug, OBJECT, 'bbb-room');
  $currentuser = wp_get_current_user();
  $password='';
  $moderatorpassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
  $attendeepassword = get_post_meta($post->ID, '_bbb_attendee_password', true);

  if(is_user_logged_in() == true) {
    $usercaparray = $currentuser->allcaps;

  }else {
    $anonymousRole = get_role('anonymous');
    $usercaparray = $anonymousRole->capabilities;
  }

  if($usercaparray["join_with_password_bbb-room"] == true ) {
      if($usercaparray["join_as_moderator_bbb-room"] == true) {
        if(strcmp($moderatorpassword,$_POST['password']) === 0) {
            $password = $moderatorpassword;
        }
      }else {
        if(strcmp($attendeepassword,$_POST['password']) === 0) {
            $password = $attendeepassword;
        }
      }
  }else {
      if($usercaparray["join_as_moderator_bbb-room"] === true) {
        $password = $moderatorpassword;
      }else {
        $password = $attendeepassword;
      }
  }
  return $password;
}

/**
* Sets the user name of the moderator or attendee
**/
function bigbluebutton_set_user_name(){
  $currentuser = wp_get_current_user();
  $username = $currentuser->display_name;
  if($username == '' || $username == null){
    $username = $_POST['name'];
  }
  return $username;
}

/**
* Sets the meetingID
**/
function bigbluebutton_set_meeting_id($slug)
{
  $post = get_page_by_path($slug, OBJECT, 'bbb-room');
  $roomtoken = get_post_meta($post->ID, '_bbb_room_token', true);
  $meetingid = $roomtoken;
  if(strlen($meetingid) == 12){
    $meetingid = sha1(home_url().$meetingid);
  }
  return $meetingid;
}
