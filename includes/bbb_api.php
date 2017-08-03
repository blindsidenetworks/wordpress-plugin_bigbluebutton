<?php
/*
Copyright 2010 Blindside Networks

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
   1.0  --  Initial version written by DJP
                   (email: djp [a t ]  architectes DOT .org)
   1.1  --  Updated by Omar Shammas and Sebastian Schneider
                    (email : omar DOT shammas [a t ] g m ail DOT com)
                    (email : seb DOT sschneider [ a t ] g m ail DOT com)
   1.2  --  Updated by Omar Shammas
                    (email : omar DOT shammas [a t ] g m ail DOT com)
   1.3  --  Updated by Jesus Federico
                    (email : jesus [a t ] blind side n e t w o rks DOT com)

*/

function bbb_wrap_simplexml_load_file($url)
{
    if (extension_loaded('curl')) {
        $ch = curl_init() or die(curl_error());
        $timeout = 10;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data) {
            return new SimpleXMLElement($data);
        } else {
            return false;
        }
    }

    return simplexml_load_file($url);
}

class BigBlueButton
{
    public $username = array();
    public $meetingid; // the meeting id

    public $welcomestring;
    // the next 2 fields are maybe not needed?!?
    public $moderatorpassword; // the moderator password
    public $attendeepassword; // the attendee pw

    public $securitysalt; // the security salt; gets encrypted with sha1
    public $endpointvalue; // the url the bigbluebutton server is installed
    public $sessionurl; // the url for the administrator to join the sessoin
    public $userurl;

    public $conferenceisrunning = false;

    // this constructor is used to create a BigBlueButton Object
    // use this object to create servers
    // Use is either 0 arguments or all 7 arguments
    public function __construct()
    {
        $numargs = func_num_args();
        // pass the information to the class variables
        if ($numargs >= 6) {
            $this->username = func_get_arg(0);
            $this->meetingid = func_get_arg(1);
            $this->welcomestring = func_get_arg(2);
            $this->moderatorpassword  = func_get_arg(3);
            $this->attendeepassword = func_get_arg(4);
            $this->securitysalt = func_get_arg(5);
            $this->endpointvalue = func_get_arg(6);
        }// end else if
    }

    //------------------------------------------------GET URLs-------------------------------------------------
    /**
    *This method returns the url to join the specified meeting.
    *
    *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
    *@param username -- the display name to be used when the user joins the meeting
    *@param PW -- the attendee or moderator password of the meeting
    *@param SALT -- the security salt of the bigbluebutton server
    *@param URL -- the url of the bigbluebutton server
    *
    *@return The url to join the meeting
    */
    public function getJoinURL($meetingid, $username, $password, $secretvalue, $endpointvalue)
    {
        $url_join = $endpointvalue.'api/join?';
        $params = 'meetingID='.urlencode($meetingid).'&fullName='.urlencode($username).'&password='.urlencode($password);

        return $url_join.$params.'&checksum='.sha1('join'.$params.$secretvalue);
    }

    /**
     *This method returns the url to join the specified meeting.
     *
     *@param name -- a name fot the meeting
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param attendeePW -- the attendee of the meeting
     *@param moderatorPW -- the moderator of the meeting
     *@param welcome -- the welcome message that gets displayed on the chat window
     *@param logoutURL -- the URL that the bbb client will go to after users logouut
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return The url to join the meeting
     */
    public function getCreateMeetingURL($name, $meetingid, $attendeepassword, $moderatorpassword, $welcome, $logouturl, $secretvalue, $endpointvalue, $record = 'false', $duration = 0, $voicebridge = 0, $metadata = array())
    {
        $url_create = $endpointvalue.'api/create?';
        if ($voicebridge == 0) {
            $voicebridge = 70000 + rand(0, 9999);
        }

        $meta = '';
        if ($metadata != '') {
            foreach ($metadata as $key => $value) {
                $meta = $meta.'&'.$key.'='.urlencode($value);
            }
        }

        $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingid).'&attendeePW='.urlencode($attendeepassword).'&moderatorPW='.urlencode($moderatorpassword).'&voiceBridge='.$voicebridge.'&logoutURL='.urlencode($logouturl).'&record='.$record.$meta;

