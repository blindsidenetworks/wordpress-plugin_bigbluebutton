<?php
/*
Plugin Name: BigBlueButton
Plugin URI: http://blindsidenetworks.com/integration
Description: BigBlueButton is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms by using a Custom Post Type. For more information on setting up your own BigBlueButton server or for using an external hosting provider visit http://bigbluebutton.org/support
Version: 2.0.0
Author: Blindside Networks
Author URI: http://blindsidenetworks.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

global $wp_version;
$exitmessage = 'This plugin has been designed for Wordpress 2.5 and later, please upgrade your current one.';

if (version_compare($wp_version, '2.5', '<')) {
    exit($exitmessage);
}

require_once 'includes/bbb_api.php';

define('BIGBLUEBUTTON_PLUGIN_VERSION', bigbluebutton_get_version());
define('BIGBLUEBUTTON_DEFAULT_ENDPOINT', 'http://test-install.blindsidenetworks.com/bigbluebutton/');
define('BIGBLUEBUTTON_DEFAULT_SECRET', '8cd8ef52e8e101574e400365b55e11a6');
//================================================================================
//--------------------------------Hooks----------------------------------------
//================================================================================

// Activation definitions.
register_activation_hook(__FILE__, 'bigbluebutton_plugin_activate');

// Deinstallation definitions.
register_uninstall_hook(__FILE__, 'bigbluebutton_plugin_uninstall' );

// Action definitions.
add_action('init', 'bigbluebutton_init');
add_action('admin_menu', 'bigbluebutton_register_settings_page', 1);
add_action('add_meta_boxes', 'bigbluebutton_meta_boxes');
add_action('save_post', 'bigbluebutton_save_data');
add_action('save_post', 'bigbluebutton_room_status_metabox', 999);
add_action('admin_notices', 'bigbluebutton_admin_notices');
add_action('admin_notices', 'bigbluebutton_error_notice');
add_action('widgets_init', 'bigbluebutton_widget_init');
add_action('before_delete_post', 'before_bbb_delete');
add_action('in_plugin_update_message-bigbluebutton/bigbluebutton-plugin.php', 'bigbluebutton_show_upgrade_notification');

// Shortcode definitions
add_shortcode('bigbluebutton', 'bigbluebutton_shortcode');
add_shortcode('bigbluebutton_recordings', 'bigbluebutton_shortcode');

// Filter definitions
add_filter('map_meta_cap', 'bigbluebutton_map_meta_cap', 10, 4);
add_filter('the_content', 'bigbluebutton_filter');


//================================================================================
//--------------------------------Plugin Activation-------------------------------
//================================================================================

/**
* On activation script
*/
function bigbluebutton_plugin_activate($network_wide)
{
    global $wpdb;
    if (is_multisite() && $network_wide) {
        $multisiteblogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($multisiteblogs as $blogid) {
            switch_to_blog($blogid);
            bigbluebutton_activate();
            restore_current_blog();
        }
    } else {
        bigbluebutton_activate();
    }
}


/**
* BigBlueButton activate
*/
function bigbluebutton_activate()
{
    if (get_option('bigbluebutton_version') == BIGBLUEBUTTON_PLUGIN_VERSION) {
        // Simple activation.
        bigbluebutton_session_setup(get_option('bigbluebutton_endpoint'), get_option('bigbluebutton_secret'));
	      return;
    }
    // Activation either for update or installation.
    add_option('bigbluebutton_version', BIGBLUEBUTTON_PLUGIN_VERSION);
    $bbburl = get_option('bigbluebutton_url');
    $bbbsalt = get_option('bigbluebutton_salt');
    if ($bbburl || $bbbsalt) {
        // Activation for update.
        delete_option('bigbluebutton_url');
        delete_option('bigbluebutton_salt');
        bigbluebutton_migrate_old_plugin_meetings();
        bigbluebutton_migrate_old_plugin_roles();
        add_option('bigbluebutton_endpoint', $bbburl);
        add_option('bigbluebutton_secret', $bbbsalt);
        bigbluebutton_session_setup($bbburl, $bbbsalt);
        return;
    }
    // Activation for installation.
	  bigbluebutton_add_default_rooms();
    bigbluebutton_set_default_roles();
    add_option('bigbluebutton_endpoint', BIGBLUEBUTTON_DEFAULT_ENDPOINT);
    add_option('bigbluebutton_secret', BIGBLUEBUTTON_DEFAULT_SECRET);
    bigbluebutton_session_setup(BIGBLUEBUTTON_DEFAULT_ENDPOINT, BIGBLUEBUTTON_DEFAULT_SECRET);
}

function bigbluebutton_add_default_rooms()
{
    if (post_exists("Demo meeting") == 0) {
        bigbluebutton_insert_post(1, "Demo meeting", bigbluebutton_generate_token(), 'ap', 'mp', 0, 0);
    }
    if (post_exists("Demo meeting (recorded)") == 0) {
        bigbluebutton_insert_post(2, "Demo meeting (recorded)", bigbluebutton_generate_token(), 'ap', 'mp', 0, 1);
    }
}

//================================================================================
//--------------------------------Plugin Deinstallation-------------------------------
//================================================================================

function bigbluebutton_plugin_uninstall () {
    global $wpdb;

    // In case is deactivateing an overwritten super old version
    if( get_option('bbb_db_version') ) {
        $table_name_old = $wpdb->prefix . "bbb_meetingRooms";
        $wpdb->query("DROP TABLE IF EXISTS $table_name_old");
        delete_option('bbb_db_version');
        delete_option('mt_bbb_url');
        delete_option('mt_salt');
    }

    // Remove old tables if exist
    $tables = array('bigbluebutton', 'bigbluebutton_logs');
    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . $table;
        $wpdb->query($sql);
    }

    // Remove old options if exist
    delete_option('bigbluebutton_plugin_version');
    delete_option('bigbluebutton_url');
    delete_option('bigbluebutton_salt');
    delete_option('bigbluebutton_permissions');

    // Remove current meeting rooms
    $args = array (
        'post_type' => 'room',
        'nopaging' => true
    );
    $query = new WP_Query($args);
    while ($query->have_posts()) {
        $query->the_post();
        $id = get_the_ID();
        wp_delete_post($id, true);
    }
    wp_reset_postdata();

    // Remove current options
    delete_option('bigbluebutton_version');
    delete_option('bigbluebutton_endpoint');
    delete_option('bigbluebutton_secret');
}

//================================================================================
//---------------------------------Upgrade----------------------------------------
//================================================================================

/*
 * Show Upgrade Notification in Plugin List for an available new Version.
 */
function bigbluebutton_show_upgrade_notification($currentPluginMetadata, $newPluginMetadata)
{
    if (!$newPluginMetadata) {
        $newPluginMetadata = bigbluebutton_update_metadata($currentPluginMetadata['slug']);
    }
    // check "upgrade_notice"
    if (isset($newPluginMetadata->upgrade_notice) && strlen(trim($newPluginMetadata->upgrade_notice)) > 0) {
        echo '<div style="background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px"><strong>Important Upgrade Notice:</strong> ';
        echo esc_html(strip_tags($newPluginMetadata->upgrade_notice)), '</div>';
    }
}

