<?php
/*
Plugin Name: BigBlueButton
Plugin URI: http://blindsidenetworks.com/integrations/wordpress
Description: BigBlueButton is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meeting rooms to interact with their readers. It was developed and is maintained by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>. For more information on setting up your own BigBlueButton server or for using an external hosting provider visit <a href= "http://bigbluebutton.org/support" target="_blank">BigBlueButton support</a>.

Version: 1.4.2
Author: Blindside Networks
Author URI: http://blindsidenetworks.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
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

//constant definition
define("BIGBLUEBUTTON_DIR", WP_PLUGIN_URL . '/bigbluebutton/' );
define('BIGBLUEBUTTON_PLUGIN_VERSION', bigbluebutton_get_version());
define('BIGBLUEBUTTON_PLUGIN_URL', plugin_dir_url( __FILE__ ));

//constant message definition
define('BIGBLUEBUTTON_STRING_WELCOME', '<br>Welcome to <b>%%CONFNAME%%</b>!<br><br>To understand how BigBlueButton works see our <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the headset icon (upper-left hand corner). <b>Please use a headset to avoid causing noise for others.</b>');
define('BIGBLUEBUTTON_STRING_MEETING_RECORDED', '<br><br>This session may be recorded.');

//constant internal definition
define("BIGBLUEBUTTON_FORM_IN_WIDGET", TRUE );

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('php/bbb_api.php');




//================================================================================
//------------------Code for development------------------------------------------
//================================================================================
if(!function_exists('_log')) {
    function _log( $message ) {
        if( WP_DEBUG === true ) {
            if( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }
}
_log('Loading the plugin');

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
//hook definitions
register_activation_hook(__FILE__, 'bigbluebutton_install' ); //Runs the install script (including the databse and options set up)
//register_deactivation_hook(__FILE__, 'bigbluebutton_uninstall') ); //Runs the uninstall function (it includes the database and options delete)
register_uninstall_hook(__FILE__, 'bigbluebutton_uninstall' ); //Runs the uninstall function (it includes the database and options delete)

//shortcode definitions
add_shortcode('bigbluebutton', 'bigbluebutton_shortcode');
add_shortcode('bigbluebutton_recordings', 'bigbluebutton_recordings_shortcode');

//action definitions
add_action('init', 'bigbluebutton_init');
add_action('admin_menu', 'bigbluebutton_add_pages', 1);
add_action('admin_init', 'bigbluebutton_admin_init', 1);
add_action('plugins_loaded', 'bigbluebutton_update' );
add_action('plugins_loaded', 'bigbluebutton_widget_init' );
set_error_handler("bigbluebutton_warning_handler", E_WARNING);

//test
//================================================================================
//------------------------------ Main Functions ----------------------------------
//================================================================================
// Sessions are required by the plugin to work.
function bigbluebutton_init() {
    bigbluebutton_init_sessions();
    bigbluebutton_init_scripts();
    bigbluebutton_init_styles();

    //Attaches the plugin's stylesheet to the plugin page just created
    add_action('wp_print_styles', 'bigbluebutton_admin_styles');
}

function bigbluebutton_init_sessions() {
    if (!session_id()) {
        session_start();
    }
}

function bigbluebutton_init_scripts() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
}

//Registers the plugin's stylesheet
function bigbluebutton_init_styles() {
    wp_register_style('bigbluebuttonStylesheet', WP_PLUGIN_URL.'/wordpress-plugin_bigbluebutton/css/bigbluebutton_stylesheet.css');
}

//Registers the plugin's stylesheet
function bigbluebutton_admin_init() {
    bigbluebutton_init_styles();
}

//Adds the plugin stylesheet to wordpress
function bigbluebutton_admin_styles() {
    wp_enqueue_style('bigbluebuttonStylesheet');
}

//Registers the bigbluebutton widget
function bigbluebutton_widget_init() {
    wp_register_sidebar_widget('bigbluebuttonsidebarwidget', __('BigBlueButton'), 'bigbluebutton_sidebar', array( 'description' => 'Displays a BigBlueButton login form in a sidebar.'));
}

//Inserts the plugin pages in the admin panel
function bigbluebutton_add_pages() {

    //Add a new submenu under Settings
    $page = add_options_page(__('BigBlueButton','menu-test'), __('BigBlueButton','menu-test'), 'manage_options', 'bigbluebutton_general', 'bigbluebutton_general_options');

    //Attaches the plugin's stylesheet to the plugin page just created
    add_action('admin_print_styles-' . $page, 'bigbluebutton_admin_styles');

}

//Sets up the bigbluebutton table to store meetings in the wordpress database
function bigbluebutton_install () {
    global $wp_roles;
    // Load roles if not set
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();

    //Installation code
    if( !get_option('bigbluebutton_plugin_version') ) {
        bigbluebutton_init_database();
    }

    ////////////////// Initialize Settings //////////////////
    if( !get_option('bigbluebutton_url') ) update_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' );
    if( !get_option('bigbluebutton_salt') ) update_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' );
    if( !get_option('bigbluebutton_permissions') ) {
        $roles = $wp_roles->role_names;
        $roles['anonymous'] = 'Anonymous';
        $permissions = array();
        bigbluebutton_permissionsAssignment($roles, $permissions);

        update_option( 'bigbluebutton_permissions', $permissions );

    }

    update_option( "bigbluebutton_plugin_version", BIGBLUEBUTTON_PLUGIN_VERSION );

}

function bigbluebutton_update() {
    global $wpdb, $wp_roles;
    // Load roles if not set
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();

    //Sets the name of the table
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";

    ////////////////// Updates for version 1.3.1 and earlier //////////////////
    $bigbluebutton_plugin_version_installed = get_option('bigbluebutton_plugin_version');
    if( !$bigbluebutton_plugin_version_installed                                                                 //It's 1.0.2 or earlier
            || (strcmp("1.3.1", $bigbluebutton_plugin_version_installed) <= 0 && get_option("bbb_db_version")) ) {  //It's 1.3.1 not updated
        ////////////////// Update Database //////////////////
        /// Initialize database will create the tables added for the new version
        bigbluebutton_init_database();
        /// Transfer the data from old table to the new one
        $table_name_old = $wpdb->prefix . "bbb_meetingRooms";
        $listOfMeetings = $wpdb->get_results("SELECT * FROM ".$table_name_old." ORDER BY id");
        foreach ($listOfMeetings as $meeting) {
            $sql = "INSERT INTO " . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW) VALUES ( %s, %s, %s, %s, %s);";
            $wpdb->query(
                    $wpdb->prepare($sql, bigbluebutton_generateToken(), $meeting->meetingID, $meeting->meetingVersion, $meeting->attendeePW, $meeting->moderatorPW)
            );
        }
        /// Remove the old table
        $wpdb->query("DROP TABLE IF EXISTS $table_name_old");

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
    }

    //Set the new permission schema
    if( $bigbluebutton_plugin_version_installed && strcmp($bigbluebutton_plugin_version_installed, "1.3.3") < 0 ) {
        $roles = $wp_roles->role_names;
        $roles['anonymous'] = 'Anonymous';

        if( !get_option('bigbluebutton_permissions') ) {
          $permissions = array();
          bigbluebutton_permissionsAssignment($roles, $permissions);

        } else {
            $old_permissions = get_option('bigbluebutton_permissions');
            foreach($roles as $key => $value) {
                if( !isset($old_permissions[$key]['participate']) ) {
                    $permissions = array();
                    $permissions[$key]['participate'] = true;
                    if($value == "Administrator") {
                        $permissions[$key]['manageRecordings'] = true;
                        $permissions[$key]['defaultRole'] = "moderator";
                    } else if($value == "Anonymous") {
                        $permissions[$key]['manageRecordings'] = false;
                        $permissions[$key]['defaultRole'] = "none";
                    } else {
                        $permissions[$key]['manageRecordings'] = false;
                        $permissions[$key]['defaultRole'] = "attendee";
                    }
                } else {
                    $permissions[$key] = $old_permissions[$key];
                }
            }

        }

        update_option( 'bigbluebutton_permissions', $permissions );

    }

    ////////////////// Set new bigbluebutton_plugin_version value //////////////////
    update_option( "bigbluebutton_plugin_version", BIGBLUEBUTTON_PLUGIN_VERSION );

}

function bigbluebutton_uninstall () {
    global $wpdb;

    //In case is deactivateing an overwritten version
    if( get_option('bbb_db_version') ) {
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
    delete_option('bigbluebutton_permissions');

    //Sets the name of the table
    $table_name = $wpdb->prefix . "bigbluebutton";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";
    $wpdb->query("DROP TABLE IF EXISTS $table_logs_name");

}

//Creates the bigbluebutton tables in the wordpress database
function bigbluebutton_init_database() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    //Sets the name of the table
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";

    //Execute sql
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

    $sql = "INSERT INTO " . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW)
    VALUES ('".bigbluebutton_generateToken()."','Demo meeting', '".time()."', 'ap', 'mp');";
    dbDelta($sql);

    $sql = "INSERT INTO " . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW, recorded)
    VALUES ('".bigbluebutton_generateToken()."','Demo meeting (recorded)', '".time()."', 'ap', 'mp', TRUE);";
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

//Returns current plugin version.
function bigbluebutton_get_version() {
    if ( !function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );

    return $plugin_folder[$plugin_file]['Version'];
}

//================================================================================
//---------------------------------ShortCode functions----------------------------
//================================================================================
//Inserts a bigbluebutton form on a post or page of the blog
function bigbluebutton_shortcode($args) {
    extract($args);

    return bigbluebutton_form($args);

}

function bigbluebutton_recordings_shortcode($args) {
    extract($args);

    return bigbluebutton_list_recordings((isset($args['title']))? $args['title']: null);

}


//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================
//Inserts a bigbluebutton widget on the siderbar of the blog
function bigbluebutton_sidebar($args) {
    extract($args);

    echo $before_widget;
    echo $before_title.'BigBlueButton'.$after_title;
    echo bigbluebutton_form($args, BIGBLUEBUTTON_FORM_IN_WIDGET);
    echo $after_widget;
}

require_once('bigbluebutton-form.php');


//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID
function bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $meetingID, $meetingName, $name) {
    $out = '
    <script type="text/javascript">
        function bigbluebutton_ping() {
            jQuery.ajax({
                url : "'.site_url('/wp-content/plugins/bigbluebutton/php/broker.php?action=ping&meetingID='.urlencode($meetingID)).'",
                async : true,
                dataType : "xml",
                success : function(xmlDoc) {
                    $xml = jQuery( xmlDoc ), $running = $xml.find( "running" );
                    if($running.text() == "true") {
                        window.location = "'.$bigbluebutton_joinURL.'";
                    }
                },
                error : function(xmlHttpRequest, status, error) {
                    console.debug(xmlHttpRequest);
                }
            });

        }

        setInterval("bigbluebutton_ping()", 60000);
    </script>';

    $out .= '
    <table>
      <tbody>
        <tr>
          <td>
            Welcome '.$name.'!<br /><br />
            '.$meetingName.' session has not been started yet.<br /><br />
            <div align="center"><img src="'.site_url('/wp-content/plugins/bigbluebutton/images/polling.gif').'" /></div><br />
            (Your browser will automatically refresh and join the meeting when it starts.)
          </td>
        </tr>
      </tbody>
    </table>';

    return $out;
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

    echo bigbluebutton_general_settings() ;


    /* If the bigbluebutton server url and salt are empty then it does not
     display the create meetings, and list meetings sections.*/
    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');
    if($url_val == '' || $salt_val == '') {
        $out .= '</div>';

    } else {
        echo bigbluebutton_permission_settings();

        echo bigbluebutton_create_meetings();

        echo bigbluebutton_list_meetings();

        echo bigbluebutton_list_recordings('List of Recordings');

    }

}

