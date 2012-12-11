<?php
/*
Plugin Name: BigBlueButton
Plugin URI: http://blindsidenetworks.com/integration
Description: BigBlueButton is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers. For more information on setting up your own BigBlueButton server or for using an external hosting provider visit http://bigbluebutton.org/support
Version: 1.3.1
Author: Blindside Networks
Author URI: http://blindsidenetworks.com/
License: GPLv2
*/

//================================================================================
//---------------------------Standard Plugin definition---------------------------
//================================================================================

//validate
global $wp_version;
$exit_msg = "This plugin has been designed for Wordpress 2.5 and later, please upgrade your current one.";
if(version_compare($wp_version, "2.5", "<")) {
    exit($exit_msg);
}

//constant definitions
define("BIGBLUEBUTTON_DIR", WP_PLUGIN_URL . '/bigbluebutton/' );

//shortcode definitions
add_shortcode('bigbluebutton', 'bigbluebutton_shortcode');
add_shortcode('bigbluebutton_test', 'bigbluebutton_test_shortcode');
add_shortcode('bigbluebutton_recordings', 'bigbluebutton_recordings_shortcode');

//action definitions
add_action('init', 'init_sessions');
add_action('init', 'init_scripts');

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('php/bbb_api.php');

global $bigbluebutton_plugin_version;