function bigbluebutton_update_metadata($pluginslug)
{
    $plugin_updates = get_plugin_updates();
    foreach ($plugin_updates as $update) {
        if ($update->update->slug === $pluginslug) {
            return $update->update;
        }
    }
}

//================================================================================
//--------------------------------Migration----------------------------------------
//================================================================================

/**
* Previous meeting's rooms information assigned to new plugins data strucure
**/
function bigbluebutton_migrate_old_plugin_meetings()
{
    global $wpdb;
    $tablename = $wpdb->prefix . "bigbluebutton";
    $listofmeetings = $wpdb->get_results("SELECT * FROM ".$tablename." ORDER BY id");

    foreach ($listofmeetings as $meeting) {
        bigbluebutton_insert_post($meeting->id, $meeting->meetingName, $meeting->meetingID, $meeting->attendeePW, $meeting->moderatorPW, $meeting->waitForModerator, $meeting->recorded);
    }
}

/**
* Previous capabilities assigned to new plugins capabilities
**/
function bigbluebutton_migrate_old_plugin_roles()
{
    $permissions = get_option('bigbluebutton_permissions');
    //'custom_create_meeting' => 'Create Meetings',
    //'custom_join_meeting_moderator' => 'Join Meetings as Moderator',
    //'custom_join_meeting_attendee' => 'Join Meetings as Attendee',
    //'custom_join_meeting_password' => 'Join Meetings with password',
    //'custom_manage_recordings' => 'Manage Recordings',
    //'custom_manage_others_recordings' => 'Manage Others\'Recordings',
    $adminrole = get_role('administrator');
    bigbluebutton_assign_role($adminrole, 'administrator', $permissions);
    $adminrole->add_cap('custom_join_meeting_moderator', false);
    $adminrole->add_cap('manage_recordings_room', $permissions["administrator"]["manageRecordings"]);

    $authorrole = get_role('author');
    bigbluebutton_assign_role($authorrole, 'author', $permissions);
    $authorrole->add_cap('join_with_password_room', false);
    $authorrole->add_cap('manage_recordings_room', $permissions["author"]["manageRecordings"]);

    $contributorrole = get_role('contributor');
    bigbluebutton_assign_role($contributorrole, 'contributor', $permissions);
    $contributorrole->add_cap('join_with_password_room', false);
    $contributorrole->add_cap('manage_recordings_room', $permissions["contributor"]["manageRecordings"]);

    $editorrole = get_role('editor');
    bigbluebutton_assign_role($editorrole, 'editor', $permissions);
    $editorrole->add_cap('join_with_password_room', false);
    $editorrole->add_cap('manage_recordings_room', $permissions["editor"]["manageRecordings"]);

    $subscriberrole = get_role('subscriber');
    bigbluebutton_assign_role($subscriberrole, 'subscriber', $permissions);
    $subscriberrole->add_cap('join_with_password_room', false);
    $subscriberrole->add_cap('manage_recordings_room', $permissions["subscriber"]["manageRecordings"]);

    add_role('anonymous', 'Anonymous', array());
    $anonymousrole = get_role('anonymous');
    bigbluebutton_assign_role($anonymousrole, 'anonymous', $permissions);
    $anonymousrole->add_cap('join_with_password_room', true);
    $anonymousrole->add_cap('manage_recordings_room', $permissions["anonymous"]["manageRecordings"]);
}

/**
* Assign roles
* @param  array  $role The role that needs to be assigned the role.
* @param  string  $rolename  String format of the role name.
* @param  array $permissions  Permissions array.
* @return
**/
function bigbluebutton_assign_role($role, $rolename, $permissions)
{
    if ($permissions[$rolename]["defaultRole"] == "moderator") {
        $role->add_cap('join_as_moderator_room', true);
        $role->add_cap('join_as_attendee_room', false);
    } else {
        $role->add_cap('join_as_moderator_room', false);
        $role->add_cap('join_as_attendee_room', true);
    }
}

function bigbluebutton_insert_post($meetingid, $meetingname, $meetingtoken, $attendeepassword, $moderatorpassword, $waitformoderator, $recorded)
{
    $postarray = array(
      'import_id' => $meetingid,
      'post_title' => $meetingname,
      'post_type' => 'room',
      'post_status' => 'publish',
    );
    $postid = wp_insert_post($postarray);
    update_post_meta($postid, '_bbb_room_token', $meetingtoken);
    update_post_meta($postid, '_bbb_attendee_password', $attendeepassword);
    update_post_meta($postid, '_bbb_moderator_password', $moderatorpassword);
    update_post_meta($postid, '_bbb_must_wait_for_admin_start', $waitformoderator);
    update_post_meta($postid, '_bbb_is_recorded', $recorded);
}

//================================================================================
//------------------------------ Main ----------------------------------
//================================================================================

/**
* Sessions are required by the plugin to work.
*/
function bigbluebutton_init()
{
    bigbluebutton_start_session();
    bigbluebutton_room_taxonomies();
    bigbluebutton_init_custom_post_type();
    bigbluebutton_css_enqueue();
    bigbluebutton_frontend_css_enqueue();
    bigbluebutton_scripts();
}

/**
* BigBlueButton Start Session
*/
function bigbluebutton_start_session()
{
    if (!session_id()) {
        session_start();
    }
}

/*
 * BBB room taxonomy
 *
 * */