        $duration = intval($duration);
        if ($duration > 0) {
            $params .= '&duration='.$duration;
        }

        if (trim($welcome)) {
            $params .= '&welcome='.urlencode($welcome);
        }

        return  $url_create.$params.'&checksum='.sha1('create'.$params.$secretvalue);
    }

    /**
     *This method returns the url to check if the specified meeting is running.
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return The url to check if the specified meeting is running
     */
    public function getIsMeetingRunningURL($meetingid, $endpointvalue, $secretvalue)
    {
        $base_url = $endpointvalue.'api/isMeetingRunning?';
        $params = 'meetingID='.urlencode($meetingid);

        return $base_url.$params.'&checksum='.sha1('isMeetingRunning'.$params.$secretvalue);
    }



    /**
     *This method returns the url to end the specified meeting.
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param modPW -- the moderator password of the meeting
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return The url to end the specified meeting
     */
    public function getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, $typeurl)
    {
        $base_url = $endpointvalue.'api/'.$typeurl.'?';
        $params = 'meetingID='.urlencode($meetingid).'&password='.urlencode($moderatorpassword);

        return  $base_url.$params.'&checksum='.sha1($typeurl.$params.$secretvalue);
    }

    /**
     *This method returns the url for listing all meetings in the bigbluebutton server.
     *
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return The url of getMeetings
     */
    public function getMeetingsURL($endpointvalue, $secretvalue)
    {
        $base_url = $endpointvalue.'api/getMeetings?';
        $params = 'random='.(rand() * 1000);

        return  $base_url.$params.'&checksum='.sha1('getMeetings'.$params.$secretvalue);
    }



    public function getRecordingsURL($meetingid, $endpointvalue, $secretvalue)
    {
        $baseurlrecord = $endpointvalue.'api/getRecordings?';
        $params = 'meetingID='.urlencode($meetingid);

        return $baseurlrecord.$params.'&checksum='.sha1('getRecordings'.$params.$secretvalue);
    }

    public function getDeleteRecordingsURL($recordid, $endpointvalue, $secretvalue)
    {
        $url_delete = $endpointvalue.'api/deleteRecordings?';
        $params = 'recordID='.urlencode($recordid);

        return $url_delete.$params.'&checksum='.sha1('deleteRecordings'.$params.$secretvalue);
    }

    public function getPublishRecordingsURL($recordid, $set, $endpointvalue, $secretvalue)
    {
        $url_delete = $endpointvalue.'api/publishRecordings?';
        $params = 'recordID='.$recordid.'&publish='.$set;

        return $url_delete.$params.'&checksum='.sha1('publishRecordings'.$params.$secretvalue);
    }

    //-----------------------------------------------CREATE----------------------------------------------------
    /**
    *This method creates a meeting and returnS the join url for moderators.
    *
    *@param username
    *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
    *@param welcomeString -- the welcome message to be displayed when a user logs in to the meeting
    *@param mPW -- the moderator password of the meeting
    *@param aPW -- the attendee password of the meeting
    *@param SALT -- the security salt of the bigbluebutton server
    *@param URL -- the url of the bigbluebutton server
    *@param logoutURL -- the url the user should be redirected to when they logout of bigbluebutton
    *
    *@return The getJoinURL if successful or an error message if unsuccessful
    */
    public function createMeetingAndGetJoinURL($username, $meetingid, $meetingname, $welcomestring, $moderatorpassword, $attendeepassword, $secretvalue, $endpointvalue, $logouturl, $record = 'false', $duration = 0, $voicebridge = 0, $metadata = array())
    {
        $xml = bbb_wrap_simplexml_load_file(self::getCreateMeetingURL($meetingname, $meetingid, $attendeepassword, $moderatorpassword, $welcomestring, $logouturl, $secretvalue, $endpointvalue, $record, $duration, $voicebridge, $metadata));

        if ($xml && $xml->returncode == 'SUCCESS') {
            return  self::getJoinURL($meetingid, $username, $moderatorpassword, $secretvalue, $endpointvalue);
        } elseif ($xml) {
            return  (string) $xml->messageKey.' : '.(string) $xml->message;
        } else {
            return 'Unable to fetch URL '.$url_create.$params.'&checksum='.sha1('create'.$params.$secretvalue);
        }
    }

    /**
     *This method creates a meeting and return an array of the xml packet.
     *
     *@param username
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param welcomeString -- the welcome message to be displayed when a user logs in to the meeting
     *@param mPW -- the moderator password of the meeting
     *@param aPW -- the attendee password of the meeting
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *@param logoutURL -- the url the user should be redirected to when they logout of bigbluebutton
     *
     *@return
     *	- Null if unable to reach the bigbluebutton server
     *	- If failed it returns an array containing a returncode, messageKey, message.
     *	- If success it returns an array containing a returncode, messageKey, message, meetingID, attendeePW, moderatorPW, hasBeenForciblyEnded
     */
    public function createMeetingArray($meetingid, $meetingname, $welcomestring, $moderatorpassword, $attendeepassword, $secretvalue, $endpointvalue, $logouturl, $record = 'false', $duration = 0, $voicebridge = 0, $metadata = array())
    {
        $xml = bbb_wrap_simplexml_load_file(self::getCreateMeetingURL($meetingname, $meetingid, $attendeepassword, $moderatorpassword, $welcomestring, $logouturl, $secretvalue, $endpointvalue, $record, $duration, $voicebridge, $metadata));

        if ($xml) {
            if ($xml->meetingID) {
                return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey, 'meetingID' => (string) $xml->meetingID, 'attendeePW' => (string) $xml->attendeePW, 'moderatorPW' => (string) $xml->moderatorPW, 'hasBeenForciblyEnded' => (string) $xml->hasBeenForciblyEnded);
            } else {
                return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
            }
        } else {
            return null;
        }
    }

    //-------------------------------------------getMeetingInfo---------------------------------------------------
    /**
    *This method calls the getMeetingInfo on the bigbluebutton server and returns an xml packet.
    *
    *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
    *@param modPW -- the moderator password of the meeting
    *@param SALT -- the security salt of the bigbluebutton server
    *@param URL -- the url of the bigbluebutton server
    *
    *@return An xml packet.
    *	If failed it returns an xml packet containing a returncode, messagekey, and message.
    *	If success it returnsan xml packet containing a returncode,
    */
    public function getMeetingInfo($meetingid, $moderatorpassword, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, 'getMeetingInfo'));
        if ($xml) {
            return  str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML()));
        }

        return false;
    }

    /**
     *This method calls the getMeetingInfo on the bigbluebutton server and returns an array.
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param modPW -- the moderator password of the meeting
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return An Array.
     *	- Null if unable to reach the bigbluebutton server
     *	- If failed it returns an array containing a returncode, messagekey, message.
     *	- If success it returns an array containing a meetingID, moderatorPW, attendeePW, hasBeenForciblyEnded, running, startTime, endTime,
     */
    public function getMeetingInfoArray($meetingid, $moderatorpassword, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, 'getMeetingInfo'));

        if ($xml && $xml->returncode == 'SUCCESS') { //If there were meetings already created
            return array('returncode' => (string) $xml->returncode, 'meetingID' => (string) $xml->meetingID, 'moderatorPW' => (string) $xml->moderatorPW, 'attendeePW' => (string) $xml->attendeePW, 'hasBeenForciblyEnded' => (string) $xml->hasBeenForciblyEnded, 'running' => (string) $xml->running, 'startTime' => (string) $xml->startTime, 'endTime' => (string) $xml->endTime, 'participantCount' => (string) $xml->participantCount, 'moderatorCount' => (string) $xml->moderatorCount, 'attendees' => (string) $xml->attendees);
        }
        checkXMLFaliure($xml);
    }