//================================================================================
//------------------Code for development------------------------------------------
//================================================================================
if(!function_exists('_log')){
    function _log( $message ) {
        if( WP_DEBUG === true ){
            if( is_array( $message ) || is_object( $message ) ){
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }
}
_log('Loading the plugin');


//================================================================================
//-------------------------BigBlueButtonPlugin Class------------------------------
//================================================================================
if (!class_exists("bigbluebuttonPlugin")) {
    class bigbluebuttonPlugin {
        function bigbluebuttonPlugin() { //constructor
            global $bigbluebutton_plugin_version;
            $bigbluebutton_plugin_version = bigbluebuttonPlugin::plugin_get_version();
        }

        //Inserts the plugin pages in the admin panel
        function plugin_add_pages() {

            //Add a new submenu under Settings
            $page = add_options_page(__('BigBlueButton','menu-test'), __('BigBlueButton','menu-test'), 'manage_options', 'bigbluebutton_general', 'bigbluebutton_general_options');

            //Attaches the plugin's stylesheet to the plugin page just created
            add_action('admin_print_styles-' . $page, 'bigbluebutton_admin_styles');

        }

        //Registers the plugin's stylesheet
        function plugin_admin_init() {
            wp_register_style('bigbluebuttonStylesheet', WP_PLUGIN_URL.'/bigbluebutton/css/bigbluebutton_stylesheet.css');
        }

        //Registers the bigbluebutton widget
        function plugin_widget_init(){
            wp_register_sidebar_widget('bigbluebuttonsidebarwidget', __('BigBlueButton'), 'bigbluebutton_sidebar', array( 'description' => 'Displays a BigBlueButton login form in a sidebar.'));
        }

        //Sets up the bigbluebutton table to store meetings in the wordpress database
        function plugin_install () {
            	
            global $wpdb, $bigbluebutton_plugin_version;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            //Sets the name of the table
            $table_name = $wpdb->prefix . "bigbluebutton";
            $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
            
            //Installation code
            if( !get_option('bbb_db_version') && !get_option('bigbluebutton_plugin_version') ){
                ////////////////// Create Database //////////////////
                $sql = "CREATE TABLE " . $table_name . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    meetingID text NOT NULL,
                    meetingName text NOT NULL,
                    meetingVersion int NOT NULL,
                    attendeePW text NOT NULL,
                    moderatorPW text NOT NULL,
                    waitForModerator BOOLEAN NOT NULL DEFAULT FALSE,
                    recorded BOOLEAN NOT NULL DEFAULT FALSE,
                    UNIQUE KEY id (id)
                    );";

                dbDelta($sql);
                
                $sql = "CREATE TABLE " . $table_logs_name . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    meetingID text NOT NULL,
                    recorded BOOLEAN NOT NULL DEFAULT FALSE,
                    timestamp int NOT NULL,
                    event text NOT NULL,
                    UNIQUE KEY id (id)
                    );";
                
                dbDelta($sql);
                
            }
            	
            ////////////////// Initialize Settings //////////////////
            if( !get_option('bigbluebutton_url')){
                echo 'No URL<br>';
            } else {
                echo 'BigBlueButton URL: '.get_option('bigbluebutton_url').'<br>';
            }
            
            if( !get_option('bigbluebutton_url') ) update_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' );
            if( !get_option('bigbluebutton_salt') ) update_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' );
             
            ////////////////// Set new bigbluebutton_plugin_version value //////////////////
            update_option( "bigbluebutton_plugin_version", $bigbluebutton_plugin_version );
            	
        }

        function plugin_update_check() {
            global $wpdb, $bigbluebutton_plugin_version;
            	
            //Sets the name of the table
            $table_name = $wpdb->prefix . "bigbluebutton";
            $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
            $table_name_old = $wpdb->prefix . "bbb_meetingRooms";

            ////////////////// Updates for version 1.0.2 and earlier //////////////////
            $bbb_db_version_installed = get_option("bbb_db_version");
            if( $bbb_db_version_installed ){
                ////////////////// Update Settings //////////////////
                if( !get_option('mt_bbb_url') ) {
                    update_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' );
                } else {
                    update_option( 'bigbluebutton_url', get_option('mt_bbb_url') );
                    delete_option('mt_bbb_url');
                }

                if( !get_option('mt_salt') ) {
                    update_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' );
                } else {
                    update_option( 'bigbluebutton_salt', get_option('mt_salt') );
                    delete_option('mt_salt');
                }

                delete_option('mt_waitForModerator'); //deletes this option because it is no longer needed, it has been incorportated into the table.
                delete_option('bbb_db_version'); //deletes this option because it is no longer needed, the versioning pattern has changed.

                ////////////////// Update Database //////////////////
                //Rename database
                $sql = "ALTER TABLE " . $table_name_old . " RENAME TO " . $table_name . ";";
                $wpdb->query($sql);

                //Only for versions 1.0 and earlier
                if( $bbb_db_version_installed && strcmp($bbb_db_version_installed, "1.0") <= 0 ){
                    $sql = "ALTER TABLE " . $table_name . " ADD waitForModerator BOOLEAN NOT NULL DEFAULT FALSE;";
                    $wpdb->query($sql);
                }
                //Common update
                $sql = "ALTER TABLE " . $table_name . " ADD meetingName TEXT NOT NULL AFTER meetingID;";
                $wpdb->query($sql);

                $sql = "ALTER TABLE " . $table_name . " ADD recorded BOOLEAN NOT NULL DEFAULT FALSE;";
                $wpdb->query($sql);


            }

            ////////////////// Updates for version 1.0.3 and larter //////////////////
            $bigbluebutton_plugin_version_installed = get_option('bigbluebutton_plugin_version');
            if( $bigbluebutton_plugin_version_installed && strcmp($bigbluebutton_plugin_version_installed, "1.0.3") == 0 ){
                	
            }
            	
            if( $bigbluebutton_plugin_version_installed && strcmp($bigbluebutton_plugin_version_installed, "1.0.4") == 0 ){

            }

            ////////////////// Set new bigbluebutton_plugin_version value //////////////////
            update_option( "bigbluebutton_plugin_version", $bigbluebutton_plugin_version );

        }

        //Sets up the bigbluebutton table to store meetings in the wordpress database
        function plugin_uninstall () {
            global $wpdb;
            
            //In case is deactivateing an overwritten version
            if( get_option('bbb_db_version') ){
                $table_name_old = $wpdb->prefix . "bbb_meetingRooms";
                $wpdb->query("DROP TABLE IF EXISTS $table_name_old");
                delete_option('bbb_db_version');
                delete_option('mt_bbb_url');
                delete_option('mt_salt');
            }
            	
            //Delete the options stored in the wordpress db
            delete_option('bigbluebutton_plugin_version');
            delete_option('bigbluebutton_url');
            delete_option('bigbluebutton_salt');
            	
            //Sets the name of the table
            $table_name = $wpdb->prefix . "bigbluebutton";
            $wpdb->query("DROP TABLE IF EXISTS $table_name");

            $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
            $wpdb->query("DROP TABLE IF EXISTS $table_logs_name");
            
        }

        /**
         * Returns current plugin version.
         *
         * @return string Plugin version
         */
        function plugin_get_version() {
            if ( !function_exists( 'get_plugins' ) )
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
            $plugin_file = basename( ( __FILE__ ) );
            
            return $plugin_folder[$plugin_file]['Version'];
        }

    }//End Class bigbluebuttonPlugin
}


//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
if (class_exists("bigbluebuttonPlugin")) {
    $bigbluebutton_plugin = new bigbluebuttonPlugin();
}

if (isset($bigbluebutton_plugin)) {

    add_action('admin_menu', array(&$bigbluebutton_plugin, 'plugin_add_pages'), 1);

    add_action('admin_init', array(&$bigbluebutton_plugin, 'plugin_admin_init'), 1);

    add_action('plugins_loaded', array(&$bigbluebutton_plugin, 'plugin_update_check') );

    add_action('plugins_loaded', array(&$bigbluebutton_plugin, 'plugin_widget_init') );

    register_activation_hook(__FILE__, array(&$bigbluebutton_plugin, 'plugin_install') ); //Runs the install script (including the databse and options set up)

    register_deactivation_hook(__FILE__, array(&$bigbluebutton_plugin, 'plugin_uninstall') ); //Runs the uninstall function (it includes the database and options delete)

    //register_uninstall_hook(__FILE__, array(&$bigbluebutton_plugin, 'plugin_uninstall') ); //Runs the uninstall function (it includes the database and options delete)
    
    set_error_handler("bigbluebutton_warning_handler", E_WARNING);

}


