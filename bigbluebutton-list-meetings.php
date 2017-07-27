<?php

//================================================================================
//---------------------------------List Meetings----------------------------------
//================================================================================
// Displays all the meetings available in the bigbluebutton server
function bigbluebutton_list_meetings() {
    global $wpdb, $wp_version, $current_site, $current_user;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= "<h2>List of Meeting Rooms</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    if( isset($_POST['SubmitList']) ) { //Creates then joins the meeting. If any problems occur the error is displayed
        // Read the posted value and delete
        $meetingID = $_POST['meetingID'];
        $sql = "SELECT * FROM ".$table_name." WHERE meetingID = %s";
        $found = $wpdb->get_row(
                $wpdb->prepare($sql, $meetingID)
        );

        if( $found ) {
            $found->meetingID = bigbluebutton_normalizeMeetingID($found->meetingID);

            //---------------------------------------------------JOIN-------------------------------------------------
            if($_POST['SubmitList'] == 'Join') {
            	//Extra parameters
            	$duration = 0;
            	$voicebridge = 0;
            	$logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];

            	//Metadata for tagging recordings
            	$metadata = array(
            			'meta_origin' => 'WordPress',
            			'meta_originversion' => $wp_version,
            			'meta_origintag' => 'wp_plugin-bigbluebutton '.BIGBLUEBUTTON_PLUGIN_VERSION,
            			'meta_originservername' => home_url(),
            			'meta_originservercommonname' => get_bloginfo('name'),
            			'meta_originurl' => $logouturl
            	);

            	//Calls create meeting on the bigbluebutton server
            	$welcome = BIGBLUEBUTTON_STRING_WELCOME;
            	if( $recorded ) $welcome .= BIGBLUEBUTTON_STRING_MEETING_RECORDED;
            	$response = BigBlueButton::createMeetingArray($current_user->display_name, $found->meetingID, $found->meetingName, $welcome, $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, $logouturl, ($found->recorded? 'true':'false'), $duration, $voicebridge, $metadata );

            	$createNew = false;
            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to join the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] == 'FAILED' ) { //The meeting was not created
            		if($response['messageKey'] == 'idNotUnique') {
            			$createNew = true;
            		}
            		else if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}
            	else{
            		if( !isset($response['messageKey']) || $response['messageKey'] == '' ) {
            			// The meeting was just created, insert the create event to the log
            			$rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $found->meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
            		}

            		$bigbluebutton_joinURL = BigBlueButton::getJoinURL($found->meetingID, $current_user->display_name, $found->moderatorPW, $salt_val, $url_val );
            		$out .= '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'"; </script>'."\n";
            	}

            }
            //---------------------------------------------------END-------------------------------------------------
            else if($_POST['SubmitList'] == 'End' ) { //Obtains the meeting information of the meeting that is going to be terminated

            	//Calls endMeeting on the bigbluebutton server
            	$response = BigBlueButton::endMeeting($found->meetingID, $found->moderatorPW, $url_val, $salt_val );

            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to terminate the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] == 'SUCCESS' ) { //The meeting was terminated
            	    $out .= '<div class="updated"><p><strong>'.$found->meetingName.' meeting has been terminated.</strong></p></div>';

            		//In case the meeting is created again it sets the meeting version to the time stamp. Therefore the meeting can be recreated before the 1 hour rule without any problems.
            		$meetingVersion = time();
            		$wpdb->update( $table_name, array( 'meetingVersion' => $meetingVersion), array( 'meetingID' => $found->meetingID ));

            	}
            	else{ //If the meeting was unable to be termindated
            		if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}



            }
            //---------------------------------------------------DELETE-------------------------------------------------
            else if($_POST['SubmitList'] == 'Delete' ) { //Obtains the meeting information of the meeting that is going to be delete

            	//Calls endMeeting on the bigbluebutton server
            	$response = BigBlueButton::endMeeting($found->meetingID, $found->moderatorPW, $url_val, $salt_val );

            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to delete the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] != 'SUCCESS' && $response['messageKey'] != 'notFound' ) { //If the meeting was unable to be deleted due to an error
            		if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}
            	else { //The meeting was terminated
            	    $sql = "DELETE FROM ".$table_name." WHERE meetingID = %s";
            	    $wpdb->query(
            	            $wpdb->prepare($sql, $meetingID)
            	    );

            	    $out .= '<div class="updated"><p><strong>'.$found->meetingName.' meeting has been deleted.</strong></p></div>';
            	}

            }
        }
    }

    //Gets all the meetings from the wordpress db
    $listOfMeetings = $wpdb->get_results("SELECT * FROM ".$table_name." ORDER BY id");

    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfMeetings) == 0) {
        $out .= '<div class="updated"><p><strong>There are no meeting rooms.</strong></p></div>';
        return $out;
    }

    //Iinitiallizes the table
    $printed = false;
    //Displays the meetings in the wordpress database that have not been created yet. Avoids displaying
    //duplicate meetings, meaning if the same meeting already exists in the bigbluebutton server then it is
    //not displayed again in this for loop
    foreach ($listOfMeetings as $meeting) {
        $info = BigBlueButton::getMeetingInfoArray( bigbluebutton_normalizeMeetingID($meeting->meetingID), $meeting->moderatorPW, $url_val, $salt_val);
        //Analyzes the bigbluebutton server's response
        if(!$info) {//If the server is unreachable, then prompts the user of the necessary action
            $out .= '<div class="updated"><p><strong>Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            return $out;
        } else if( $info['returncode'] == 'FAILED' && $info['messageKey'] != 'notFound' && $info['messageKey'] != 'invalidPassword') { /// If the meeting was unable to be deleted due to an error
            if($info['messageKey'] == 'checksumError') {
                $out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            }
            else{
                $out .= '<div class="updated"><p><strong>'.$info['message'].'</strong></p></div>';
            }
            return $out;
        } else if( $info['returncode'] == 'FAILED' && ($info['messageKey'] == 'notFound' || $info['messageKey'] != 'invalidPassword') ) { /// The meeting exists only in the wordpress db
            if(!$printed) {
                $out .= bigbluebutton_print_table_header();
                $printed = true;
            }
            $out .= '
            <form name="form1" method="post" action="">
            <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
            <tr>
            <td>'.$meeting->meetingName.'</td>
            <td>'.$meeting->meetingID.'</td>
            <td>'.$meeting->attendeePW.'</td>
            <td>'.$meeting->moderatorPW.'</td>
            <td>'.($meeting->waitForModerator? 'Yes': 'No').'</td>
            <td>'.($meeting->recorded? 'Yes': 'No').'</td>
            <td><input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
            <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />
            </td>
            </tr>
            </form>';
        } else { /// The meeting exists in the bigbluebutton server

            if(!$printed) {
                $out .= bigbluebutton_print_table_header();
                $printed = true;
            }

            $out .= '
            <form name="form1" method="post" action="">
            <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
            <tr>
            <td>'.$meeting->meetingName.'</td>
            <td>'.$meeting->meetingID.'</td>
            <td>'.$meeting->attendeePW.'</td>
            <td>'.$meeting->moderatorPW.'</td>
            <td>'.($meeting->waitForModerator? 'Yes': 'No').'</td>
            <td>'.($meeting->recorded? 'Yes': 'No').'</td>';
            if( isset($info['hasBeenForciblyEnded']) && $info['hasBeenForciblyEnded']=='false') {
                $out .= '
                <td><input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
                <input type="submit" name="SubmitList" class="button-primary" value="End" onClick="return confirm(\'Are you sure you want to end the meeting?\')" />&nbsp;
                <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />
                </td>';
            } else {
                $out .= '
                <td>
                <!-- Meeting has ended and is temporarily unavailable. -->
                <input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
                <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />&nbsp;
                </td>';
            }
            $out .= '	</tr>
            </form>';
        }
    }

    $out .= '
    </table>
    </div><hr />';

    return $out;
}