function bigbluebutton_room_taxonomies()
{
    $singular = 'Room';
    $labels = array(
        'name' => _x($singular.' Categories', 'taxonomy general name'),
        'singular_name' => _x($singular.' Category', 'taxonomy singular name'),
        'search_items' => __('Search '.$singular.' Categories'),
        'popular_items' => __('Popular '.$singular.'  Categories'),
        'all_items' => __('All '.$singular.'  Categories'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __('Edit '.$singular.' Category'),
        'update_item' => __('Update '.$singular.' Category'),
        'add_new_item' => __('Add New '.$singular.' Category'),
        'new_item_name' => __('New '.$singular.' Category Name'),
        'separate_items_with_commas' => __('Separate '.$singular.' categories with commas'),
        'add_or_remove_items' => __('Add or remove '.$singular.' categories'),
        'choose_from_most_used' => __('Choose from the most used '.$singular.' categories'),
        'menu_name' => __('Categories'),
    );
    register_taxonomy('room-category', array('room'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'room-category'),
        'capabilities' => array(
                'manage_terms' => 'manage_bbb-cat',
                'edit_terms' => 'edit_bbb-cat',
                'delete_terms' => 'delete_bbb-cat',
                'assign_terms' => 'assign_bbb-cat', ),
    ));
}
/*
* BBB Room Cutom Post Type Declaration
*/
function bigbluebutton_init_custom_post_type()
{
    $singular = 'Room';
    $plural = 'Rooms';
    $labels = array(
        'name' => _x($plural, 'post type general name'),
        'singular_name' => _x($singular, 'post type singular name'),
        'add_new' => _x('Add New', 'bbb'),
        'add_new_item' => __('Add New '.$singular),
        'edit_item' => __('Edit '.$singular),
        'new_item' => __('New '.$singular),
        'all_items' => __('All '.$plural),
        'view_item' => __('View '.$singular),
        'search_items' => __('Search '.$plural),
        'not_found' => __('No '.$plural.' found'),
        'not_found_in_trash' => __('No '.$plural.' found in Trash'),
        'parent_item_colon' => '',
        'menu_name' => $plural,
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'room', 'with_front' => false),
        'capability_type' => 'room',
        'capabilities' => array(
          'edit_posts' => 'Edit Rooms',
          'edit_others_posts' => 'Edit Others\'Rooms',
          'publish_posts' => 'Publish Rooms',
          'read_private_posts' => 'Read Private Rooms',
          'delete_posts' => 'Delete Rooms',
          'delete_private_posts' => 'Delete Private Rooms',
          'delete_published_posts' => 'Delete Published Rooms',
          'delete_others_posts' => 'Delete Others\'Rooms',
          'edit_private_posts' => 'Edit Private Rooms',
          'edit_published_posts' => 'Edit Published Rooms',
          'custom_create_meeting' => 'Create Meetings',
          'custom_join_meeting_moderator' => 'Join Meetings as Moderator',
          'custom_join_meeting_attendee' => 'Join Meetings as Attendee',
          'custom_join_meeting_password' => 'Join Meetings with password',
          'custom_manage_recordings' => 'Manage Recordings',
          'custom_manage_others_recordings' => 'Manage Others\'Recordings',
          //'joinat' => 'join_as_attendee_room',
          //'joinmd' => 'join_as_moderator_room',
          //'joinpw' => 'join_with_password_room',
          //'managerecordings' => 'manage_recordings_room',
          //'publish_post' => 'publish_recordings_own_room',
          //'create_rooms' => 'edit_plugins_room',
        ),
        'map_meta_cap' => true,
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'menu_icon' => 'dashicons-video-alt2',
        'supports' => array('title', 'editor', 'page-attributes', 'author'),
    );
    register_post_type('room', $args);
}

function bigbluebutton_map_meta_cap()
{
    /**
   * NOTE: This method cannot be deleted as it is needed for the hook
   */
}

/*
 * This displays some CSS needed for the BigBlueButton plugin, in the backend
 */
function bigbluebutton_css_enqueue()
{
    $css = plugins_url('css/bigbluebutton.css', __FILE__);
    wp_register_style('bigbluebutton_css', $css);
    wp_enqueue_style('bigbluebutton_css');
}

/*
 * This displays some CSS needed for the BigBlueButton plugin, in the frontend
 */
function bigbluebutton_frontend_css_enqueue()
{
    $css = plugins_url('css/bigbluebutton_frontend.css', __FILE__);
    wp_enqueue_style('bigbluebutton_frontend_css', $css);
    wp_enqueue_style('bigbluebutton_frontend_css');
}

/*
 * This displays some JavaScript needed for the BigBlueButton plugin, in the backend
 */
function bigbluebutton_scripts()
{
    wp_enqueue_script('jquery');
    $js = plugins_url('js/bigbluebutton-plugin.js', __FILE__);
    wp_register_script('bigbluebutton-plugin_script', $js);
    wp_enqueue_script('bigbluebutton-plugin_script');
    wp_localize_script('bigbluebutton-plugin_script', 'bbbScript', array(
    'pluginsUrl' => bigbluebutton_plugin_base_url()
    ));
}

//===============================================================================================
//------------------------------  BigBlueButton Settings page -----------------------------------
//===============================================================================================

/**
*   Inserts the BigBlueButton Settings page unter the Settings menu
*/
function bigbluebutton_register_settings_page()
{
    add_submenu_page('options-general.php', 'Site Options', 'BigBlueButton', 'edit_pages', 'site-options', 'bigbluebutton_options_page_callback');
}

/**
 * Update notice success.
 */
function bigbluebutton_update_notice_success()
{
    echo '<div class="updated">
       <p>BigBlueButton options have been updated.</p>
    </div>';
}

/**
 * Update notice fail.
 */
function bigbluebutton_update_notice_fail()
{
    echo '<div class="error">
       <p>BigBlueButton options failed to update.</p>
    </div>';
}

/**
 * Update notice no change.
 */
function bigbluebutton_update_notice_no_change()
{
    echo '<div class="updated">
       <p>BigBlueButton options have not changed.</p>
    </div>';
}

/**
 * BigBlueButton Settings page.
 */
function bigbluebutton_options_page_callback()
{
	  $outputstring = '';
	  $outputstring .= '<div class="wrap">'."\n";
	  $outputstring .= '<div id="icon-options-general" class="icon32"><br /></div><h2>BigBlueButton Settings</h2>'."\n";
	  $outputstring .= '    <form  action="'.$_SERVER['REQUEST_URI'].'" method="post" name="site_options_page" >'."\n";
	  $outputstring .= '        <h2 class="title">Server</h2>'."\n";
	  $outputstring .= '        <p>The settings listed below determine the BigBlueButton server that will be used for the live sessions.</p>'."\n";
	  $outputstring .= '        <table class="form-table">'."\n";
	  $outputstring .= '            <tr>'."\n";
	  $outputstring .= '                <th scope="row">Endpoint</th>'."\n";
	  $outputstring .= '                <td>'."\n";
	  $outputstring .= '                    <input type="text" size="56" name="endpoint" value="'.trim(get_option('bigbluebutton_endpoint')).'" />'."\n";
	  $outputstring .= '                    <p>Example: http://test-install.blindsidenetworks.com/bigbluebutton/</p>'."\n";
	  $outputstring .= '                </td>'."\n";
	  $outputstring .= '            </tr>'."\n";
	  $outputstring .= '            <tr>'."\n";
	  $outputstring .= '                <th>Shared Secret</th>'."\n";
	  $outputstring .= '                <td>'."\n";
	  $outputstring .= '                    <input type="text" size="56" name="secret" value="'.trim(get_option('bigbluebutton_secret')).'" />'."\n";
	  $outputstring .= '                    <p>Example: 8cd8ef52e8e101574e400365b55e11a6</p>'."\n";
	  $outputstring .= '                </td>'."\n";
	  $outputstring .= '            </tr>'."\n";
	  $outputstring .= '        </table>'."\n";
	  $outputstring .= '        <p>The default values included as part of this settings are for using a FREE BigBlueButton server provided by Blindside Networks for testing purposes. They must be replaced with the parameters obtained from a BigBlueButton server better suited for production.</p>'."\n";
	  $outputstring .= '        <p class="submit">'."\n";
	  $outputstring .= '            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">'."\n";
	  $outputstring .= '        </p>'."\n";
	  $outputstring .= '    </form>'."\n";
	  $outputstring .= '    </div>'."\n";
	  echo $outputstring;
}