//Adds the plugin stylesheet to wordpress
function bigbluebutton_admin_styles(){
    wp_enqueue_style('bigbluebuttonStylesheet');
}


// Sessions are required by the plugin to work.
function init_sessions() {
    if (!session_id()) {
        session_start();
    }
}

function init_scripts() {
    if (!is_admin()) {
	    //wp_deregister_script('jquery');
        //wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js', false);
        wp_enqueue_script('jquery');
	}    
}


//================================================================================
//------------------------------Error Handler-------------------------------------
//================================================================================
function bigbluebutton_warning_handler($errno, $errstr) {
    //Do Nothing
}


//================================================================================
//---------------------------------ShortCode functions----------------------------
//================================================================================
//Inserts a bigbluebutton form on a post or page of the blog
function bigbluebutton_shortcode($args) {
    extract($args);

    bigbluebutton_form($args);

}

function bigbluebutton_recordings_shortcode($args) {
    extract($args);
    
    bigbluebutton_list_recordings((isset($args['title']))? $args['title']: null);

}

//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================
//Inserts a bigbluebutton widget on the siderbar of the blog
function bigbluebutton_sidebar($args) {
    extract($args);

    echo $before_widget;
    echo $before_title.'BigBlueButton'.$after_title;
    echo $after_widget;
    
    bigbluebutton_form($args);

}

//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================
//Create the form called by the Shortcode and Widget functions

function bigbluebutton_form($args) {

    global $wpdb, $wp_version, $current_site;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
    
    //Read in existing option value from database
    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    //Gets all the meetings from wordpress database
    $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW FROM ".$table_name." ORDER BY meetingName");
    	
    $dataSubmitted = false;
    $validMeeting = false;
    $meetingExist = false;
    if( isset($_POST['SubmitForm']) && $_POST['SubmitForm'] == 'Join' ) { //The user has submitted his login information
        $dataSubmitted = true;
        $meetingExist = true;

        //Read posted values
        $name = $_POST['display_name'];
        $password = $_POST['pwd'];
        $meetingID = $_POST['meetingID'];

        $found = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
        if($found->meetingID == $meetingID && ($found->moderatorPW == $password || $found->attendeePW == $password) ){
            
            //Extra parameters
            $duration = 0;
            $voicebridge = 0;
            //Metadata for tagging recordings
            $metadata = Array(
                'origin' => 'Wordpress',
                'originversion' => $wp_version,
                'origintag' => 'wp_plugin-bigbluebutton '.$bigbluebutton_plugin_version,
                //'originnameserver' => get_current_site_name( $current_site )
            );
            //Call for creating meeting on the bigbluebutton server
            $response = BigBlueButton::createMeetingArray($name, $found->meetingID, $found->meetingName, "", $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, get_option('siteurl'), $recorded? 'true':'false', $duration, $voicebridge, $metadata );

            //Analyzes the bigbluebutton server's response
            if(!$response || $response['returncode'] == 'FAILED' ){//If the server is unreachable, or an error occured
                echo "Sorry an error occured while joining the meeting.";
                return;
                	
            } else{ //The user can join the meeting, as it is valid
                if( !isset($response['messageKey']) || $response['messageKey'] == '' ){
                    // The meeting was just created, insert the create event to the log
                    $rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
                }
                    
                $bigbluebutton_joinURL = BigBlueButton::getJoinURL($found->meetingID, $name, $password, $salt_val, $url_val );
                //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
                //do not wait for moderator option is set to false then the user is immediately redirected to the meeting
                if ( (BigBlueButton::isMeetingRunning( $found->meetingID, $url_val, $salt_val ) && ($found->moderatorPW == $password || $found->attendeePW == $password ) )
                        || $response['moderatorPW'] == $password
                        || ($response['attendeePW'] == $password && !$found->waitForModerator)  ){
                    //If the password submitted is correct then the user gets redirected
                    echo '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'";</script>'."\n";
                    return;
                }
                //If the viewer has the correct password, but the meeting has not yet started they have to wait
                //for the moderator to start the meeting
                else if ($found->attendeePW == $password){
                    //Stores the url and salt of the bigblubutton server in the session
                    $_SESSION['mt_bbb_url'] = $url_val;
                    $_SESSION['mt_salt'] = $salt_val;
                    //Displays the javascript to automatically redirect the user when the meeting begins
                    bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $found->meetingID, $found->meetingName, $name);
                    return;
                }
            }
        }
    }

    //Displays the meetings in the wordpress database.
    foreach ($listOfMeetings as $meeting) {
        $validMeeting = true;
        break;
    }

    //If a valid meeting was found the login form is displayed
    if($validMeeting){
        //Alerts the user if the password they entered does not match
        //the meeting's password
        if($dataSubmitted && !$meetingExist){
            echo "***".$meetingID." no longer exists.***";
        }
        else if($dataSubmitted){
            echo "***Incorrect Password***";
        }
        echo '
            <form name="form1" method="post" action="">
              <table>
                <tr>
                  <td>Meeting</td>
                  <td><select name="meetingID">';

        foreach ($listOfMeetings as $meeting) {
            echo '                    <option value="'.$meeting->meetingID.'">'.$meeting->meetingName.'</option>';
		}
		
		echo '
                  </select>
                </tr>
                <tr>
                  <td>Name</td>
                  <td><INPUT type="text" id="name" name="display_name" size="10"></td>
                </tr>
                <tr>
                  <td>Password</td>
                  <td><INPUT type="password" name="pwd" size="10"></td>
                </tr>
              </table>
              <INPUT type="submit" name="SubmitForm" value="Join">
            </form>';
    
    } else if($dataSubmitted){
        //Alerts the user if the password they entered does not match
        //the meeting's password
        echo "***".$meetingID." no longer exists.***<br />";
        echo "No meeting rooms are currently available to join.";
    
    } else{
        echo "No meeting rooms are currently available to join.";
    
    }

}