//================================================================================
//------------------------------General Settings----------------------------------
//================================================================================
// The page allows the user specifies the url of the bigbluebutton server and its salt
function bigbluebutton_general_settings() {

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= '<div class="wrap">';
    $out .= "<h2>BigBlueButton General Settings</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    //Obtains the meeting information of the meeting that is going to be terminated
    if( isset($_POST['SubmitSettings']) && $_POST['SubmitSettings'] == 'Save Settings') {

        //Reads their posted value
        $url_val = $_POST[ 'bigbluebutton_url' ];
        $salt_val = $_POST[ 'bigbluebutton_salt' ];

        //
        if(strripos($url_val, "/bigbluebutton/") == false) {
            if(substr($url_val, -1) == "/") {
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
        $out .= '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }

    if($url_val == "http://test-install.blindsidenetworks.com/bigbluebutton/" ) {
        $out .= '<div class="updated"><p><strong>You are using a test BigBlueButton server provided by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>. For more information on setting up your own BigBlueButton server see <i><a href="http://bigbluebutton.org/support" target="_blank">http://bigbluebutton.org/support.</a></i></strong></div>';
    }
    //Form to update the url of the bigbluebutton server, and it`s salt

    $out .= '
    <form name="form1" method="post" action="">
    <p>BigBlueButton URL:<input type="text" name="bigbluebutton_url" value="'.$url_val.'" size="60"><br> eg. \'http://test-install.blindsidenetworks.com/bigbluebutton/\'
    </p>
    <p>BigBlueButton shared secret:<input type="text" name="bigbluebutton_salt" value="'.$salt_val.'" size="40"><br> It can be found in /var/lib/tomcat7/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties.<br>eg. \'8cd8ef52e8e101574e400365b55e11a6\'.
    </p>

    <p class="submit">
    <input type="submit" name="SubmitSettings" class="button-primary" value="Save Settings" />
    </p>

    </form>
    <hr />';

    return $out;

}

//================================================================================
//------------------------------Permisssion Settings----------------------------------
//================================================================================
// The page allows the user grants permissions for accessing meetings
function bigbluebutton_permission_settings() {
    global $wp_roles;
    $roles = $wp_roles->role_names;
    $roles['anonymous'] = 'Anonymous';

    //Initializes the variable that will collect the output
    $out = '';

    if( isset($_POST['SubmitPermissions']) && $_POST['SubmitPermissions'] == 'Save Permissions' ) {
        foreach($roles as $key => $value) {
            if( !isset($_POST[$key.'-defaultRole']) ) {
              $permissions = array();
                if( $value == "Administrator" ) {
                    $permissions[$key]['defaultRole'] = 'moderator';
                } else if ( $value == "Anonymous" ) {
                    $permissions[$key]['defaultRole'] = 'none';
                } else {
                    $permissions[$key]['defaultRole'] = 'attendee';
                }
            } else {
                $permissions[$key]['defaultRole'] = $_POST[$key.'-defaultRole'];
            }

            if( !isset($_POST[$key.'-participate']) ) {
                $permissions[$key]['participate'] = false;
            } else {
                $permissions[$key]['participate'] = true;
            }

            if( !isset($_POST[$key.'-manageRecordings']) ) {
                $permissions[$key]['manageRecordings'] = false;
            } else {
                $permissions[$key]['manageRecordings'] = true;
            }


        }
        update_option( 'bigbluebutton_permissions', $permissions );

    } else {
        $permissions = get_option('bigbluebutton_permissions');

    }

    //Displays the title of the page
    $out .= "<h2>BigBlueButton Permission Settings</h2>";

    $out .= '</br>';

    $out .= '
    <form name="form1" method="post" action="">
    <table class="stats" cellspacing="5">
    <tr>
    <th class="hed" colspan="1">Role</td>
    <th class="hed" colspan="1">Manage Recordings</th>
    <th class="hed" colspan="1">Participate</th>
    <th class="hed" colspan="1">Join as Moderator</th>
    <th class="hed" colspan="1">Join as Attendee</th>
    <th class="hed" colspan="1">Join with Password</th>
    </tr>';

    foreach($roles as $key => $value) {
        $out .= '
        <tr>
        <td>'.$value.'</td>
        <td><input type="checkbox" name="'.$key.'-manageRecordings" '.($permissions[$key]['manageRecordings']?'checked="checked"': '').' /></td>
        <td><input type="checkbox" name="'.$key.'-participate" '.($permissions[$key]['participate']?'checked="checked"': '').' /></td>
        <td><input type="radio" name="'.$key.'-defaultRole" value="moderator" '.($permissions[$key]['defaultRole']=="moderator"?'checked="checked"': '').' /></td>
        <td><input type="radio" name="'.$key.'-defaultRole" value="attendee" '.($permissions[$key]['defaultRole']=="attendee"?'checked="checked"': '').' /></td>
        <td><input type="radio" name="'.$key.'-defaultRole" value="none" '.($permissions[$key]['defaultRole']=="none"?'checked="checked"': '').' /></td>
        </tr>';
    }

    $out .= '
    </table>
    <p class="submit"><input type="submit" name="SubmitPermissions" class="button-primary" value="Save Permissions" /></p>
    </form>
    <hr />';

    return $out;

}

//================================================================================
//-----------------------------Create a Meeting-----------------------------------
//================================================================================
//This page allows the user to create a meeting
function bigbluebutton_create_meetings() {
    global $wpdb;

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= "<h2>Create a Meeting Room</h2>";

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    //Obtains the meeting information of the meeting that is going to be created
    if( isset($_POST['SubmitCreate']) && $_POST['SubmitCreate'] == 'Create' ) {

        /// Reads the posted values
        $meetingName = htmlspecialchars(stripcslashes($_POST[ 'meetingName' ]));
        $attendeePW = $_POST[ 'attendeePW' ]? $_POST[ 'attendeePW' ]: bigbluebutton_generatePasswd(6, 2);
        $moderatorPW = $_POST[ 'moderatorPW' ]? $_POST[ 'moderatorPW' ]: bigbluebutton_generatePasswd(6, 2, $attendeePW);
        $waitForModerator = (isset($_POST[ 'waitForModerator' ]) && $_POST[ 'waitForModerator' ] == 'True')? true: false;
        $recorded = (isset($_POST[ 'recorded' ]) && $_POST[ 'recorded' ] == 'True')? true: false;
        $meetingVersion = time();
        /// Assign a random seed to generate unique ID on a BBB server
        $meetingID = bigbluebutton_generateToken();


        //Checks to see if the meeting name, attendee password or moderator password was left blank
        if($meetingName == '' || $attendeePW == '' || $moderatorPW == '') {
            //If the meeting name was left blank, the user is prompted to fill it out
            $out .= '<div class="updated">
            <p>
            <strong>All fields must be filled.</strong>
            </p>
            </div>';

        } else {
            $alreadyExists = false;

            //Checks the meeting to be created to see if it already exists in wordpress database
            $table_name = $wpdb->prefix . "bigbluebutton";
            $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName FROM ".$table_name);

            foreach ($listOfMeetings as $meeting) {
                if($meeting->meetingName == $meetingName) {
                    $alreadyExists = true;
                    //Alerts the user to choose a different name
                    $out .= '<div class="updated">
                    <p>
                    <strong>'.$meetingName.' meeting room already exists. Please select a different name.</strong>
                    </p>
                    </div>';
                    break;
                }
            }

            //If the meeting doesn't exist in the wordpress database then create it
            if(!$alreadyExists) {
                $rows_affected = $wpdb->insert( $table_name, array( 'meetingID' => $meetingID, 'meetingName' => $meetingName, 'meetingVersion' => $meetingVersion, 'attendeePW' => $attendeePW, 'moderatorPW' => $moderatorPW, 'waitForModerator' => $waitForModerator? 1: 0, 'recorded' => $recorded? 1: 0) );

                $out .= '<div class="updated">
                <p>
                <strong>Meeting Room Created.</strong>
                </p>
                </div>';

            }

        }

    }

    //Form to create a meeting, the fields are the meeting name, and the optional fields are the attendee password and moderator password
    $out .= '
    <form name="form1" method="post" action="">
    <p>Meeting Room Name: <input type="text" name="meetingName" value="" size="20"></p>
    <p>Attendee Password: <input type="text" name="attendeePW" value="" size="20"></p>
    <p>Moderator Password: <input type="text" name="moderatorPW" value="" size="20"></p>
    <p>Wait for moderator to start meeting: <input type="checkbox" name="waitForModerator" value="True" /></p>
    <p>Recorded meeting: <input type="checkbox" name="recorded" value="True" /></p>
    <p class="submit"><input type="submit" name="SubmitCreate" class="button-primary" value="Create" /></p>
    </form>
    <hr />';

    return $out;

}

require_once('bigbluebutton-list-meetings.php');

//================================================================================
//---------------------------------List Recordings----------------------------------
//================================================================================
// Displays all the recordings available in the bigbluebutton server
function bigbluebutton_list_recordings($title=null) {
    global $wpdb, $wp_roles, $current_user;
    $table_name = $wpdb->prefix . "bigbluebutton";
    $table_logs_name = $wpdb->prefix . "bigbluebutton_logs";

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

    $url_val = get_option('bigbluebutton_url');
    $salt_val = get_option('bigbluebutton_salt');

    $_SESSION['mt_bbb_url'] = $url_val;
    $_SESSION['mt_salt'] = $salt_val;

    //Gets all the meetings from wordpress database
    $listOfMeetings = $wpdb->get_results("SELECT DISTINCT meetingID FROM ".$table_logs_name." WHERE recorded = 1 ORDER BY timestamp;");

    $meetingIDs = '';
    $listOfRecordings = Array();
    if($listOfMeetings) {
        foreach ($listOfMeetings as $meeting) {
            if( $meetingIDs != '' ) $meetingIDs .= ',';
            $meetingIDs .= $meeting->meetingID;
        }
    }

    $listOfRecordings = Array();
    if( $meetingIDs != '' ) {
        $recordingsArray = BigBlueButton::getRecordingsArray($meetingIDs, $url_val, $salt_val);
        if( $recordingsArray['returncode'] == 'SUCCESS' && !$recordingsArray['messageKey'] ) {
            $listOfRecordings = $recordingsArray['recordings'];
        }
    }

    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfRecordings) == 0) {
        $out .= '<div class="updated"><p><strong>There are no recordings available.</strong></p></div>';
        return $out;
    }

    //Displays the title of the page
    if($title)
        $out .= "<h2>".$title."</h2>";

    if ( bigbluebutton_can_manageRecordings($role) ) {
        $out .= '
        <script type="text/javascript">
            wwwroot = \''.get_bloginfo('url').'\'
            function actionCall(action, recordingid) {

                action = (typeof action == \'undefined\') ? \'publish\' : action;

                if (action == \'publish\' || (action == \'delete\' && confirm("Are you sure to delete this recording?"))) {
                    if (action == \'publish\') {
                        var el_a = document.getElementById(\'actionbar-publish-a-\'+ recordingid);
                        if (el_a) {
                            var el_img = document.getElementById(\'actionbar-publish-img-\'+ recordingid);
                            if (el_a.title == \'Hide\' ) {
                                action = \'unpublish\';
                                el_a.title = \'Show\';
                                el_img.src = wwwroot + \'/wp-content/plugins/bigbluebutton/images/show.gif\';
                            } else {
                                action = \'publish\';
                                el_a.title = \'Hide\';
                                el_img.src = wwwroot + \'/wp-content/plugins/bigbluebutton/images/hide.gif\';
                            }
                        }
                    } else {
                        // Removes the line from the table
                        jQuery(document.getElementById(\'actionbar-tr-\'+ recordingid)).remove();
                    }
                    actionurl = wwwroot + "/wp-content/plugins/bigbluebutton/php/broker.php?action=" + action + "&recordingID=" + recordingid;
                    jQuery.ajax({
                            url : actionurl,
                            async : false,
                            success : function(response) {
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
    $out .= '
    <div id="bbb-recordings-div" class="bbb-recordings">
    <table class="stats" cellspacing="5">
      <tr>
        <th class="hed" colspan="1">Recording</td>
        <th class="hed" colspan="1">Meeting Room Name</td>
        <th class="hed" colspan="1">Date</td>
        <th class="hed" colspan="1">Duration</td>';
    if ( bigbluebutton_can_manageRecordings($role) ) {
        $out .= '
        <th class="hedextra" colspan="1">Toolbar</td>';
    }
    $out .= '
      </tr>';
    foreach( $listOfRecordings as $recording) {
        if ( bigbluebutton_can_manageRecordings($role) || $recording['published'] == 'true') {
            /// Prepare playback recording links
            $type = '';
            foreach ( $recording['playbacks'] as $playback ) {
                if ($recording['published'] == 'true') {
                    $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
                } else {
                    $type .= $playback['type'].'&#32;';
                }
            }

            /// Prepare duration
            $endTime = isset($recording['endTime'])? floatval($recording['endTime']):0;
            $endTime = $endTime - ($endTime % 1000);
            $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
            $startTime = $startTime - ($startTime % 1000);
            $duration = intval(($endTime - $startTime) / 60000);

            /// Prepare date
            //Make sure the startTime is timestamp
            if( !is_numeric($recording['startTime']) ) {
                $date = new DateTime($recording['startTime']);
                $recording['startTime'] = date_timestamp_get($date);
            } else {
                $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
            }

            //Format the date
            //$formatedStartDate = gmdate("M d Y H:i:s", $recording['startTime']);
            $formatedStartDate = date_i18n( "M d Y H:i:s", $recording['startTime'], false );

            //Print detail
            $out .= '
            <tr id="actionbar-tr-'.$recording['recordID'].'">
              <td>'.$type.'</td>
              <td>'.$recording['meetingName'].'</td>
              <td>'.$formatedStartDate.'</td>
              <td>'.$duration.' min</td>';

            /// Prepare actionbar if role is allowed to manage the recordings
            if ( bigbluebutton_can_manageRecordings($role) ) {
                $action = ($recording['published'] == 'true')? 'Hide': 'Show';
                $actionbar = "<a id=\"actionbar-publish-a-".$recording['recordID']."\" title=\"".$action."\" href=\"#\"><img id=\"actionbar-publish-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bigbluebutton/images/".strtolower($action).".gif\" class=\"iconsmall\" onClick=\"actionCall('publish', '".$recording['recordID']."'); return false;\" /></a>";
                $actionbar .= "<a id=\"actionbar-delete-a-".$recording['recordID']."\" title=\"Delete\" href=\"#\"><img id=\"actionbar-delete-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bigbluebutton/images/delete.gif\" class=\"iconsmall\" onClick=\"actionCall('delete', '".$recording['recordID']."'); return false;\" /></a>";
                $out .= '
                <td>'.$actionbar.'</td>';
            }

            $out .= '
            </tr>';
        }
    }

    //Print end of the table
    $out .= '  </table>
    </div>';

    return $out;

}


//Begins the table of list meetings with the number of columns specified
function bigbluebutton_print_table_header() {
    return '
    <div>
    <table class="stats" cellspacing="5">
      <tr>
        <th class="hed" colspan="1">Meeting Room Name</td>
        <th class="hed" colspan="1">Meeting Token</td>
        <th class="hed" colspan="1">Attendee Password</td>
        <th class="hed" colspan="1">Moderator Password</td>
        <th class="hed" colspan="1">Wait for Moderator</td>
        <th class="hed" colspan="1">Recorded</td>
        <th class="hedextra" colspan="1">Actions</td>
      </tr>';
}

//================================================================================
//------------------------------- Helping functions ------------------------------
//================================================================================
//Validation methods
function bigbluebutton_can_participate($role) {
    $permissions = get_option('bigbluebutton_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['participate']) && $permissions[$role]['participate'] );

}

function bigbluebutton_can_manageRecordings($role) {
    $permissions = get_option('bigbluebutton_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['manageRecordings']) && $permissions[$role]['manageRecordings'] );

}

function bigbluebutton_validate_defaultRole($wp_role, $bbb_role) {
    $permissions = get_option('bigbluebutton_permissions');
    if( $wp_role == null || $wp_role == 'unregistered' || $wp_role == '' )
        $role = 'anonymous';
    else
        $role = $wp_role;
    return ( isset($permissions[$role]['defaultRole']) && $permissions[$role]['defaultRole'] == $bbb_role );
}

function bigbluebutton_generateToken($tokenLength=6) {
    $token = '';

    if(function_exists('openssl_random_pseudo_bytes')) {
        $token .= bin2hex(openssl_random_pseudo_bytes($tokenLength));
    } else {
        //fallback to mt_rand if php < 5.3 or no openssl available
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters)-1;
        $tokenLength *= 2;

        //select some random characters
        for ($i = 0; $i < $tokenLength; $i++) {
            $token .= $characters[mt_rand(0, $charactersLength)];
        }
    }

    return $token;
}

function bigbluebutton_generatePasswd($numAlpha=6, $numNonAlpha=2, $salt='') {
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';

    $pepper = '';
    do{
        $pepper = str_shuffle( substr(str_shuffle($listAlpha),0,$numAlpha) . substr(str_shuffle($listNonAlpha),0,$numNonAlpha) );
    } while($pepper == $salt);

    return $pepper;
}

function bigbluebutton_normalizeMeetingID($meetingID) {
    return (strlen($meetingID) == 12)? sha1(home_url().$meetingID): $meetingID;
}

function bigbluebutton_permissionsAssignment($roles, $permissions){
  foreach($roles as $key => $value) {
      $permissions[$key]['participate'] = true;
      if($value == "Administrator") {
          $permissions[$key]['manageRecordings'] = true;
          $permissions[$key]['defaultRole'] = "moderator";
      } else if($value == "Anonymous") {
          $permissions[$key]['manageRecordings'] = false;
          $permissions[$key]['defaultRole'] = "none";
      } else {
          $permissions[$key]['manageRecordings'] = false;
          $permissions[$key]['defaultRole'] = "attendee";
      }

  }
}