if (is_admin() && (isset($_POST['endpoint']) || isset($_POST['secret']))) {
    $doupdate = 0;
    $bbbendpoint = get_option('bigbluebutton_endpoint');
    if (isset($_POST['endpoint']) && ($bbbendpoint != $_POST['endpoint'])) {
        $bbbendpoint = $_POST['endpoint'];
        $doupdate = 1;
    }
    $bbbsecret = get_option('bigbluebutton_secret');
    if (isset($_POST['secret']) && ($bbbsecret != $_POST['secret'])) {
        $bbbsecret = $_POST['secret'];
        $doupdate = 1;
    }
    if ($doupdate) {
        $updateendpoint = update_option('bigbluebutton_endpoint', $bbbendpoint);
        $updatesecret = update_option('bigbluebutton_secret', $bbbsecret);
        if ($updateendpoint && $updatesecret) {
            add_action('admin_notices', 'bigbluebutton_update_notice_success');
        } else {
            add_action('admin_notices', 'bigbluebutton_update_notice_fail');
        }
    } else {
        add_action('admin_notices', 'bigbluebutton_update_notice_no_change');
    }
}

//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================

// Inserts a bigbluebuttonrooms widget on the siderbar of the blog.
function bigbluebutton_sidebar($args)
{
    $currentuser = wp_get_current_user();
    $bbbposts = bigbluebutton_get_bbb_posts('0', '');
    $atts = array('join' => 'true');
    bigbluebutton_shortcode_defaults($atts, 'rooms');
    echo $args['before_widget'];
    echo $args['before_title'].'BigBlueButton Rooms'.$args['after_title'];
    echo bigbluebutton_shortcode_output_form($bbbposts, $atts, $currentuser);
    echo $args['after_widget'];
}
// Inserts a bigbluebutton widget on the siderbar of the blog.
function bigbluebutton_rooms_sidebar($args)
{
    $currentuser = wp_get_current_user();
    $bbbposts = bigbluebutton_get_bbb_posts('0', '');
    $atts = array('join' => 'false');
    bigbluebutton_shortcode_defaults($atts, 'rooms');
    echo $args['before_widget'];
    echo $args['before_title'].'BigBlueButton'.$args['after_title'];
    echo bigbluebutton_shortcode_output_form($bbbposts, $atts, $currentuser);
    echo $args['after_widget'];
}

// Registers the bigbluebutton widget.
function bigbluebutton_widget_init()
{
    wp_register_sidebar_widget('bigbluebuttonsidebarwidget', __('BigBlueButton'), 'bigbluebutton_sidebar',
                          array('description' => 'Displays a BigBlueButton login form in a sidebar.'));
    wp_register_sidebar_widget('bigbluebuttonroomswidget', __('BigBlueButton Rooms'), 'bigbluebutton_rooms_sidebar',
                          array('description' => 'Displays a dropdown menu for selecting BigBlueButton Rooms in a sidebar.'));
}


//================================================================================
//---------------------------------Shortcode--------------------------------------
//----------------------------Rooms and Recordings--------------------------------
//================================================================================

/**
*   Adding BigBlueButton shortcode according to the tag provided
*
* @param  array  $atts The shortcode attributes: type, title, join.
* @param  array  $content   Content of the shortcode.
* @param  string $tag   The shortcode tag.
* @return
*/
function bigbluebutton_shortcode($atts, $content, $tag)
{
	  bigbluebutton_shortcode_defaults($atts, $tag);
    $pairs = array('bbb_categories' => '0',
                   'bbb_posts' => '');
    extract(shortcode_atts($pairs, $atts));
    $bbbposts = bigbluebutton_get_bbb_posts($bbbcategories, $bbbposts);
    return bigbluebutton_shortcode_output($bbbposts, $atts);
}

/**
 * Updates shortcode attributes based on the tag.
 *
 * @param  array  &$atts The shortcode attributes.
 * @param  string $tag   The shortcode tag.
 * @return
 */
function bigbluebutton_shortcode_defaults(&$atts, $tag)
{//handle spelling mistakes//default for token empty
    if ($atts == null) {
        $atts = array();
    }
    if (!array_key_exists('type', $atts)) {
        $atts['type'] = bigbluebutton_shortcode_type_default($tag);
    }
    if (!array_key_exists('title', $atts)) {
        $atts['title'] = bigbluebutton_shortcode_title_default($atts['type']);
    }
    if (!array_key_exists('join', $atts)) {
        $atts['join'] = bigbluebutton_shortcode_join_default($atts['type']);
    }
    if (!array_key_exists('enclosed', $atts)) {
        $atts['enclosed'] = 'true';
    }
    if (!array_key_exists('style', $atts)) {
        $atts['style'] = 'primary';
    }
    if (!array_key_exists('size', $atts)) {
        $atts['size'] = 'normal';
    }
    if (!array_key_exists('target', $atts)) {
        $atts['target'] = '_self';
    }
    if (!array_key_exists('display', $atts)) {
        $atts['display'] = 'block';
    }
}


/**
 * Returns the default shortcode type based on its tag.
 *
 * @param  string $tag  The shortcode tag.
 * @return string
 */
function bigbluebutton_shortcode_type_default($tag)
{
    if ($tag == 'bigbluebutton_recordings') {
        return 'recordings';
    }
    return 'meetings';
}

/**
 * Returns the default tile for a form based on the shortcode type.
 *
 * @param  string $type  The shortcode type.
 * @return string
 */
function bigbluebutton_shortcode_title_default($type)
{
    if ($type == 'recordings') {
        return 'Recordings';
    }
    if ($type == 'rooms') {
        return 'Rooms';
    }
    return 'Meetings';
}

/**
 * Returns the default join for a form based on the shortcode type.
 *
 * @param  string $type  The shortcode type.
 * @return string
 */
function bigbluebutton_shortcode_join_default($type)
{
    if ($type == 'recordings' || $type == 'rooms') {
        return 'false';
    }
    return 'true';
}

// BigBlueButton shortcodes.
function bigbluebutton_get_bbb_posts($bbbcategories, $bbbposts)
{
    $args = array('post_type' => 'room',
                  'orderby' => 'name',
                  'posts_per_page' => -1,
                  'order' => 'ASC',
      );
    if ($bbbcategories) {
        $taxquery = array(
                'taxonomy' => 'room-category',
                'field' => 'id',
                'terms' => explode(',', $bbbcategories),
              );
        $args['tax_query'] = array($taxquery);
    }
    if ($bbbposts) {
        $args['post__in'] = explode(',', $bbbposts);
    }
    $bbbposts = new WP_Query($args);
    return $bbbposts;
}


/**
*   Sets the output for the shortcodes.
*
* @param  array  $atts The shortcode attributes: type, title, join.
* @param  array  $bbbposts  Information about the post.
* @return
*/
function bigbluebutton_shortcode_output($bbbposts, $atts)
{
    $currentuser = wp_get_current_user();
    $endpointvalue = get_option('bigbluebutton_endpoint');
    $secretvalue = get_option('bigbluebutton_secret');
    bigbluebutton_session_setup($endpointvalue, $secretvalue);
    if ($atts['type'] == 'recordings') {
        return bigbluebutton_shortcode_output_recordings($bbbposts, $atts, $currentuser, $endpointvalue, $secretvalue);
    }
    return bigbluebutton_shortcode_output_form($bbbposts, $atts, $currentuser);
}

