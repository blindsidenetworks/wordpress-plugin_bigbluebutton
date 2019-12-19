<?php
/**
 * Fired during plugin activation
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      3.0.0
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Activator {

	/**
	 * Set default capabilities for roles.
	 *
	 * By default, only administrators and authors can create, edit, and delete rooms.
	 * Only administrators and owners can enter a meeting as a moderator. Everyone else enters as a viewer.
	 *
	 * @since    3.0.0
	 */
	public static function activate() {
		self::set_default_roles();
	}

	/**
	 * Set default capabilities for rooms.
	 *
	 * Recurse through each role and assign default capabilities involving rooms, how to enter rooms, and recordings.
	 *
	 * @since   3.0.0
	 */
	public static function set_default_roles() {
		$are_defaults_set = get_option( 'bigbluebutton_default_roles_set' );
		if ( true === $are_defaults_set ) {
			return;
		}
		if ( get_role( 'anonymous' ) === null ) {
			add_role(
				'anonymous', __( 'Anonymous' ), array(
					'read' => true,
				)
			);
		}
		$roles      = get_editable_roles();
		$role_names = array_keys( $roles );

		self::set_default_capabilities_for_each_role( $role_names );
		update_option( 'bigbluebutton_default_roles_set', true );
	}

	/**
	 * Loop through each role and set default capabilities.
	 *
	 * @since  3.0.0
	 *
	 * @param  Array $role_names     List of role names.
	 */
	private static function set_default_capabilities_for_each_role( $role_names ) {
		foreach ( $role_names as $name ) {
			self::set_default_capability_for_one_role( $name );
		}
	}

	/**
	 * Set default capability for one role.
	 *
	 * @since  3.0.0
	 *
	 * @param  String $name    Role name to set capability for.
	 */
	private static function set_default_capability_for_one_role( $name ) {
		$role         = get_role( $name );
		$set_join_cap = self::join_permissions_set( $role );
		$role->add_cap( 'read_bbb_room' );

		if ( $role->has_cap( 'activate_plugins' ) ) {
			self::set_admin_capability( $role );
		}

		if ( 'administrator' == $name ) {
			self::set_admin_capability( $role );
			if ( ! $set_join_cap ) {
				$role->add_cap( 'join_as_moderator_bbb_room' );
			}
			return;
		}
		if ( 'author' == $name ) {
			self::set_edit_room_capability( $role );
		}
		if ( 'author' == $name || 'editor' == $name || 'contributer' == $name || 'subscriber' == $name ) {
			if ( ! $set_join_cap ) {
				$role->add_cap( 'join_as_viewer_bbb_room' );
			}
			return;
		}
		if ( ! $set_join_cap ) {
			$role->add_cap( 'join_with_access_code_bbb_room' );
		}
	}

	/**
	 * Set default capability for admin.
	 *
	 * @since  3.0.0
	 *
	 * @param  Object $role The role object to set capabilties for.
	 */
	private static function set_admin_capability( $role ) {
		self::set_edit_room_capability( $role );
		$role->add_cap( 'edit_others_bbb_rooms' );
		$role->add_cap( 'delete_others_bbb_rooms' );
		$role->add_cap( 'create_recordable_bbb_room' );
		$role->add_cap( 'manage_bbb_room_recordings' );
		$role->add_cap( 'view_extended_bbb_room_recording_formats' );
	}

	/**
	 * Set admin's extensive capabilities.
	 *
	 * @since  3.0.0
	 *
	 * @param  Role $role The role object to set capabilties for.
	 */
	private static function set_edit_room_capability( $role ) {
		$role->add_cap( 'edit_bbb_rooms' );
		$role->add_cap( 'edit_published_bbb_rooms' );
		$role->add_cap( 'delete_bbb_rooms' );
		$role->add_cap( 'delete_published_bbb_rooms' );
		$role->add_cap( 'publish_bbb_rooms' );
		if ( ! $role->has_cap( 'manage_categories' ) ) {
			$role->add_cap( 'manage_categories' );
		}
	}

	/**
	 * Check if the role already has join room permissions set, from migration.
	 *
	 * @param  Object $role       The role object to check join room permissions.
	 * @return Boolean true|false  The boolean value of whether the role already has join room permissions set.
	 */
	private static function join_permissions_set( $role ) {
		if ( $role->has_cap( 'join_as_moderator_bbb_room' ) || $role->has_cap( 'join_as_viewer_bbb_room' ) || $role->has_cap( 'join_with_access_code_bbb_room' ) ) {
			return true;
		}
		return false;
	}
}
