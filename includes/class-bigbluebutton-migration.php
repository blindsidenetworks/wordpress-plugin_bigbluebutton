<?php

/**
 * Fired during plugin migration
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 */

/**
 * Fired during plugin migration.
 *
 * This class defines all code necessary to run to upgrade the plugin from an older version to the newest version.
 *
 * @since      3.0.0
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class BigbluebuttonMigration {

    private $old_version;
    private $new_version;
    private $error_message = "";

    /**
     * Constructor for the migration class.
     * 
     * @since   3.0.0
     */
    public function __construct($old_version, $new_version) {
        $this->old_version = $old_version;
        $this->new_version = $new_version;
    }

    /**
     * Migrate from the old version to the new version.
     * 
     * @since   3.0.0
     */
    public function migrate() {
        $success = true;        
        $success = $this->import_rooms();
        if (!$success) {
            return $success;
        }
        $this->import_permissions();
        return $success;
    }

    /**
     * Import rooms from the old table to the new tables.
     * 
     * @since   3.0.0
     */
    private function import_rooms() {
        global $wpdb;
        $old_rooms_table = 'wp_bigbluebutton';
        $old_room_logs_table = 'wp_bigbluebutton_logs';

        // import old rooms to new rooms
        $old_rooms_query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($old_rooms_table));
        $old_room_logs_query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($old_room_logs_table));
        $old_room_logs_table_exists = ($wpdb->get_var($old_room_logs_query) === $old_room_logs_table);
        if ($wpdb->get_var($old_rooms_query) === $old_rooms_table) {
            $old_rooms = $wpdb->get_results("SELECT * FROM " . $old_rooms_table . ";");
            // import old rooms to new rooms
            foreach($old_rooms as $old_room) {
                $new_room_args = array(
                    'post_title' => $old_room->meetingName,
                    'post_type' => 'bbb-room',
                );

                $new_room_id = wp_insert_post($new_room_args);

                if ($new_room_id == 0) {
                    $this->error_message = "Failed to import the room, " . $old_room->meetingName . ".";
                    return false;
                } else {
                    wp_publish_post($new_room_id);
                    wp_update_post(array(
                        'ID' => $new_room_id,
                        'post_name' => wp_unique_post_slug($old_room->meetingName, $new_room_id, 'publish', 'bbb-room', 0)
                    ));

                    // add room codes to postmeta data
                    $meeting_id = (strlen($old_room->meetingID) == 12)? sha1(home_url().$old_room->meetingID): $old_room->meetingID;
                    update_post_meta($new_room_id, 'bbb-room-moderator-code', $old_room->moderatorPW);
                    update_post_meta($new_room_id, 'bbb-room-viewer-code', $old_room->attendeePW);
                    update_post_meta($new_room_id, 'bbb-room-token', $meeting_id);
                    if ($old_room_logs_table_exists) {
                        $wpdb->delete($old_room_logs_table, array('meetingID' => $meeting_id));
                    }

                    // update room recordable value
                    update_post_meta($new_room_id, 'bbb-room-recordable', ($old_room->recorded ? 'true' : 'false'));
                    update_post_meta($new_room_id, 'bbb-room-wait-for-moderator', ($old_room->waitForModerator ? 'true' : 'false'));
                    
                    // delete room from old table
                    $wpdb->delete($old_rooms_table, array('id' => $old_room->id));
                }
            }
            $check_old_rooms = $wpdb->get_results("SELECT * FROM " . $old_rooms_table . ";");
            if (count($check_old_rooms) > 0) {
                $this->error_message = "Not all rooms were able to be imported to the new version.";
                return false;
            } else {
                $wpdb->query("DROP TABLE IF EXISTS " . $old_rooms_table);
            }
            $check_room_logs = $wpdb->get_results("SELECT * FROM " . $old_room_logs_table . ";");
            if (count($check_room_logs)) {
                $this->error_message = "Not all room logs were able to be imported to the new version.";
                return false;
            } else {
                // delete old log table
                $wpdb->query("DROP TABLE IF EXISTS " . $old_room_logs_table);
            }
        }
        
        return true;
    }

    /**
     * Import old capabilities associated with roles to the new way of handling them.
     * 
     * @since   3.0.0
     */
    private function import_permissions() {
        $old_permissions = get_option('bigbluebutton_permissions');
        if ($old_permissions !== false) {
            foreach($old_permissions as $old_role_name => $old_role) {
                $role = get_role($old_role_name);
                if ($role === NULL) {
                    continue;
                }
                if (isset($old_role['participate']) && $old_role['participate']) {
                    switch($old_role['defaultRole']) {
                        case 'moderator':
                            $role->add_cap('join_as_moderator_bbb_room');
                            if ($role->has_cap('join_as_viewer_bbb_room')) {
                                $role->remove_cap('join_as_viewer_bbb_room');
                            }
                            if ($role->has_cap('join_with_access_code_bbb_room')) {
                                $role->remove_cap('join_with_access_code_bbb_room');
                            }
                            break;
                        case 'attendee':
                            $role->add_cap('join_as_viewer_bbb_room');
                            if ($role->has_cap('join_as_moderator_bbb_room')) {
                                $role->remove_cap('join_as_moderator_bbb_room');
                            }
                            if ($role->has_cap('join_with_access_code_bbb_room')) {
                                $role->remove_cap('join_with_access_code_bbb_room');
                            }
                            break;
                        case 'none':
                            $role->add_cap('join_with_access_code_bbb_room');
                            if ($role->has_cap('join_as_moderator_bbb_room')) {
                                $role->remove_cap('join_as_moderator_bbb_room');
                            }
                            if ($role->has_cap('join_as_viewer_bbb_room')) {
                                $role->remove_cap('join_as_viewer_bbb_room');
                            }
                            break;
                    }
                } else if (isset($old_role['participate']) && !$old_role['participate']) {
                    if ($role->has_cap('join_as_moderator_bbb_room')) {
                        $role->remove_cap('join_as_moderator_bbb_room');
                    }
                    if ($role->has_cap('join_as_viewer_bbb_room')) {
                        $role->remove_cap('join_as_viewer_bbb_room');
                    }
                    if ($role->has_cap('join_with_access_code_bbb_room')) {
                        $role->remove_cap('join_with_access_code_bbb_room');
                    }
                }
                if (isset($old_role['manageRecordings']) && $old_role['manageRecordings']) {
                    $role->add_cap('manage_bbb_room_recordings');
                    $role->add_cap('view_extended_bbb_room_recording_formats');
                }
            }
        }
        delete_option('bigbluebutton_permissions');
    }

    /**
     * Get the error message if migration script is not successful.
     */
    public function get_error() {
        return $this->error_message;
    }
}