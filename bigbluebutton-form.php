<?php
//================================================================================
//Create the form called by the Shortcode and Widget functions
function bigbluebutton_form($args, $bigbluebutton_form_in_widget = false) {
    global $wpdb, $wp_version, $current_site, $current_user, $wp_roles;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";

    $token = isset($args['token']) ?$args['token']: null;
    $tokens = isset($args['tokens']) ?$args['tokens']: null;
    $submit = isset($args['submit']) ?$args['submit']: null;

    //Initializes the variable that will collect the output
    $out = '';

    //Set the role for the current user if is logged in
    $role = null;
    if( $current_user->ID ) {
        $role = "unregistered";
        foreach($wp_roles->role_names as $_role => $Role) {
            if (array_key_exists($_role, $current_user->caps)) {
                $role = $_role;
                break;
            }
        }
    } else {
        $role = "anonymous";
    }

    //Read in existing option value from database
    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');
    //Read in existing permission values from database
    $permissions = get_option('bigbluebutton_permissions');

    //Gets all the meetings from wordpress database
    $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW FROM ".$table_name." ORDER BY meetingName");

    $dataSubmitted = false;
    $meetingExist = false;
    if( isset($_POST['SubmitForm']) ) { //The user has submitted his login information
        $dataSubmitted = true;
        $meetingExist = true;

        $meetingID = $_POST['meetingID'];

        $sql = "SELECT * FROM ".$table_name." WHERE meetingID = %s";
        $found = $wpdb->get_row(
                $wpdb->prepare($sql, $meetingID)
        );
        if( $found ) {
            $found->meetingID = bigbluebutton_normalizeMeetingID($found->meetingID);

            if( !$current_user->ID ) {
                $name = isset($_POST['display_name']) && $_POST['display_name']? htmlspecialchars($_POST['display_name']): $role;

                if( bigbluebutton_validate_defaultRole($role, 'none') ) {
                    $password = $_POST['pwd'];
                } else {
                    $password = $permissions[$role]['defaultRole'] == 'none'? $found->moderatorPW: $found->attendeePW;
                }

            } else {
                if( $current_user->display_name != '' ) {
                    $name = $current_user->display_name;
                } else if( $current_user->user_firstname != '' || $current_user->user_lastname != '' ) {
                    $name = $current_user->user_firstname != ''? $current_user->user_firstname.' ': '';
                    $name .= $current_user->user_lastname != ''? $current_user->user_lastname.' ': '';
                } else if( $current_user->user_login != '') {
                    $name = $current_user->user_login;
                } else {
                    $name = $role;
                }
                if( bigbluebutton_validate_defaultRole($role, 'none') ) {
                    $password = $_POST['pwd'];
                } else {
                    $password = $permissions[$role]['defaultRole'] == 'moderator'? $found->moderatorPW: $found->attendeePW;
                }

            }

            //Extra parameters
            $recorded = $found->recorded;
            $welcome = (isset($args['welcome']))? html_entity_decode($args['welcome']): BIGBLUEBUTTON_STRING_WELCOME;
            if( $recorded ) $welcome .= BIGBLUEBUTTON_STRING_MEETING_RECORDED;
            $duration = 0;
            $voicebridge = (isset($args['voicebridge']))? html_entity_decode($args['voicebridge']): 0;
            $logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];

            //Metadata for tagging recordings
            $metadata = Array(
                    'meta_origin' => 'WordPress',
                    'meta_originversion' => $wp_version,
                    'meta_origintag' => 'wp_plugin-bigbluebutton '.BIGBLUEBUTTON_PLUGIN_VERSION,
                    'meta_originservername' => home_url(),
                    'meta_originservercommonname' => get_bloginfo('name'),
                    'meta_originurl' => $logouturl
            );
            //Call for creating meeting on the bigbluebutton server
            $response = BigBlueButton::createMeetingArray($name, $found->meetingID, $found->meetingName, $welcome, $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, $logouturl, $recorded? 'true':'false', $duration, $voicebridge, $metadata );

            //Analyzes the bigbluebutton server's response
            if(!$response || $response['returncode'] == 'FAILED' ) {//If the server is unreachable, or an error occured
                $out .= "Sorry an error occured while joining the meeting.";
                return $out;

            } else{ //The user can join the meeting, as it is valid
                if( !isset($response['messageKey']) || $response['messageKey'] == '' ) {
                    // The meeting was just created, insert the create event to the log
                    $rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $found->meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
                }

                $bigbluebutton_joinURL = BigBlueButton::getJoinURL($found->meetingID, $name, $password, $salt_val, $url_val );
                //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
                //do not wait for moderator option is set to false then the user is immediately redirected to the meeting
                if ( (BigBlueButton::isMeetingRunning( $found->meetingID, $url_val, $salt_val ) && ($found->moderatorPW == $password || $found->attendeePW == $password ) )
                        || $response['moderatorPW'] == $password
                        || ($response['attendeePW'] == $password && !$found->waitForModerator)  ) {
                    //If the password submitted is correct then the user gets redirected
                    $out .= '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'";</script>'."\n";
                    return $out;
                }
                //If the viewer has the correct password, but the meeting has not yet started they have to wait
                //for the moderator to start the meeting
                else if ($found->attendeePW == $password) {
                    //Stores the url and salt of the bigblubutton server in the session
                    $_SESSION['mt_bbb_url'] = $url_val;
                    $_SESSION['mt_salt'] = $salt_val;
                    //Displays the javascript to automatically redirect the user when the meeting begins
                    $out .= bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $found->meetingID, $found->meetingName, $name);
                    return $out;
                }
            }
        }
    }

    //If a valid meeting was found the login form is displayed
    if(sizeof($listOfMeetings) > 0) {
        //Alerts the user if the password they entered does not match
        //the meeting's password

        if($dataSubmitted && !$meetingExist) {
            $out .= "***".$meetingID." no longer exists.***";
        }
        else if($dataSubmitted) {
            $out .= "***Incorrect Password***";
        }

        if ( bigbluebutton_can_participate($role) ) {
            $out .= '
            <form id="bbb-join-form'.($bigbluebutton_form_in_widget?'-widget': '').'" class="bbb-join" name="form1" method="post" action="">';

            if(sizeof($listOfMeetings) > 1 && !$token ) {
                if( isset($tokens) && trim($tokens) != '' ) {
                    $tokens_array = explode(',', $tokens);
                    $where = "";
                    foreach ($tokens_array as $tokens_element) {
                        if( $where == "" )
                            $where .= " WHERE meetingID='".$tokens_element."'";
                        else
                            $where .= " OR meetingID='".$tokens_element."'";
                    }
                    $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW FROM ".$table_name.$where." ORDER BY meetingName");
                }
                $out .= '
                <label>Meeting:</label>
                <select name="meetingID">';

                foreach ($listOfMeetings as $meeting) {
                    $out .= '
                    <option value="'.$meeting->meetingID.'">'.$meeting->meetingName.'</option>';
                }

                $out .= '
                </select>';
            } else if ($token) {
                $out .= '
                <input type="hidden" name="meetingID" id="meetingID" value="'.$token.'" />';

            } else {
                $meeting = reset($listOfMeetings);
                $out .= '
                <input type="hidden" name="meetingID" id="meetingID" value="'.$meeting->meetingID.'" />';

            }

            if( !$current_user->ID ) {
                $out .= '
                <label>Name:</label>
                <input type="text" id="name" name="display_name" size="10">';
            }
            if( bigbluebutton_validate_defaultRole($role, 'none') ) {
                $out .= '
                <label>Password:</label>
                <input type="password" name="pwd" size="10">';
            }
            $out .= '
            </table>';
            if(sizeof($listOfMeetings) > 1 && !$token ) {
                $out .= '

                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join').'">';
            } else if ($token) {
                foreach ($listOfMeetings as $meeting) {
                    if($meeting->meetingID == $token ) {
                        $out .= '
                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join '.$meeting->meetingName).'">';
                        break;
                    }
                }

                if($meeting->meetingID != $token ) {
                    $out .= '
                <div>Invalid meeting token</div>';
                }

            } else {
                $out .= '
                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join '.$meeting->meetingName).'">';

            }
            $out .= '
            </form>';

        } else {
            $out .= $role." users are not allowed to participate in meetings";

        }

    } else if($dataSubmitted) {
        //Alerts the user if the password they entered does not match
        //the meeting's password
        $out .= "***".$meetingID." no longer exists.***<br />";
        $out .= "No meeting rooms are currently available to join.";

    } else{
        $out .= "No meeting rooms are currently available to join.";

    }

    return $out;
}