/**
*   Shortcode output form for the rooms tag.
*
* @param  array  $bbbposts  Information about the post.
* @param  array  $atts The shortcode attributes: type, title, join.
* @return
*/
function bigbluebutton_shortcode_output_form($bbbposts, $atts, $currentuser)
{
    if (!$bbbposts->have_posts()) {
        return '<p> No rooms have been created. </p>';
    }
    $joinorview = "Join";
    if ($atts['join'] === "false") {
        $joinorview = "View";
    }
    if ($atts['enclosed'] === "true") {
        $outputstring  = '<form id="room" class="bbb-shortcode">'."\n";
        $outputstring .= '  <div id="bbb-join-container"></div>'."\n";
        $outputstring .= '  <div id="bbb-error-container"></div>'."\n";
        $outputstring .= '  <label>'.$atts['title'].'</label>'."\n";
		}
    $posts = $bbbposts->get_posts();
    if ((count($posts) == 1)||strlen($atts['token']) == 12) {
        $outputstring .= bigbluebutton_shortcode_output_form_single($bbbposts, $atts, $currentuser, $joinorview);
    } else {
        $outputstring .= bigbluebutton_shortcode_output_form_multiple($bbbposts, $atts, $currentuser, $joinorview);
    }
    if ($atts['enclosed'] === "true") {
        $outputstring .= '</form>'."\n";
	  }
    return $outputstring;
}

/**
*   Shortcode output form for single room.
*
* @param  array  $bbbposts  Information about the post.
* @param  array  $atts The shortcode attributes: type, title, join.
* @param  array $currentuser Details of the current user
* @param  string $joinorview join or view stirng on the button
* @return
*/
function bigbluebutton_shortcode_output_form_single($bbbposts, $atts, $currentuser, $joinorview)
{
    $outputstring = '';
    $slug = $bbbposts->post->post_name;
    $title = $bbbposts->post->post_title;
	  $text = $joinorview.' '.$title;
	  if (array_key_exists('text', $atts)) {
	      $text = $atts['text'];
	  }
    $outputstring .= bigbluebutton_form_setup($currentuser, $atts);
    $outputstring .= '<input type="hidden" name="hiddenInput" id="hiddenInput" value="'.$slug.'" />';
    $usercapabilitiesarray = bigbluebutton_assign_capabilities_array($currentuser);
    $outputstring .= '<a href="#" class="btn btn-'.$atts['style'].' btn-'.$atts['size'].' btn-'.$atts['display'].' bbb-shortcode-selector" onClick="bigbluebutton_join_meeting(\''.$atts['join'].'\',\''.json_encode(is_user_logged_in()).'\',\''.json_encode($usercapabilitiesarray["join_with_password_room"]).'\')" >'.$text.'</a>'."\n";
    return $outputstring;
}

/**
*   Shortcode output form for multiple rooms.
*
* @param  array  $bbbposts  Information about the post.
* @param  array  $atts The shortcode attributes: type, title, join.
* @return
*/
function bigbluebutton_shortcode_output_form_multiple($bbbposts, $atts, $currentuser, $joinorview)
{
    $outputstring = '<select class="bbb-shortcode" id="bbbRooms">'."\n";
    $outputstring .= '<option disabled selected value>select '.$atts['type'].'</option>'."\n";
    while ($bbbposts->have_posts()) {
        $bbbposts->the_post();
        $slug = $bbbposts->post->post_name;
        $title = $bbbposts->post->post_title;
        $roomtoken = get_post_meta($bbbposts->post->ID, '_bbb_room_token', true);
        if ($atts['token'] == null||(strpos($atts['token'], $roomtoken) !== false)) {
            $outputstring .= '<option value="'.$slug.'">'.$title.'</option>'."\n";
        }
    }
    wp_reset_postdata();
    $outputstring .= '</select>'."\n";
    $outputstring .= bigbluebutton_form_setup($currentuser, $atts);
    $outputstring .= '<input type="hidden" name="hiddenInput" id="hiddenInput" value="'.$slug.'" />';
    $usercapabilitiesarray = bigbluebutton_assign_capabilities_array($currentuser);
    $outputstring .= '<a href="#" class="btn btn-'.$atts['style'].' btn-'.$atts['size'].' btn-'.$atts['display'].' bbb-shortcode-selector" onClick="bigbluebutton_join_meeting(\''.$atts['join'].'\',\''.json_encode(is_user_logged_in()).'\',\''.json_encode($usercapabilitiesarray["join_with_password_room"]).'\')" >'.$joinorview.'</a>'."\n";
    return $outputstring;
}

/**
*   Sets the password form for the Room widget.
*
* @param  array $currentuser Details fo the current user
* @param  array  $atts The shortcode attributes: type, title, join.
* @return
*/
function bigbluebutton_form_setup($currentuser, $atts)
{
    $outputstring = '';
    $userArray = array();
    if ((is_user_logged_in() == true)) {
        $userArray = $currentuser->allcaps;
    } else {
        $anonymousrole = get_role('anonymous');
        $userArray = $anonymousrole->capabilities;
        if ($atts['join'] === "true") {
            $outputstring .= '<label>Name:</label>'."\n";
            $outputstring .= '<input type="text" name="displayname" id="displayname" >'."\n";
        }
    }
    if (($userArray["join_with_password_room"] == true) && ($atts['join'] === "true")) {
        $outputstring .= '<label>Password:</label>'."\n";
        $outputstring .= '<input type="password" name="roompw" id="roompw" >'."\n";
    }
    return $outputstring;
}

 /**
*   Shortcode output form for the recordings tag.
*
* @param  array  $bbbposts  Information about the post.
* @param  array  $atts The shortcode attributes: type, title, join.
* @param  array $currentuser Details fo the current user
* @param  string $endpointvalue BBB endpoint value
* @param  string $secretvalue BBB secret value
* @return
 */
function bigbluebutton_shortcode_output_recordings($bbbposts, $atts, $currentuser, $endpointvalue, $secretvalue)
{
    $outputstring = '';
    if ($atts['enclosed'] === "true") {
        $outputstring .= '<form id="recording">'."\n";
        $outputstring .= '  <label>'.$atts['title'].'</label>'."\n";
        $outputstring .= '  <div id="bbb-recordings-div" class="bbb-recordings">'."\n";
    }
    $listofallrecordings = array();
    $outputstring .= '    <table  class="stats" cellspacing="5">'."\n";
	  $outputstring .= bigbluebutton_print_recordings_table_headers($currentuser);
    while ($bbbposts->have_posts()) {
        $bbbposts->the_post();
        $roomtoken = get_post_meta($bbbposts->post->ID, '_bbb_room_token', true);
        $meetingID = bigbluebutton_normalize_meeting_id($roomtoken);

        if ($atts['token'] == null||(strpos($atts['token'], $roomtoken) !== false)) {
            if ($meetingID != '') {
                $recordingsarray = BigBlueButton::getRecordingsArray($meetingID, $endpointvalue, $secretvalue);
                if ($recordingsarray['returncode'] == 'SUCCESS' && !$recordingsarray['messageKey']) {
                    $listofrecordings = $recordingsarray['recordings'];
                    $outputstring .= bigbluebutton_print_recordings_data($listofrecordings, $currentuser);
                    array_push($listofallrecordings, $listofrecordings);
                }
            }
        }
    }
    wp_reset_postdata();
    $outputstring .= '    </table>'."\n";
    if ($atts['enclosed'] === "true") {
        $outputstring .= '  </div>'."\n";
        $outputstring .= '</form>'."\n";
    }
    if ((count($listofallrecordings) == 0)) {
        $outputstring = '<p><strong>There are no recordings available.</strong></p>';
		}
    return $outputstring;
}