//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID
function bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $meetingID, $meetingName, $name){

    echo '<script type="text/javascript">
            function bigbluebutton_ping() {
                jQuery.ajax({
                    url : "./wp-content/plugins/bigbluebutton/php/broker.php?action=ping&meetingID='.urlencode($meetingID).'",
	   		        async : true,
	   		        dataType : "xml",
                    success : function(xmlDoc){
                        $xml = jQuery( xmlDoc ), $running = $xml.find( "running" );
                        if($running.text() == "true"){
                            window.location = "'.$bigbluebutton_joinURL.'";
                        }
                    },
	   		        error : function(xmlHttpRequest, status, error) {
	   		            console.debug(xmlHttpRequest);
			        }
                });
            
            }
            
            setInterval("bigbluebutton_ping()", 5000);
          </script>';

    echo '<table>
            <tbody>
              <tr>
                <td>
                  Hi '.$name.',<br /><br />
                  Now waiting for the moderator to start '.$meetingName.'.<br />
                  <div align="center"><img src="./wp-content/plugins/bigbluebutton/images/polling.gif" /></div><br />
                  (Your browser will automatically refresh and join the meeting when it starts.)
                </td>
              </tr>
            </tbody>
          </table>';
    return;
}


//================================================================================
//---------------------------------bigbluebutton Page--------------------------------------
//================================================================================
//The main page where the user specifies the url of the bigbluebutton server and its salt
function bigbluebutton_general_options() {

    //Checks to see if the user has the sufficient persmissions and capabilities
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    /* If the bigbluebutton server url and salt are empty then it does not
     display the create meetings, and list meetings sections.*/
    if (bigbluebutton_general_settings()){

        bigbluebutton_create_meetings();

        bigbluebutton_list_meetings();

        bigbluebutton_list_recordings();

    }

}

//================================================================================
//------------------------------General Settings----------------------------------
//================================================================================
// The page allows the user specifies the url of the bigbluebutton server and its salt
function bigbluebutton_general_settings() {

    //Displays the title of the page
    echo '<div class="wrap">';
    echo "<h2>BigBlueButton Settings</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');
    
    //Obtains the meeting information of the meeting that is going to be terminated
    if( isset($_POST['SubmitSettings']) && $_POST['SubmitSettings'] == 'Save Changes') {
         
        //Reads their posted value
        $url_val = $_POST[ 'bigbluebutton_url' ];
        $salt_val = $_POST[ 'bigbluebutton_salt' ];

        //
        if(strripos($url_val, "/bigbluebutton/") == false){
            if(substr($url_val, -1) == "/"){
                $url_val .= "bigbluebutton/";
            }
            else{
                $url_val .= "/bigbluebutton/";
            }
        }
        	
        // Save the posted value in the database
        update_option('bigbluebutton_url', $url_val );
        update_option('bigbluebutton_salt', $salt_val );

        // Put an settings updated message on the screen
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }

    if($url_val == "http://test-install.blindsidenetworks.com/bigbluebutton/" ){
        echo '<div class="updated"><p><strong>You are using a test BigBlueButton server provided by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>. For more information on setting up your own BigBlueButton server see <i><a href="http://bigbluebutton.org/support" target="_blank">http://bigbluebutton.org/support.</a></i></strong></div>';
    }
    //Form to update the url of the bigbluebutton server, and it`s salt

    echo '
        <form name="form1" method="post" action="">
          <p>URL of BigBlueButton server:<input type="text" name="bigbluebutton_url" value="'.$url_val.'" size="60"> eg. \'http://example.com/bigbluebutton/\'
          </p>
          <p>Salt of BigBlueButton server:<input type="text" name="bigbluebutton_salt" value="'.$salt_val.'" size="40"> Can be found in /var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties
          </p>

          <p class="submit">
            <input type="submit" name="SubmitSettings" class="button-primary" value="Save Changes" />
          </p>

        </form>
        <hr />';

    //Checks to see if the url and salt are empty. If they are then
    //the create meetings, and list meetings sections are not displayed
    if($url_val == '' || $salt_val == ''){
        echo '</div>';
        return false;
    }

    return true;

}

