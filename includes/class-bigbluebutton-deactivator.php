<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      3.0.0
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Deactivator {

	/**
	 * Remove all capabilities associated with this plugin.
	 *
	 * Remove capabilities for creating/editing/viewing rooms and joining as moderator/viewer.
	 *
	 * @since    3.0.0
	 */
	public static function deactivate() {

		$args = get_post_type_object('bbb-room');
		$custom_capabilities = array(
			'view_bbb_room_list',
			'join_as_moderator_bbb_room',
			'join_as_viewer_bbb_room',
			'join_with_access_code_bbb_room'
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

}
