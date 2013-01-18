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

function bbb_wrap_simplexml_load_file($url){
	
	if (extension_loaded('curl')) {
		$ch = curl_init() or die ( curl_error() );
		$timeout = 10;
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);	
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec( $ch );
		curl_close( $ch );
		
		if($data)
			return (new SimpleXMLElement($data));
		else
			return false;
	}
	
	return (simplexml_load_file($url));	
}

class BigBlueButton {
	
	var $userName = array();
	var $meetingID; // the meeting id
	
	var $welcomeString;
	// the next 2 fields are maybe not needed?!?
	var $modPW; // the moderator password 
	var $attPW; // the attendee pw
	
	var $securitySalt; // the security salt; gets encrypted with sha1
	var $URL; // the url the bigbluebutton server is installed
	var $sessionURL; // the url for the administrator to join the sessoin
	var $userURL;
	
	var $conferenceIsRunning = false;
	
	// this constructor is used to create a BigBlueButton Object
	// use this object to create servers
	// Use is either 0 arguments or all 7 arguments
	public function __construct() {
		$numargs = func_num_args();

		if( $numargs == 0 ) {
		}
		// pass the information to the class variables
		else if( $numargs >= 6 ) {
			$this->userName = func_get_arg(0);
			$this->meetingID = func_get_arg(1);
			$this->welcomeString = func_get_arg(2);
			$this->modPW = func_get_arg(3);
			$this->attPW = func_get_arg(4);
			$this->securitySalt = func_get_arg(5);
			$this->URL = func_get_arg(6);
			

			$arg_list = func_get_args();
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
	public function getJoinURL( $meetingID, $userName, $PW, $SALT, $URL ) {
		$url_join = $URL."api/join?";
		$params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.urlencode($PW);
		return ($url_join.$params.'&checksum='.sha1("join".$params.$SALT) );
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
	public function getCreateMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $metadata = array() ) {
		$url_create = $URL."api/create?";
		if ( $voiceBridge == 0)
			$voiceBridge = 70000 + rand(0, 9999);
	
		$meta = '';
		foreach ($metadata as $key => $value) {
			$meta = $meta.'&'.$key.'='.urlencode($value);
		}
	
		$params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&voiceBridge='.$voiceBridge.'&logoutURL='.urlencode($logoutURL).'&record='.$record.$meta;
	
		$duration = intval($duration);
		if( $duration > 0 )
			$params .= '&duration='.$duration;
	
		if( trim( $welcome ) )
			$params .= '&welcome='.urlencode($welcome);
	
		return ( $url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
	}
	
	
	/**
	*This method returns the url to check if the specified meeting is running.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
	*@param SALT -- the security salt of the bigbluebutton server
	*@param URL -- the url of the bigbluebutton server
	*
	*@return The url to check if the specified meeting is running.
	*/
	public function getIsMeetingRunningURL( $meetingID, $URL, $SALT ) {
		$base_url = $URL."api/isMeetingRunning?";
		$params = 'meetingID='.urlencode($meetingID);
		return ($base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT) );	
	}

	/**
	*This method returns the url to getMeetingInfo of the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebutton server
	*@param URL -- the url of the bigbluebutton server
	*
	*@return The url to check if the specified meeting is running.
	*/
	public function getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) {
		$base_url = $URL."api/getMeetingInfo?";
		$params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
		return ( $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT));	
	}
	
	/**
	*This method returns the url for listing all meetings in the bigbluebutton server.
	*
	*@param SALT -- the security salt of the bigbluebutton server
	*@param URL -- the url of the bigbluebutton server
	*
	*@return The url of getMeetings.
	*/
	public function getMeetingsURL($URL, $SALT) { 
		$base_url = $URL."api/getMeetings?";
		$params = 'random='.(rand() * 1000 );
		return ( $base_url.$params.'&checksum='.sha1("getMeetings".$params.$SALT));
	}

