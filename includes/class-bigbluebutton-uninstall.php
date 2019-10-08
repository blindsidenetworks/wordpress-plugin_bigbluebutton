<?php

class Bigbluebutton_Uninstall {

    /**
	 * Remove all capabilities associated with this plugin.
	 *
	 * Remove capabilities for creating/editing/viewing rooms and joining as moderator/viewer.
	 *
	 * @since    3.0.0
	 */
    public static function uninstall() {
        if ( ! defined('WP_UNINSTALL_PLUGIN')) {
            exit;
        }
        
        self::trash_rooms_and_categories();
        self::delete_capabilities();
        self::remove_options();
    }

    private static function trash_rooms_and_categories() {
        global $wpdb;
        $wpdb->query('DELETE FROM wp_postmeta WHERE post_id in (SELECT id from wp_posts where post_type="bbb-room";');
        $wpdb->query('DELETE FROM wp_term_relationships WHERE term_taxonomy_id in (SELECT term_taxonomy_id from wp_term_taxonomy WHERE taxonomy="bbb-room-category")');
        $wpdb->query('DELETE FROM wp_posts WHERE post_type="bbb-room";');
        $wpdb->query('DELETE FROM wp_term_relationships WHERE taxonomy="bbb-room-category"');
    }

    private static function delete_capabilities() {
        $args = get_post_type_object('bbb-room');
		$custom_capabilities = array(
			'view_bbb_room_list',
			'join_as_moderator_bbb_room',
			'join_as_viewer_bbb_room',
			'join_with_access_code_bbb_room',
			'create_recordable_bbb_room',
			'manage_bbb_room_recordings',
			'view_extended_bbb_room_recording_formats'
		);

		if (property_exists($args, 'capabilities')) {
			$arg_capabilities = $args->capabilities;
			$args->capabilities = array_merge($arg_capabilities, $custom_capabilities);
		} else {
			$args->capabilities = $custom_capabilities;
		}
		

		$capabilities = get_object_vars(get_post_type_capabilities($args));
		$roles = get_editable_roles();
		$role_names = array_keys($roles);

		foreach ($role_names as $name) {
			$role = get_role($name);

			foreach ($capabilities as $cap) {
				if (strpos($cap, 'bbb_room') === false) {
					continue;
				}
				if ($role->has_cap($cap)) {
					$role->remove_cap($cap);
				}
			}
			
        }
    }

    private static function remove_options() {
        delete_option('bigbluebutton_url');
        delete_option('bigbluebutton_salt');
        delete_option('bigbluebutton_plugin_version');
    }
}