/**
*   Prints the headers of the recording table.
*
* @param  array $currentuser Details of the current user
* @return
*/
function bigbluebutton_print_recordings_table_headers($currentuser)
{
    $outputstring = '';
    $outputstring .= '      <tr>'."\n";
    $outputstring .= '        <th class="hed" colspan="1">Recording</th>'."\n";
    $outputstring .= '        <th class="hed" colspan="1">Meeting Room Name</th>'."\n";
    $outputstring .= '        <th class="hed" colspan="1">Date</th>'."\n";
    $outputstring .= '        <th class="hed" colspan="1">Duration</th>'."\n";
    if ($currentuser->allcaps["manage_recordings_room"] == true) {
        $outputstring  .= '        <th class="hedextra" colspan="1">Toolbar</th>'."\n";
    }
    $outputstring .= '      </tr>'."\n";
    return $outputstring;
}

/**
*   Prints the recordings data.
*
* @param  array $listofrecordings Recording details
* @param  array $currentuser Details fo the current user
* @return
*/
function bigbluebutton_print_recordings_data($listofrecordings, $currentuser)
{
    $outputstring ='';

    foreach ($listofrecordings as $recording) {
        $type = bigbluebutton_playback_recording_link($recording);
        $duration = bigbluebutton_meeting_duration($recording);
        $formatedstartdate = bigbluebutton_formatted_startdate($recording);
        if ($recording['published'] == 'true' || $currentuser->allcaps["manage_recordings_room"] == true) {
            $outputstring  = '';
            $outputstring .= '      <tr id="actionbar-tr-'.$recording['recordID'].'">'."\n";
            $outputstring .= '        <td>'.$type.'</td>'."\n";
            $outputstring .= '        <td>'.$recording['meetingName'].'</td>'."\n";
            $outputstring .= '        <td>'.$formatedstartdate.'</td>'."\n";
            $outputstring .= '        <td>'.$duration.' min</td>'."\n";

            if ($currentuser->allcaps["manage_recordings_room"] == true) {
                $action = '';
                $class = '';
                if ($recording['published'] == 'true') {
                    $action = 'Show';
                    $class = 'dashicons dashicons-visibility';
                } else {
                    $action = 'Hide';
                    $class = 'dashicons dashicons-hidden';
                }
                $outputstring .= '        <td>'."\n";
                $outputstring .= '          <a id="actionbar-publish-a-'.$recording['recordID'].'" title="'.$action.'" href="#"><span id="actionbar-publish-img-'.$recording['recordID'].'"  class="'.$class.'" onclick="bigbluebutton_action_call(\'publish\', \''.$recording['recordID'].'\'); return false;" /></span></a>'."\n";
                $outputstring .= '          <a id="actionbar-delete-a-'.$recording['recordID'].'" title="Delete" href="#"><span id="actionbar-delete-img-'.$recording['recordID'].'" class="dashicons dashicons-trash" onclick="bigbluebutton_action_call(\'delete\', \''.$recording['recordID'].'\'); return false;" /></span></a>'."\n";
                $outputstring .= '        </td>'."\n";
            }
            $outputstring .= '      </tr>'."\n";
        }
    }
    return $outputstring;
}

/**
*   Sets the link of the recording.
*
* @param  array $recording Recording details
* @return
*/
function bigbluebutton_playback_recording_link($recording)
{
    $type = '';
    foreach ($recording['playbacks'] as $playback) {
        if ($recording['published'] == 'true') {
            $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
        } else {
            $type .= $playback['type'].'&#32;';
        }
    }
    return $type;
}

/**
*   Sets the duration of the recording.
*
* @param  array $recording Recording details
* @return
*/
function bigbluebutton_meeting_duration($recording)
{
    $endtime = isset($recording['endTime']) ? floatval($recording['endTime']) : 0;
    $endtime = $endtime - ($endtime % 1000);
    $starttime = isset($recording['startTime']) ? floatval($recording['startTime']) : 0;
    $starttime = $starttime - ($starttime % 1000);
    return intval(($endtime - $starttime) / 60000);
}


/**
*   Sets the formatted date of the recording.
*
* @param  array $recording Recording details
* @return
*/
function bigbluebutton_formatted_startdate($recording)
{
    if (!is_numeric($recording['startTime'])) {
        $date = new DateTime($recording['startTime']);
        $recording['startTime'] = date_timestamp_get($date);
    } else {
        $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
    }
    return date_i18n('M d Y H:i:s T', $recording['startTime'] + (get_option('gmt_offset') * 60 * 60), false, true);
}


//================================================================================
//-----------------------------Metaboxes-----------------------------------
//================================================================================

/*
 * This adds the 'Room Details' box and 'Room Recordings' box below the main content
* area in a BigBlueButton
 post
*/
function bigbluebutton_meta_boxes()
{
    add_meta_box('room-details', __('Room Details'), 'bigbluebutton_room_details_metabox', 'room', 'normal', 'low');
    add_meta_box('room-recordings', __('Room Recordings'), 'bigbluebutton_list_room_recordings', 'room', 'normal', 'low');
    add_meta_box('room-status', __('Room Status'), 'bigbluebutton_room_status_metabox', 'room', 'normal', 'low');
}

/*
 * Content for the 'Room Details' box
 */