function checkXMLFaliure($xml){
  if (($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
      return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
  } else { //If the server is unreachable, then prompts the user of the necessary action
      return null;
  }
}
    //-----------------------------------------------getMeetings------------------------------------------------------
    /**
    *This method calls getMeetings on the bigbluebutton server, then calls getMeetingInfo for each meeting and concatenates the result.
    *
    *@param URL -- the url of the bigbluebutton server
    *@param SALT -- the security salt of the bigbluebutton server
    *
    *@return
    *	- If failed then returns a boolean of false.
    *	- If succeeded then returns an xml of all the meetings
    */
    public function getMeetings($endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingsURL($endpointvalue, $secretvalue));
        if ($xml && $xml->returncode == 'SUCCESS') {
            if ((string) $xml->messageKey) {
                return  $xml->message->asXML();
            }
            ob_start();
            echo '<meetings>';
            if (count($xml->meetings) && count($xml->meetings->meeting)) {
                foreach ($xml->meetings->meeting as $meeting) {
                    echo '<meeting>';
                    echo self::getMeetingInfo($meeting->meetingID, $meeting->moderatorPW, $endpointvalue, $secretvalue);
                    echo '</meeting>';
                }
            }
            echo '</meetings>';

            return ob_get_clean();
        } else {
            return false;
        }
    }

    /**
     *This method calls getMeetings on the bigbluebutton server, then calls getMeetingInfo for each meeting and concatenates the result.
     *
     *@param URL -- the url of the bigbluebutton server
     *@param SALT -- the security salt of the bigbluebutton server
     *
     *@return
     *	- Null if the server is unreachable
     *	- If FAILED then returns an array containing a returncode, messageKey, message.
     *	- If SUCCESS then returns an array of all the meetings. Each element in the array is an array containing a meetingID,
     */
    public function getMeetingsArray($endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingsURL($endpointvalue, $secretvalue));

        if ($xml && $xml->returncode == 'SUCCESS' && $xml->messageKey) {
            //The meetings were returned
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } elseif ($xml && $xml->returncode == 'SUCCESS') { //If there were meetings already created

            foreach ($xml->meetings->meeting as $meeting) {
                $meetings = array();
                $meetings[] = array('meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running);
            }

            return $meetings;
        }
        checkXMLFaliure($xml);
    }

    public function getRecordingsArray($meetingid, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getRecordingsURL($meetingid, $endpointvalue, $secretvalue));
        if ($xml && $xml->returncode == 'SUCCESS' && $xml->messageKey) {
            //The meetings were returned
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } elseif ($xml && $xml->returncode == 'SUCCESS') { //If there were meetings already created
            $recordings = array();

            foreach ($xml->recordings->recording as $recording) {
                $playbackarray = array();
                foreach ($recording->playback->format as $format) {
                    $playbackarray[(string) $format->type] = array('type' => (string) $format->type, 'url' => (string) $format->url);
                }

                //Add the metadata to the recordings array
                $metadataarray = array();
                $metadata = get_object_vars($recording->metadata);
                foreach ($metadata as $key => $value) {
                    if (is_object($value)) {
                        $value = '';
                    }
                    $metadataarray['meta_'.$key] = $value;
                }

                $recordings[] = array('recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime, 'playbacks' => $playbackarray) + $metadataarray;
            }

            usort($recordings, 'BigBlueButton::recordingBuildSorter');

            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey, 'recordings' => $recordings);
        }
        checkXMLFaliure($xml);
    }

    private function recordingBuildSorter($a, $b)
    {
        if ($a['startTime'] < $b['startTime']) {
            return -1;
        } elseif ($a['startTime'] == $b['startTime']) {
            return 0;
        } else {
            return 1;
        }
    }

    //----------------------------------------------getUsers---------------------------------------
    /**
    *This method prints the usernames of the attendees in the specified conference.
    *
    *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
    *@param modPW -- the moderator password of the meeting
    *@param URL -- the url of the bigbluebutton server
    *@param SALT -- the security salt of the bigbluebutton server
    *@param UNAME -- is a boolean to determine how the username is formatted when printed. Default if false
    *
    *@return A boolean of true if the attendees were printed successfully and false otherwise
    */
    public function getUsers($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, $username = false)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, 'getMeetingInfo'));
        if ($xml && $xml->returncode == 'SUCCESS') {
            ob_start();
            if (count($xml->attendees) && count($xml->attendees->attendee)) {
                foreach ($xml->attendees->attendee as $attendee) {
                    if ($username === true) {
                        echo 'User name: '.$attendee->fullName.'<br />';
                    } else {
                        echo $attendee->fullName.'<br />';
                    }
                }
            }

            return ob_end_flush();
        } else {
            return false;
        }
    }

    /**
     *This method returns an array of the attendees in the specified meeting.
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param modPW -- the moderator password of the meeting
     *@param URL -- the url of the bigbluebutton server
     *@param SALT -- the security salt of the bigbluebutton server
     *
     *@return
     *	- Null if the server is unreachable.
     *	- If FAILED, returns an array containing a returncode, messageKey, message.
     *	- If SUCCESS, returns an array of array containing the userID, fullName, role of each attendee
     */
    public function getUsersArray($meetingid, $moderatorpassword, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, 'getMeetingInfo'));

        if ($xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null) {
            //The meetings were returned
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } elseif ($xml && $xml->returncode == 'SUCCESS') { //If there were meetings already created
            foreach ($xml->attendees->attendee as $attendee) {
                $users = array();
                $users[] = array('userID' => $attendee->userID, 'fullName' => $attendee->fullName, 'role' => $attendee->role);
            }

            return $users;
        }
        checkXMLFaliure($xml);
    }

    //------------------------------------------------Other Methods------------------------------------
    /**
    *This method calls end meeting on the specified meeting in the bigbluebutton server.
    *
    *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
    *@param modPW -- the moderator password of the meeting
    *@param SALT -- the security salt of the bigbluebutton server
    *@param URL -- the url of the bigbluebutton server
    *
    *@return
    *	- Null if the server is unreachable
    * 	- An array containing a returncode, messageKey, message
    */
    public function endMeeting($meetingid, $moderatorpassword, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getMeetingURL($meetingid, $moderatorpassword, $endpointvalue, $secretvalue, 'end'));

        if ($xml) { //If the xml packet returned failure it displays the message to the user
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } else { //If the server is unreachable, then prompts the user of the necessary action
            return null;
        }
    }

    public function doDeleteRecordings($recordids, $endpointvalue, $secretvalue)
    {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bbb_wrap_simplexml_load_file(self::getDeleteRecordingsURL($id, $endpointvalue, $secretvalue));
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }

        return true;
    }

    public function doPublishRecordings($recordids, $set, $endpointvalue, $secretvalue)
    {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bbb_wrap_simplexml_load_file(self::getPublishRecordingsURL($id, $set, $endpointvalue, $secretvalue));
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }

        return true;
    }

    public function getServerVersion($endpointvalue)
    {
        $baseurlrecord = $endpointvalue.'api';

        $xml = bbb_wrap_simplexml_load_file($baseurlrecord);
        if ($xml && $xml->returncode == 'SUCCESS') {
            return $xml->version;
        } else {
            return null;
        }
    }

    /**
     *This method check the BigBlueButton server to see if the meeting is running (i.e. there is someone in the meeting).
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return A boolean of true if the meeting is running and false if it is not running
     */
    public function isMeetingRunning($meetingid, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getIsMeetingRunningURL($meetingid, $endpointvalue, $secretvalue));
        if ($xml && $xml->returncode == 'SUCCESS') {
            return  ((string) $xml->running == 'true') ? true : false;
        } else {
            return  false;
        }
    }

    /**
     *This method calls isMeetingRunning on the BigBlueButton server.
     *
     *@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
     *@param SALT -- the security salt of the bigbluebutton server
     *@param URL -- the url of the bigbluebutton server
     *
     *@return
     * 	- If SUCCESS it returns an xml packet
     * 	- If the FAILED or the server is unreachable returns a string of 'false'
     */
    public function getMeetingXML($meetingid, $endpointvalue, $secretvalue)
    {
        $xml = bbb_wrap_simplexml_load_file(self::getIsMeetingRunningURL($meetingid, $endpointvalue, $secretvalue));
        if ($xml && $xml->returncode == 'SUCCESS') {
            return  str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML()));
        } else {
            return 'false';
        }
    }
}