//================================================================================
//---------------------------------List Meetings----------------------------------
//================================================================================
// Displays all the meetings available in the bigbluebutton server
function bigbluebutton_list_meetings() {
    global $wpdb, $wp_version, $current_site, $current_user, $bigbluebutton_plugin_version;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
    
    //Displays the title of the page
    echo "<h2>List of Meeting Rooms</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    //---------------------------------------------------JOIN-----------------------------------------------
    if( isset($_POST['SubmitList']) ) { //Creates then joins the meeting. If any problems occur the error is displayed
        // Read the posted value and delete
        $meetingID = $_POST['meetingID'];
        $found = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
        $meetingName = $found->meetingName;
        $moderatorPW = $found->moderatorPW;
        $attendeePW = $found->attendeePW;
        $meetingVersion = $found->meetingVersion;
        $recorded = $found->recorded;
        
        if($_POST['SubmitList'] == 'Join'){
            //Extra parameters
            $duration = 0;
            $voicebridge = 0;
            //Metadata for tagging recordings
            $metadata = array(
                'meta_origin' => 'WordPress',
                'meta_originversion' => $wp_version,
                'meta_origintag' => 'wp_plugin-bigbluebutton '.$bigbluebutton_plugin_version,
                'meta_originservername' => get_option('siteurl'),
                'meta_originservercommonname' => get_bloginfo('name'),
            );
            
            //Calls create meeting on the bigbluebutton server
            $response = BigBlueButton::createMeetingArray($current_user->display_name, $meetingID, $meetingName, "", $moderatorPW, $attendeePW, $salt_val, $url_val, get_option('siteurl'), ($recorded? 'true':'false'), $duration, $voicebridge, $metadata );
            
            $createNew = false;
            //Analyzes the bigbluebutton server's response
            if(!$response){//If the server is unreachable, then prompts the user of the necessary action
                echo '<div class="updated"><p><strong>Unable to join the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            }
            else if( $response['returncode'] == 'FAILED' ) { //The meeting was not created
                if($response['messageKey'] == 'idNotUnique'){
                    $createNew = true;
                }
                else if($response['messageKey'] == 'checksumError'){
                    echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
                }
                else{
                    echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
                }
            }
            else{
                if( !isset($response['messageKey']) || $response['messageKey'] == '' ){
                    // The meeting was just created, insert the create event to the log
                    $rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
                }
                
                $bigbluebutton_joinURL = BigBlueButton::getJoinURL($meetingID, $current_user->display_name,$moderatorPW, $salt_val, $url_val );
                echo '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'"; return false;</script>'."\n";
                return;
            }
            	
        }
        //---------------------------------------------------END-------------------------------------------------
        else if($_POST['SubmitList'] == 'End' ) { //Obtains the meeting information of the meeting that is going to be terminated
            	
            //Calls endMeeting on the bigbluebutton server
            $response = BigBlueButton::endMeeting($meetingID, $moderatorPW, $url_val, $salt_val );

            //Analyzes the bigbluebutton server's response
            if(!$response){//If the server is unreachable, then prompts the user of the necessary action
                echo '<div class="updated"><p><strong>Unable to terminate the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            }
            else if( $response['returncode'] == 'SUCCESS' ) { //The meeting was terminated
                echo '<div class="updated"><p><strong>'.$meetingName.' meeting has been terminated.</strong></p></div>';

                //In case the meeting is created again it sets the meeting version to the time stamp. Therefore the meeting can be recreated before the 1 hour rule without any problems.
                $meetingVersion = time();
                $wpdb->update( $table_name, array( 'meetingVersion' => $meetingVersion), array( 'meetingID' => $meetingID ));
                	
            }
            else{ //If the meeting was unable to be termindated
                if($response['messageKey'] == 'checksumError'){
                    echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
                }
                else{
                    echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
                }
            }
            	
            	
            	
        }
        //---------------------------------------------------DELETE-------------------------------------------------
        else if($_POST['SubmitList'] == 'Delete' ) { //Obtains the meeting information of the meeting that is going to be delete

            //Calls endMeeting on the bigbluebutton server
            $response = BigBlueButton::endMeeting($meetingID, $moderatorPW, $url_val, $salt_val );

            //Analyzes the bigbluebutton server's response
            if(!$response){//If the server is unreachable, then prompts the user of the necessary action
                echo '<div class="updated"><p><strong>Unable to delete the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            }
            else if( $response['returncode'] != 'SUCCESS' && $response['messageKey'] != 'notFound' ) { //If the meeting was unable to be deleted due to an error
                if($response['messageKey'] == 'checksumError'){
                    echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
                }
                else{
                    echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
                }
            }
            else { //The meeting was terminated
                $wpdb->query("DELETE FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
                echo '<div class="updated"><p><strong>'.$meetingID.' meeting has been deleted.</strong></p></div>';
            }
            	
        }
    }


    //Gets all the meetings from the wordpress db
    $listOfMeetings = $wpdb->get_results("SELECT * FROM ".$table_name." ORDER BY id");

    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfMeetings) == 0){
        echo '<div class="updated"><p><strong>There are no meeting rooms.</strong></p></div>';
        return;
    }

    //Iinitiallizes the table
    $printed = false;
    //Displays the meetings in the wordpress database that have not been created yet. Avoids displaying
    //duplicate meetings, meaning if the same meeting already exists in the bigbluebutton server then it is
    //not displayed again in this for loop
    foreach ($listOfMeetings as $meeting) {

        $info = BigBlueButton::getMeetingInfoArray( $meeting->meetingID, $meeting->moderatorPW, $url_val, $salt_val);
        //Analyzes the bigbluebutton server's response
        if(!$info){//If the server is unreachable, then prompts the user of the necessary action
            echo '<div class="updated"><p><strong>Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            return;
        } else if( $info['returncode'] == 'FAILED' && $info['messageKey'] != 'notFound' && $info['messageKey'] != 'invalidPassword') { /// If the meeting was unable to be deleted due to an error
            if($info['messageKey'] == 'checksumError'){
                echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            }
            else{
                echo '<div class="updated"><p><strong>'.$info['message'].'</strong></p></div>';
            }
            return;
        } else if( $info['returncode'] == 'FAILED' && ($info['messageKey'] == 'notFound' || $info['messageKey'] != 'invalidPassword') ){ /// The meeting exists only in the wordpress db
            if(!$printed){
                bigbluebutton_print_table_header();
                $printed = true;
            }
            echo '<form name="form1" method="post" action="">
                    <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
                    <tr>
                      <td>'.$meeting->meetingName.'</td>
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

            if(!$printed){
                bigbluebutton_print_table_header();
                $printed = true;
            }

            echo '<form name="form1" method="post" action="">
                    <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
                    <tr>
                      <td>'.$meeting->meetingName.'</td>
                      <td>'.$meeting->attendeePW.'</td>
                      <td>'.$meeting->moderatorPW.'</td>
                      <td>'.($meeting->waitForModerator? 'Yes': 'No').'</td>
                      <td>'.($meeting->recorded? 'Yes': 'No').'</td>
                      </td>';
		    if( isset($info['hasBeenForciblyEnded']) && $info['hasBeenForciblyEnded']=='false'){
		        echo '    <td><input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
		                      <input type="submit" name="SubmitList" class="button-primary" value="End" onClick="return confirm(\'Are you sure you want to end the meeting?\')" />&nbsp;
		                      <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />
		                  </td>';
		    } else {
		        echo '    <td>
			                <!-- Meeting has ended and is temporarily unavailable. -->
			                <input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
			                <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />&nbsp;
                          </td>';
		    }
		    echo '	</tr>
                  </form>';
        }
    }

    echo '  </table>
          </div><hr />';

}

//================================================================================
//---------------------------------List Recordings----------------------------------
//================================================================================
// Displays all the recordings available in the bigbluebutton server
function bigbluebutton_list_recordings($title) {
    global $wpdb;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
    
    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');
    
    $_SESSION['mt_bbb_url'] = $url_val;
    $_SESSION['mt_salt'] = $salt_val;
    
    $title = ($title != null)? $title: 'List of Recordings'; 
    
    //Gets all the meetings from wordpress database
    $listOfMeetings = $wpdb->get_results("SELECT DISTINCT meetingID FROM ".$table_logs_name." WHERE recorded = 1 ORDER BY timestamp;");
    
    $meetingIDs = '';
    $listOfRecordings = Array();
    if($listOfMeetings){
        foreach ($listOfMeetings as $meeting) {
            if( $meetingIDs != '' ) $meetingIDs .= ', ';
            $meetingIDs .= $meeting->meetingID;
        }
    }
    
    $listOfRecordings = Array();
    if( $meetingIDs != '' ){
        $recordingsArray = BigBlueButton::getRecordingsArray($meetingIDs, $url_val, $salt_val);
        if( $recordingsArray['returncode'] == 'SUCCESS' && !$recordingsArray['messageKey'] ){
            $listOfRecordings = $recordingsArray['recordings'];
        }
    }
    
    
    //Displays the title of the page
    echo "<h2>".$title."</h2>";

    if ( current_user_can('activate_plugins') ) {
        echo '
          <script type="text/javascript">
              wwwroot = \''.get_bloginfo('url').'\'
              function actionCall(action, recordingid) {
                  
                  actionurl = wwwroot + "/wp-content/plugins/bigbluebutton/php/broker.php?action=" + action + "&recordingID=" + recordingid;
	              action = (typeof action == \'undefined\') ? \'publish\' : action;
	              
	              if (action == \'publish\' || action == \'unpublish\' || (action == \'delete\' && confirm("Are you sure to delete this recording?"))) {
		              if (action == \'publish\' || action == \'unpublish\') {
			              var el_a = document.getElementById(\'actionbar-publish-a-\'+ recordingid);
			              if (el_a) {
				              var el_img = document.getElementById(\'actionbar-publish-img-\'+ recordingid);
				              if (el_a.title == \'Hide\' ) {
					              el_a.title = \'Show\';
					              el_img.src = wwwroot + \'/wp-content/plugins/bigbluebutton/images/show.gif\';
                              } else {
                                  el_a.title = \'Hide\';
                                  el_img.src = wwwroot + \'/wp-content/plugins/bigbluebutton/images/hide.gif\';
                              }
                          }
                      } else {
                          // Removes the line from the table
                          $(document.getElementById(\'actionbar-tr-\'+ recordingid)).remove();
                      }
                      jQuery.ajax({
                          url : actionurl,
	   		              async : false,
                          success : function(response){
                          },
	   		              error : function(xmlHttpRequest, status, error) {
	   		                  console.debug(xmlHttpRequest);
			              }
                      });
                  }    
              }   
          </script>';
    }
    
    
    //Print begining of the table
    echo '
          <div>
            <table class="stats" cellspacing="5">
              <tr>
                <th class="hed" colspan="1">Recording</td>
                <th class="hed" colspan="1">Meeting Room Name</td>
                <th class="hed" colspan="1">Date</td>
                <th class="hed" colspan="1">Duration</td>';
    if ( current_user_can('activate_plugins') ) {
        echo '
                <th class="hedextra" colspan="1">Toolbar</td>';
    }
    echo '
              </tr>';
    foreach( $listOfRecordings as $recording){
      if ( current_user_can('activate_plugins') || $recording['published'] == 'true') {
        /// Prepare playback recording links
        $type = '';
        foreach ( $recording['playbacks'] as $playback ){
            $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
        }
        
        /// Prepare actionbar
        $actionbar = '';
        if ( current_user_can('activate_plugins') ) {
            
            $deleteURL = BigBluebutton::getDeleteRecordingsURL($recording['recordID'], $url_val, $salt_val);
            if ( $recording['published'] == 'true' ){
                $publishURL = BigBluebutton::getPublishRecordingsURL($recording['recordID'], 'false', $url_val, $salt_val);
                $actionbar = "<a id=\"actionbar-publish-a-".$recording['recordID']."\" title=\"Hide\" href=\"#\"><img id=\"actionbar-publish-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bigbluebutton/images/hide.gif\" class=\"iconsmall\" onClick=\"actionCall('unpublish', '".$recording['recordID']."'); return false;\" /></a>";
            } else {
                $publishURL = BigBluebutton::getPublishRecordingsURL($recording['recordID'], 'true', $url_val, $salt_val);
                $actionbar = "<a id=\"actionbar-publish-a-".$recording['recordID']."\" title=\"Show\" href=\"#\"><img id=\"actionbar-publish-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bigbluebutton/images/show.gif\" class=\"iconsmall\" onClick=\"actionCall('publish', '".$recording['recordID']."'); return false;\" /></a>";
            }
            $actionbar .= "<a id=\"actionbar-delete-a-".$recording['recordID']."\" title=\"Delete\" href=\"#\"><img id=\"actionbar-delete-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bigbluebutton/images/delete.gif\" class=\"iconsmall\" onClick=\"actionCall('delete', '".$recording['recordID']."'); return false;\" /></a>";
        }
        
        
        /// Prepare duration
        $endTime = isset($recording['endTime'])? intval(str_replace('"', '\"', $recording['endTime'])):0;
        $endTime = $endTime - ($endTime % 1000);
        $startTime = isset($recording['startTime'])? intval(str_replace('"', '\"', $recording['startTime'])):0;
        $startTime = $startTime - ($startTime % 1000);
        $duration = intval(($endTime - $startTime) / 60000);
        
        /// Prepare date
        //Make sure the startTime is timestamp
        if( !is_numeric($recording['startTime']) ){
            $date = new DateTime($recording['startTime']);
            $recording['startTime'] = date_timestamp_get($date);
        } else {
            $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
        }
        
        //Format the date
        //$formatedStartDate = gmdate("M d Y H:i:s", $recording['startTime']);
        $formatedStartDate = date_i18n( "M d Y H:i:s", $recording['startTime'], false );
        
        //Print detail
        echo '
              <tr id="actionbar-tr-'.$recording['recordID'].'">
                <td class="hed" colspan="1">'.$type.'</td>
                <td class="hed" colspan="1">'.$recording['meetingName'].'</td>
                <td class="hed" colspan="1">'.$formatedStartDate.'</td>
                <td class="hed" colspan="1">'.$duration.' min</td>';
        
        if ( current_user_can('activate_plugins') ) {
            echo '
                <td class="hedextra" colspan="1">'.$actionbar.'</td>';
        }
        
        echo '    
              </tr>';
      }
    }

    //Print end of the table
    echo '  </table>
          </div>';
    
    
    
}


//Begins the table of list meetings with the number of columns specified
function bigbluebutton_print_table_header(){
    echo '<div>
            <table class="stats" cellspacing="5">
              <tr>
                <th class="hed" colspan="1">Meeting Room Name</td>
                <th class="hed" colspan="1">Attendee Password</td>
                <th class="hed" colspan="1">Moderator Password</td>
                <th class="hed" colspan="1">Wait for Moderator</td>
                <th class="hed" colspan="1">Recorded</td>
                <th class="hedextra" colspan="1">Actions</td>
              </tr>';
}

//================================================================================
//-----------------------------Create a Meeting-----------------------------------
//================================================================================
//This page allows the user to create a meeting
function bigbluebutton_create_meetings() {

    //Displays the title of the page
    echo "<h2>Create a Meeting Room</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    //Obtains the meeting information of the meeting that is going to be created
    if( isset($_POST['SubmitCreate']) && $_POST['SubmitCreate'] == 'Create' ) {
         
        /// Reads the posted values
        $meetingName = $_POST[ 'meetingName' ];
        $attendeePW = $_POST[ 'attendeePW' ];
        $moderatorPW = $_POST[ 'moderatorPW' ];
        $waitForModerator = (isset($_POST[ 'waitForModerator' ]) && $_POST[ 'waitForModerator' ] == 'True')? true: false;
        $recorded = (isset($_POST[ 'recorded' ]) && $_POST[ 'recorded' ] == 'True')? true: false;
        $meetingVersion = time();
        /// Assign a random unique ID based on the name and timestamp
        $meetingID = sha1($meetingName.strval($meetingVersion));


        //Checks to see if the meeting name, attendee password or moderator password was left blank
        if($meetingName == '' || $attendeePW == '' || $moderatorPW == ''){
			//If the meeting name was left blank, the user is prompted to fill it out
			echo '<div class="updated">
                    <p>
                      <strong>All fields must be filled.</strong>
                    </p>
                  </div>';
			
		} else {
			$alreadyExists = false;
			
			//Checks the meeting to be created to see if it already exists in wordpress database
			global $wpdb;
			$table_name = $wpdb->prefix . "bigbluebutton";
			$listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName FROM ".$table_name);
			
			foreach ($listOfMeetings as $meeting) {
				if($meeting->meetingName == $meetingName){
					$alreadyExists = true;
					//Alerts the user to choose a different name
			        echo '<div class="updated">
                            <p>
                              <strong>'.$meetingName.' meeting room already exists. Please select a different name.</strong>
                            </p>
                          </div>';
					break;
				}
			}
			
			//If the meeting doesn't exist in the wordpress database then create it
			if(!$alreadyExists){ 
				$rows_affected = $wpdb->insert( $table_name, array( 'meetingID' => $meetingID, 'meetingName' => $meetingName, 'meetingVersion' => $meetingVersion, 'attendeePW' => $attendeePW, 'moderatorPW' => $moderatorPW, 'waitForModerator' => $waitForModerator? 1: 0, 'recorded' => $recorded? 1: 0) );
				
			    echo '<div class="updated">
                        <p>
                          <strong>Meeting Room Created.</strong>
                        </p>
                      </div>';

			}
			
			$meetingID = '';
			$meetingName = '';
			$meetingVersion = NULL;
			$attendeePW = '';
			$moderatorPW = '';
			$waitForModerator = false;
			$recorded = false;
				
		}		
    }
	
    //Form to create a meeting, the fields are the meeting name, and the optional fields are the attendee password and moderator password
    echo '<form name="form1" method="post" action="">
            <p>Meeting Room Name: <input type="text" name="meetingName" value=""	size="20"></p>
            <p>Attendee Password: <input type="text" name="attendeePW" value="" size="20"></p>
            <p>Moderator Password: <input type="text" name="moderatorPW" value="" size="20"></p>
            <p>Wait for moderator to start meeting: <input type="checkbox" name="waitForModerator" value="True" /></p>
            <p>Recorded meeting: <input type="checkbox" name="recorded" value="True" /></p>
            <p class="submit"><input type="submit" name="SubmitCreate" class="button-primary" value="Create" /></p>
          </form>
          <hr />';

}