function bigbluebutton_room_details_metabox($post)
{
    wp_nonce_field(basename(__FILE__), 'bbb_rooms_nonce');
    $attendeepassword = get_post_meta($post->ID, '_bbb_attendee_password', true);
    $moderatorpassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
    $bbbwaitadminstart = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
    $isrecorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
    $roomtoken = get_post_meta($post->ID, '_bbb_room_token', true);
    $roomwelcomemessage = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
    $outputstring = '';
    $outputstring .= '    <table class="custom-admin-table">'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Attendee Password</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '                <input type="text" name="bbb_attendee_password" id="bbb_attendee_password" value="'.$attendeepassword.'" />'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Moderator Password</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '                <input type="text" name="bbb_moderator_password"  value="'.$moderatorpassword.'" />'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Wait for Admin to start meeting?</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '               	<input type="radio" name="bbb_must_wait_for_admin_start" id="bbb_must_wait_for_admin_start_yes" value="1"'.(!$bbbwaitadminstart || $bbbwaitadminstart == '1' ? 'checked="checked"' : '').' />'."\n";
    $outputstring .= '                <label for="bbb_must_wait_for_admin_start_yes" >Yes</label>'."\n";
    $outputstring .= '               	<input type="radio" name="bbb_must_wait_for_admin_start" id="bbb_must_wait_for_admin_start_no" value="0"'.($bbbwaitadminstart == '0' ? 'checked="checked"' : '').' />'."\n";
    $outputstring .= '                <label for="bbb_must_wait_for_admin_start_no" >No</label>'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Record meeting?</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '                <input type="radio" name="bbb_is_recorded" id="bbb_is_recorded_yes" value="1"'.(!$isrecorded || $isrecorded == '1' ? 'checked="checked"' : '').'/>'."\n";
    $outputstring .= '                <label for="bbb_is_recorded_yes" >Yes</label>'."\n";
    $outputstring .= '                <input type="radio" name="bbb_is_recorded" id="bbb_is_recorded_no" value="0"'.($isrecorded == '0' ? 'checked="checked"' : '').' />'."\n";
    $outputstring .= '                <label for="bbb_is_recorded_no" >No</label>'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Room Token</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '                <p>The room token is set when the post is saved. This is not editable.</p>'."\n";
    $outputstring .= '                <input type="hidden" name="bbb_room_token" value="'.($roomtoken ? $roomtoken : 'Token Not Set').'" />'."\n";
    $outputstring .= '                <p>Room Token: <strong>'.($roomtoken ? $roomtoken : 'Token Not Set').'</strong></p>'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '        <tr>'."\n";
    $outputstring .= '            <th>Room Welcome Msg</th>'."\n";
    $outputstring .= '            <td>'."\n";
    $outputstring .= '                <textarea name="bbb_room_welcome_msg">'.$roomwelcomemessage.'</textarea>'."\n";
    $outputstring .= '            </td>'."\n";
    $outputstring .= '        </tr>'."\n";
    $outputstring .= '	  </table>'."\n";
    $outputstring .= '	  <input type="hidden" name="bbb-noncename" id="bbb-noncename" value="'.wp_create_nonce('bbb').'" />'."\n";
    echo $outputstring;
}


/*
 * List the specific posts recordings
 * @param  array  $post all the posts available
 */
 function bigbluebutton_list_room_recordings($post)
 {
     $args = array('post_type' => 'room',
                 'orderby' => 'name',
                 'posts_per_page' => -1,
                 'order' => 'ASC',
     );
     $bbbposts = new WP_Query($args);
     $roomtoken = get_post_meta($post->ID, '_bbb_room_token', true);
     $atts = array('token' => $roomtoken);
     $currentuser  = wp_get_current_user();
     $endpointvalue = get_option('bigbluebutton_endpoint');
     $secretvalue = get_option('bigbluebutton_secret');
     bigbluebutton_session_setup($endpointvalue, $secretvalue);
     if (get_post_status($post->ID) == "publish") {
         echo bigbluebutton_shortcode_output_recordings($bbbposts, $atts, $currentuser, $endpointvalue, $secretvalue);
     }
 }

 /**
  * Room Status Metabox.
  */
 function bigbluebutton_room_status_metabox($post)
 {
     $outputstring = '';
     $endpointvalue = get_option('bigbluebutton_endpoint');
     $secretvalue = get_option('bigbluebutton_secret');
     bigbluebutton_session_setup($endpointvalue, $secretvalue);
     $roomtoken = get_post_meta($post->ID, '_bbb_room_token', true);
     $currentuser = wp_get_current_user();
     $meetingid = bigbluebutton_normalize_meeting_id($roomtoken);

     if ($_POST['SubmitList'] == 'End Meeting Now') {
         BigBlueButton::endMeeting(bigbluebutton_normalize_meeting_id($_POST['bbb_room_token']), $_POST['bbb_moderator_password'], $endpointvalue, $secretvalue);
     }
     //if people can register let them option when not signed in
     if (get_post_status($post->ID) === 'publish') {
         $usercapabilitiesarray = bigbluebutton_assign_capabilities_array($currentuser);
         $slug = $post->post_name;
         $outputstring .= '<input type="hidden" name="hiddenInput" id="hiddenInput" value="'.$slug.'" />';
         $outputstring .= '<input type="button" style=" left: 0;padding: 5x 100px;" class="button-primary" value="Join"  onClick="bigbluebutton_join_meeting(\'true\',\''.json_encode(is_user_logged_in()).'\',
       \''.json_encode($usercapabilitiesarray["join_with_password_room"]).'\'); setTimeout(function() {document.location.reload(true);}, 5000);" />';
     }

     if (BigBlueButton::isMeetingRunning($meetingid, $endpointvalue, $secretvalue)) {
         $outputstring .= '<input type="submit" name="SubmitList" style="position: absolute; left: 70px;padding: 5x;" class="button-primary" value="End Meeting Now" />&nbsp';
     }
     echo $outputstring;
 }


 //================================================================================
 //---------------------------------Save data----------------------------------
 //================================================================================

 /**
 * Save Data in Custom Post Type
 */
 function bigbluebutton_save_data()
 {
     $postid = get_the_ID();
     $attendeepassword = get_post_meta($postid, '_bbb_attendee_password', true);
     $moderatorpassword = get_post_meta($postid, '_bbb_moderator_password', true);
     $endpointvalue = get_option('bigbluebutton_endpoint');
     $secretvalue = get_option('bigbluebutton_secret');
     bigbluebutton_session_setup($endpointvalue, $secretvalue);
     $newnonce = wp_create_nonce('bbb');

     if ($_POST['"bbb-noncename'] == $newnonce) {
         return $postid;
     }
     // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
         return $postid;
     }

     if (!current_user_can('edit_rooms', $postid)) {
         return $postid;
     }
     $post = get_post($postid);
     if ($post->post_type == 'room') {
         $token = get_post_meta($postid, '_bbb_room_token', true);
         // Assign a random seed to generate unique ID on a BBB server
         if (!$token) {
             $meetingid = bigbluebutton_generate_token();
             update_post_meta($postid, '_bbb_room_token', $meetingid);
         }

         $attendeepassword = bigbluebutton_generate_password(6, 2);
         $moderatorpassword = bigbluebutton_generate_password(6, 2, $attendeepassword);

         bigbluebutton_set_password($postid, 'bbb_attendee_password', $attendeepassword);
         bigbluebutton_set_password($postid, 'bbb_moderator_password', $moderatorpassword);

         if (($moderatorpassword !== $_POST['bbb_moderator_password']) || ($attendeepassword !== $_POST['bbb_attendee_password'])) {
             BigBlueButton::endMeeting(bigbluebutton_normalize_meeting_id($_POST['bbb_room_token']), $moderatorpassword, $endpointvalue, $secretvalue);
         }

         update_post_meta($postid, '_bbb_must_wait_for_admin_start', esc_attr($_POST['bbb_must_wait_for_admin_start']));
         update_post_meta($postid, '_bbb_is_recorded', esc_attr($_POST['bbb_is_recorded']));
         update_post_meta($postid, '_bbb_room_welcome_msg', esc_attr($_POST['bbb_room_welcome_msg']));
     }

     return $postid;
 }

 /**
 * Setting password.
 */
function bigbluebutton_set_password($postid, $password, $randompassword)
{
    if (empty($_POST[$password]) && (get_post_status($postid) === 'publish')) {
        update_post_meta($postid, '_'.$password, $randompassword); //random generated
    } else {
        update_post_meta($postid, '_'.$password, esc_attr($_POST[$password]));
    }
}