	/**
	*This method returns the url to end the specified meeting.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
	*@param modPW -- the moderator password of the meeting
	*@param SALT -- the security salt of the bigbluebutton server
	*@param URL -- the url of the bigbluebutton server
	*
	*@return The url to end the specified meeting.
	*/
	public function getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) {
		$base_url = $URL."api/end?";
		$params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
		return ( $base_url.$params.'&checksum='.sha1("end".$params.$SALT) );
	}	

	
	public function getRecordingsURL($meetingID, $URL, $SALT ) {
	    $base_url_record = $URL."api/getRecordings?";
	    $params = "meetingID=".urlencode($meetingID);
	
	    return ($base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT) );
	}
	
	public function getDeleteRecordingsURL( $recordID, $URL, $SALT ) {
	    $url_delete = $URL."api/deleteRecordings?";
	    $params = 'recordID='.urlencode($recordID);
	    return ($url_delete.$params.'&checksum='.sha1("deleteRecordings".$params.$SALT) );
	}
	
	public function getPublishRecordingsURL( $recordID, $set, $URL, $SALT ) {
	    $url_delete = $URL."api/publishRecordings?";
	    $params = 'recordID='.$recordID."&publish=".$set;
	    return ($url_delete.$params.'&checksum='.sha1("publishRecordings".$params.$SALT) );
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
	public function createMeetingAndGetJoinURL( $username, $meetingID, $meetingName, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $metadata = array() ) {

		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getCreateMeetingURL($meetingName, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );
		
		if( $xml && $xml->returncode == 'SUCCESS' ) {
			return ( BigBlueButton::getJoinURL( $meetingID, $username, $mPW, $SALT, $URL ) );
		}	
		else if( $xml ) {
			return ( (string)$xml->messageKey.' : '.(string)$xml->message );
		}
		else {
			return ('Unable to fetch URL '.$url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
		}
	}

	/**
	*This method creates a meeting and return an array of the xml packet
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
	*	- If success it returns an array containing a returncode, messageKey, message, meetingID, attendeePW, moderatorPW, hasBeenForciblyEnded.
	*/
	public function createMeetingArray( $username, $meetingID, $meetingName, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $metadata = array() ) {
	
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getCreateMeetingURL($meetingName, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );
		
		if( $xml ) {
			if($xml->meetingID) 
			    return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey, 'meetingID' => (string)$xml->meetingID, 'attendeePW' => (string)$xml->attendeePW, 'moderatorPW' => (string)$xml->moderatorPW, 'hasBeenForciblyEnded' => (string)$xml->hasBeenForciblyEnded );
			else 
			    return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey );
		}
		else {
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
	public function getMeetingInfo( $meetingID, $modPW, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
		if($xml){
			return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
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
		  participantCount, moderatorCount, attendees.
	*/
	public function getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
				
        if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
			return array( 'returncode' => (string)$xml->returncode, 'meetingID' => (string)$xml->meetingID, 'moderatorPW' => (string)$xml->moderatorPW, 'attendeePW' => (string)$xml->attendeePW, 'hasBeenForciblyEnded' => (string)$xml->hasBeenForciblyEnded, 'running' => (string)$xml->running, 'startTime' => (string)$xml->startTime, 'endTime' => (string)$xml->endTime, 'participantCount' => (string)$xml->participantCount, 'moderatorCount' => (string)$xml->moderatorCount, 'attendees' => (string)$xml->attendees );
		}
		else if( ($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
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
	*	- If succeeded then returns an xml of all the meetings.
	*/
	public function getMeetings( $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingsURL( $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' ) {
			if( (string)$xml->messageKey )
				return ( $xml->message->asXML() );	
			ob_start();
			echo '<meetings>';
			if( count( $xml->meetings ) && count( $xml->meetings->meeting ) ) {
				foreach ($xml->meetings->meeting as $meeting)
				{
					echo '<meeting>';
					echo BigBlueButton::getMeetingInfo($meeting->meetingID, $meeting->moderatorPW, $URL, $SALT);
					echo '</meeting>';
				}
			}
			echo '</meetings>';
			return (ob_get_clean());
		}
		else {
			return (false);
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
		  moderatorPW, attendeePW, hasBeenForciblyEnded, running.
	*/
	public function getMeetingsArray( $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingsURL( $URL, $SALT ) );

		if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
		
			foreach ($xml->meetings->meeting as $meeting) {
				$meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
			}

			return $meetings;

		}
		else if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}
	}
	
    public function getRecordingsArray($meetingID, $URL, $SALT ) {
        $xml = bbb_wrap_simplexml_load_file( BigBlueButton::getRecordingsURL( $meetingID, $URL, $SALT ) );
        if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
            return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
        } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
            $recordings = array();

            foreach ($xml->recordings->recording as $recording) {
                $playbackArray = array();
                foreach ( $recording->playback->format as $format ){
                    $playbackArray[(string) $format->type] = array( 'type' => (string) $format->type, 'url' => (string) $format->url );
                }

                //Add the metadata to the recordings array
               $metadataArray = array();
                $metadata = get_object_vars($recording->metadata);
                foreach ($metadata as $key => $value) {
                    if(is_object($value)) $value = '';
                    $metadataArray['meta_'.$key] = $value;
                }
    
                $recordings[] = array( 'recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime, 'playbacks' => $playbackArray ) + $metadataArray;

            }
	
            usort($recordings, 'BigBlueButton::recordingBuildSorter');
            return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey, 'recordings' => $recordings);
	
        } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
            return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
        } else { //If the server is unreachable, then prompts the user of the necessary action
            return NULL;
        }
    }

    private function recordingBuildSorter($a, $b){
    	if( $a['startTime'] < $b['startTime']) return -1;
    	else if( $a['startTime'] == $b['startTime']) return 0;
    	else return 1;
    }
    
	//----------------------------------------------getUsers---------------------------------------
	/**
	*This method prints the usernames of the attendees in the specified conference.
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
	*@param modPW -- the moderator password of the meeting
	*@param URL -- the url of the bigbluebutton server
	*@param SALT -- the security salt of the bigbluebutton server
	*@param UNAME -- is a boolean to determine how the username is formatted when printed. Default if false.
	*
	*@return A boolean of true if the attendees were printed successfully and false otherwise.
	*/
	public function getUsers( $meetingID, $modPW, $URL, $SALT, $UNAME = false ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' ) {
			ob_start();
			if( count( $xml->attendees ) && count( $xml->attendees->attendee ) ) {
				foreach ( $xml->attendees->attendee as $attendee ) {
					if( $UNAME  == true ) {
						echo "User name: ".$attendee->fullName.'<br />';
					}
					else {
						echo $attendee->fullName.'<br />';
					}
				}
			}
			return (ob_end_flush());
		}
		else {
			return (false);
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
	public function getUsersArray( $meetingID, $modPW, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

		if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null ) {//The meetings were returned
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
			foreach ($xml->attendees->attendee as $attendee){
					$users[] = array(  'userID' => $attendee->userID, 'fullName' => $attendee->fullName, 'role' => $attendee->role );
			}
			return $users;
		}
		else if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}
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
	* 	- An array containing a returncode, messageKey, message.
	*/
	public function endMeeting( $meetingID, $modPW, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) );

		if( $xml ) { //If the xml packet returned failure it displays the message to the user
			return array('returncode' => (string)$xml->returncode, 'message' => (string)$xml->message, 'messageKey' => (string)$xml->messageKey);
		}
		else { //If the server is unreachable, then prompts the user of the necessary action
			return null;
		}

	}

	public function doDeleteRecordings( $recordIDs, $URL, $SALT ) {
	
	    $ids = 	explode(",", $recordIDs);
	    foreach( $ids as $id){
	        $xml = bbb_wrap_simplexml_load_file( BigBlueButton::getDeleteRecordingsURL($id, $URL, $SALT) );
	        if( $xml && $xml->returncode != 'SUCCESS' )
	            return false;
	    }
	    return true;
	}
	
	public function doPublishRecordings( $recordIDs, $set, $URL, $SALT ) {
	    $ids = 	explode(",", $recordIDs);
	    foreach( $ids as $id){
	        $xml = bbb_wrap_simplexml_load_file( BigBlueButton::getPublishRecordingsURL($id, $set, $URL, $SALT) );
	        if( $xml && $xml->returncode != 'SUCCESS' )
	            return false;
	    }
	    return true;
	}
	
	public function getServerVersion( $URL ){
	    $base_url_record = $URL."api";
	
	    $xml = bbb_wrap_simplexml_load_file( $base_url_record );
	    if( $xml && $xml->returncode == 'SUCCESS' )
	        return $xml->version;
	    else
	        return NULL;
	
	}
	
	
	/**
	*This method check the BigBlueButton server to see if the meeting is running (i.e. there is someone in the meeting)
	*
	*@param meetingID -- the unique meeting identifier used to store the meeting in the bigbluebutton server
	*@param SALT -- the security salt of the bigbluebutton server
	*@param URL -- the url of the bigbluebutton server
	*
	*@return A boolean of true if the meeting is running and false if it is not running
	*/
	public function isMeetingRunning( $meetingID, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS' ) 
			return ( ( (string)$xml->running == 'true' ) ? true : false);
		else
			return ( false );
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
	public function getMeetingXML( $meetingID, $URL, $SALT ) {
		$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
		if( $xml && $xml->returncode == 'SUCCESS') 
			return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
		else
			return 'false';	
	}

}
?>
