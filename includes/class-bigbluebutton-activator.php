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

		foreach ( $role_names as $name ) {
			$role = get_role( $name );

			switch ( $name ) {
				case 'administrator':
					$role->add_cap( 'edit_bbb_rooms' );
					$role->add_cap( 'edit_others_bbb_rooms' );
					$role->add_cap( 'edit_published_bbb_rooms' );
					$role->add_cap( 'delete_bbb_rooms' );
					$role->add_cap( 'delete_others_bbb_rooms' );
					$role->add_cap( 'delete_published_bbb_rooms' );
					$role->add_cap( 'publish_bbb_rooms' );
					$role->add_cap( 'view_bbb_room_list' );
					$role->add_cap( 'join_as_moderator_bbb_room' );
					$role->add_cap( 'create_recordable_bbb_room' );
					$role->add_cap( 'manage_bbb_room_recordings' );
					$role->add_cap( 'view_extended_bbb_room_recording_formats' );
					break;
				case 'editor':
					$role->add_cap( 'read_bbb_room' );
					$role->add_cap( 'join_as_viewer_bbb_room' );
					break;
				case 'author':
					$role->add_cap( 'edit_bbb_rooms' );
					$role->add_cap( 'edit_published_bbb_rooms' );
					$role->add_cap( 'delete_bbb_rooms' );
					$role->add_cap( 'delete_published_bbb_rooms' );
					$role->add_cap( 'publish_bbb_rooms' );
					$role->add_cap( 'view_bbb_room_list' );
					$role->add_cap( 'join_as_viewer_bbb_room' );
					if ( ! $role->has_cap( 'manage_categories' ) ) {
						$role->add_cap( 'manage_categories' );
					}
					break;
				case 'contributor':
					$role->add_cap( 'read_bbb_room' );
					$role->add_cap( 'join_as_viewer_bbb_room' );
					break;
				case 'subscriber':
					$role->add_cap( 'read_bbb_room' );
					$role->add_cap( 'join_as_viewer_bbb_room' );
					break;
				case 'anonymous':
					$role->add_cap( 'read_bbb_room' );
					$role->add_cap( 'join_with_access_code_bbb_room' );
					break;
				default:
					$role->add_cap( 'read_bbb_room' );
					break;
			}
		}
		update_option( 'bigbluebutton_default_roles_set', true );
	}
}