//================================================================================
//------------------------------ Page ----------------------------------
//================================================================================


/*
 * Content filter to add BBB Button
 */
function bigbluebutton_filter($content)
{
    $outputstring = '';
    if (('room' == get_post_type()) && (is_single())) {
        $postid = get_the_ID();
        $post = get_post($postid);
        $slug = $post->post_name;
        $meetingname = get_the_title($post->ID);
        bigbluebutton_session_setup(get_option('bigbluebutton_endpoint'), get_option('bigbluebutton_secret'));
        $currentuser = wp_get_current_user();
        $usercapabilitiesarray = bigbluebutton_assign_capabilities_array($currentuser);
        $outputstring .= '<div id="bbb-join-container"></div>';
        $outputstring .= '<div id="bbb-error-container"></div>';
        $outputstring .= '<form id="room" class="bbb-shortcode">'."\n";
        if (($currentuser->allcaps == [])||$usercapabilitiesarray["join_with_password_room"] == true) {
            $atts['join'] ="true";
        } else {
            $atts['join'] ="false";
        }
        $outputstring .= bigbluebutton_form_setup($currentuser, $atts);
        $outputstring .= '<input type="hidden" name="hiddenInput" id="hiddenInput" value="'.$slug.'" />';
        $outputstring .= '<input class="bbb-shortcode-selector" type="button" onClick="bigbluebutton_join_meeting(\'true\',\''.json_encode(is_user_logged_in()).'\',\''.json_encode($usercapabilitiesarray["join_with_password_room"]).'\')" value="Join  '.$meetingname.'"/>'."\n";
        $outputstring .= '</form>';
    }
    return $content.$outputstring;
}

//================================================================================
//------------------------------- Admin notices ------------------------------
//================================================================================

/*
* This displays any notices that may be stored in $_SESSION
*/
function bigbluebutton_admin_notices()
{
    if (!empty($_SESSION['bigbluebutton_admin_notices'])) {
        echo  $_SESSION['bigbluebutton_admin_notices'];
    }
    unset($_SESSION['bigbluebutton_admin_notices']);
}


/**
 * Error notices.
 */
function bigbluebutton_error_notice()
{
    $screen = get_current_screen();
    if ($screen->id == 'edit-room' && !function_exists('members_get_capabilities')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>For changing the default capabilities for each user is recommended to use an additional plugin as <a href="https://wordpress.org/plugins/members/" target="_blank">Members</a>.</strong></p>';
        echo '</div>';
    }
}



//================================================================================
//------------------------------- Helping functions ------------------------------
//================================================================================

function before_bbb_delete()
{
    /*
     * NOTE: If we want to do anything when the BBB post in wordpress is deleted, we can hook into here.
     */
}


/**
 * Adding default roles.
 */
function bigbluebutton_set_default_roles()
{
    $adminrole = get_role('administrator');
    $adminrole->add_cap('join_as_attendee_room', false);
    $adminrole->add_cap('join_as_moderator_room', true);
    $adminrole->add_cap('join_with_password_room', false);
    $adminrole->add_cap('manage_recordings_room', true);

    $authorrole = get_role('author');
    $authorrole->add_cap('join_as_attendee_room', false);
    $authorrole->add_cap('join_as_moderator_room', true);
    $authorrole->add_cap('join_with_password_room', false);
    $authorrole->add_cap('manage_recordings_room', true);

    $contributorrole = get_role('contributor');
    $contributorrole->add_cap('join_as_attendee_room', false);
    $contributorrole->add_cap('join_as_moderator_room', true);
    $contributorrole->add_cap('join_with_password_room', false);
    $contributorrole->add_cap('manage_recordings_room', true);

    $editorrole = get_role('editor');
    $editorrole->add_cap('join_as_attendee_room', false);
    $editorrole->add_cap('join_as_moderator_room', true);
    $editorrole->add_cap('join_with_password_room', false);
    $editorrole->add_cap('manage_recordings_room', true);

    $subscriberrole = get_role('subscriber');
    $subscriberrole->add_cap('join_as_attendee_room', true);
    $subscriberrole->add_cap('join_as_moderator_room', false);
    $subscriberrole->add_cap('join_with_password_room', false);
    $subscriberrole->add_cap('manage_recordings_room', false);

    add_role('anonymous', 'Anonymous', array());
    $anonymousrole = get_role('anonymous');
    $anonymousrole->add_cap('join_as_attendee_room', true);
    $anonymousrole->add_cap('join_as_moderator_room', false);
    $anonymousrole->add_cap('join_with_password_room', true);
    $anonymousrole->add_cap('manage_recordings_room', false);
}

/*
 * Assignes the correct capabilities array
 */
function bigbluebutton_assign_capabilities_array($currentuser)
{
    if (is_user_logged_in() == true) {
        return $currentuser->allcaps;
    } else {
        $anonymousrole = get_role('anonymous');
        return $anonymousrole->capabilities;
    }
}

/*
 * Generates token
 */
function bigbluebutton_generate_token($tokenlength = 6)
{
    $token = '';
    if (function_exists('openssl_random_pseudo_bytes')) {
        $token .= bin2hex(openssl_random_pseudo_bytes($tokenlength));
    } else {
        //fallback to mt_rand if php < 5.3 or no openssl available
        $characters = '0123456789abcdef';
        $characterslength = strlen($characters) - 1;
        $tokenlength *= 2;
        //select some random characters
        for ($i = 0; $i < $tokenlength; ++$i) {
            $token .= $characters[mt_rand(0, $characterslength)];
        }
    }

    return $token;
}

/**
*   Returning the base url of the plugin
*/
function bigbluebutton_plugin_base_url()
{
    return "".pathinfo(plugins_url(plugin_basename(__FILE__), dirname(__FILE__)))['dirname'];
}

/*
 * Set up SESSIONS array
 */
function bigbluebutton_session_setup($endpointvalue, $secretvalue)
{
    $_SESSION['mt_bbb_endpoint'] = $endpointvalue;
    $_SESSION['mt_bbb_secret'] = $secretvalue;
}

/*
 * Generates random password
 */
function bigbluebutton_generate_password($numAlpha=6, $numNonAlpha=2, $salt='')
{
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-@_;./*?$-!,';

    $pepper = '';
    do {
        $pepper = str_shuffle(substr(str_shuffle($listAlpha), 0, $numAlpha) . substr(str_shuffle($listNonAlpha), 0, $numNonAlpha));
    } while ($pepper == $salt);

    return $pepper;
}

/*
 * Normalizing meeting ID
 */
function bigbluebutton_normalize_meeting_id($meetingid)
{
    return (strlen($meetingid) == 12) ? sha1(home_url().$meetingid) : $meetingid;
}

/*
 * Returns current plugin version.
 */
function bigbluebutton_get_version()
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    $pluginfolder = get_plugins('/'.plugin_basename(dirname(__FILE__)));
    $pluginfile = basename((__FILE__));

    return $pluginfolder[$pluginfile]['Version'];
}
