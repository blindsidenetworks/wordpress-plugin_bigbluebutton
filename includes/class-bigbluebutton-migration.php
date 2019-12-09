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
class Bigbluebutton_Migration {

	/**
	 * The old version of the plugin that the user is updating from.
	 *
	 * @since    3.0.0
	 *
	 * @access   private
	 * @var      String    $old_version    Keep track of the previous version of the plugin that was installed.
	 */
	private $old_version;

	/**
	 * The new version of the plugin that the user is updating to.
	 *
	 * @since 3.0.0
	 *
	 * @access   private
	 * @var      String    $new_version    Keep track of the new version of the plugin that is installed.
	 */
	private $new_version;

	/**
	 * The error message that will be displayed if there is an issue with migrating from an old version to the new version.
	 *
	 * @access   private
	 * @var      String    $error_message    Stores the migration error message, if an issue arises.
	 */
	private $error_message = '';

	/**
	 * Constructor for the migration.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $old_version    Old version of the plugin.
	 * @param   String $new_version    New version of the plugin.
	 */
	public function __construct( $old_version, $new_version ) {
		$this->old_version = $old_version;
		$this->new_version = $new_version;
	}

	/**
	 * Migrate from the old version to the new verion.
	 *
	 * @since   3.0.0
	 *
	 * @return  Boolean     $success    Boolean value if the migration suceeded.
	 */
	public function migrate() {
		$success = false;
		$this->import_from_older_versions();
		$success = $this->import_rooms();
		if ( ! $success ) {
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
		$old_rooms_table     = 'wp_bigbluebutton';
		$old_room_logs_table = 'wp_bigbluebutton_logs';

		// Import old rooms to new rooms.
		$old_rooms_query            = $wpdb->prepare( 'SHOW TABLES LIKE %s;', $wpdb->esc_like( $old_rooms_table ) );
		$old_room_logs_query        = $wpdb->prepare( 'SHOW TABLES LIKE %s;', $wpdb->esc_like( $old_room_logs_table ) );
		$old_room_logs_table_exists = ( $wpdb->get_var( $old_room_logs_query ) === $old_room_logs_table );
		if ( $wpdb->get_var( $old_rooms_query ) === $old_rooms_table ) {
			$old_rooms = $wpdb->get_results( 'SELECT * FROM ' . $old_rooms_table );
			// Import old rooms to new rooms.
			foreach ( $old_rooms as $old_room ) {
				$new_room_args = array(
					'post_title' => $old_room->meetingName,
					'post_type'  => 'bbb-room',
				);

				$new_room_id = wp_insert_post( $new_room_args );

				if ( 0 === $new_room_id ) {
					$this->error_message = sprintf( wp_kses( __( 'Failed to import the room, %s.', 'bigbluebutton' ), array() ), $old_room->meetingNamen );
					return false;
				} else {
					wp_publish_post( $new_room_id );
					wp_update_post(
						array(
							'ID'        => $new_room_id,
							'post_name' => wp_unique_post_slug( $old_room->meetingName, $new_room_id, 'publish', 'bbb-room', 0 ),
						)
					);

					// Add room codes to postmeta data.
					$meeting_id = ( 12 == strlen( $old_room->meetingID ) ) ? sha1( home_url() . $old_room->meetingID ) : $old_room->meetingID;
					update_post_meta( $new_room_id, 'bbb-room-moderator-code', $old_room->moderatorPW );
					update_post_meta( $new_room_id, 'bbb-room-viewer-code', $old_room->attendeePW );
					update_post_meta( $new_room_id, 'bbb-room-token', $old_room->meetingID );
					update_post_meta( $new_room_id, 'bbb-room-meeting-id', $meeting_id );
					if ( $old_room_logs_table_exists ) {
						$wpdb->delete( $old_room_logs_table, array( 'meetingID' => $meeting_id ) );
					}

					// Update room recordable value.
					update_post_meta( $new_room_id, 'bbb-room-recordable', ( $old_room->recorded ? 'true' : 'false' ) );
					update_post_meta( $new_room_id, 'bbb-room-wait-for-moderator', ( $old_room->waitForModerator ? 'true' : 'false' ) );

					// Delete room from old table.
					$wpdb->delete( $old_rooms_table, array( 'id' => $old_room->id ) );
				}
			}
			$check_old_rooms = $wpdb->get_results( 'SELECT * FROM ' . $old_rooms_table );
			if ( count( $check_old_rooms ) > 0 ) {
				$this->error_message = __( 'Not all rooms were able to be imported to the new version.', 'bigbluebutton' );
				return false;
			} else {
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $old_rooms_table );
			}
			$check_room_logs = $wpdb->get_results( 'SELECT * FROM ' . $old_room_logs_table );
			if ( count( $check_room_logs ) > 0 ) {
				$this->error_message = __( 'Not all room logs were able to be imported to the new version.' );
				return false;
			} else {
				// Delete old log table.
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $old_room_logs_table );
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
		$old_permissions = get_option( 'bigbluebutton_permissions' );
		if ( false === $old_permissions ) {
			delete_option( 'bigbluebutton_permissions' );
			return;
		}
		foreach ( $old_permissions as $old_role_name => $old_role ) {
			$role = get_role( $old_role_name );
			if ( null === $role ) {
				continue;
			}
			if ( isset( $old_role['manageRecordings'] ) && $old_role['manageRecordings'] ) {
				$role->add_cap( 'manage_bbb_room_recordings' );
				$role->add_cap( 'view_extended_bbb_room_recording_formats' );
			}
			if ( ! isset( $old_role['participate'] ) ) {
				continue;
			}
			if ( $old_role['participate'] ) {
				$this->set_role_participation( $role, $old_role['defaultRole'] );
			}
			if ( ! $old_role['participate'] ) {
				$role->remove_cap( 'join_as_moderator_bbb_room' );
				$role->remove_cap( 'join_as_viewer_bbb_room' );
				$role->remove_cap( 'join_with_access_code_bbb_room' );
			}
		}
		delete_option( 'bigbluebutton_permissions' );
	}

	/**
	 * Set WordPress core role according to old role.
	 *
	 * @since   3.0.0
	 *
	 * @param   Role   $role       Role object to assign join room permissions to.
	 * @param   String $role_name  Role name for old format.
	 * @return void
	 */
	private function set_role_participation( $role, $role_name ) {
		switch ( $role_name ) {
			case 'moderator':
				$role->add_cap( 'join_as_moderator_bbb_room' );
				$role->remove_cap( 'join_as_viewer_bbb_room' );
				$role->remove_cap( 'join_with_access_code_bbb_room' );
				break;
			case 'attendee':
				$role->add_cap( 'join_as_viewer_bbb_room' );
				$role->remove_cap( 'join_as_moderator_bbb_room' );
				$role->remove_cap( 'join_with_access_code_bbb_room' );
				break;
			case 'none':
				$role->add_cap( 'join_with_access_code_bbb_room' );
				$role->remove_cap( 'join_as_moderator_bbb_room' );
				$role->remove_cap( 'join_as_viewer_bbb_room' );
				break;
		}
	}
	/**
	 * Get the error message if migration script is not successful.
	 *
	 * @since   3.0.0
	 */
	public function get_error() {
		return $this->error_message;
	}

	/**
	 * Import from older versions to 1.4.6 before updating to 3.0.0
	 *
	 * @since   1.4.6
	 */
	public function import_from_older_versions() {
		$this->import_from_older_versions_rooms();
		$this->import_from_older_versions_permissions();

		// Set bigbluebutton_plugin_version value.
		update_option( 'bigbluebutton_plugin_version', '1.4.6' );

	}

	/**
	 * Import rooms from older versions to 1.4.6 before updating to 3.0.0
	 *
	 * @since   1.4.6
	 */
	public function import_from_older_versions_rooms() {
		global $wpdb;

		// Sets the name of the table.
		$table_name = $wpdb->prefix . 'bigbluebutton';

		// Updates for version 1.3.1 and earlier.
		$bigbluebutton_plugin_version_installed = get_option( 'bigbluebutton_plugin_version' );
		if ( false === $bigbluebutton_plugin_version_installed || ( strcmp( '1.3.1', $bigbluebutton_plugin_version_installed ) <= 0 && get_option( 'bbb_db_version' ) ) ) {

			// Initialize database will create the tables added for version 1.4.6.
			$this->bigbluebutton_init_old_database();
			// Transfer the data from old table to the new one.
			$table_name_old   = $wpdb->prefix . 'bbb_meetingRooms';
			$list_of_meetings = $wpdb->get_results( 'SELECT * FROM ' . $table_name_old . ' ORDER BY id;' );
			foreach ( $list_of_meetings as $meeting ) {
				$sql = 'INSERT INTO ' . $table_name . ' (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW) VALUES ( %s, %s, %s, %s, %s);';
				$wpdb->query( $wpdb->prepare( $sql, Bigbluebutton_Admin_Helper::generate_random_code( 6 ), $meeting->meetingID, $meeting->meetingVersion, $meeting->attendeePW, $meeting->moderatorPW ) );
			}
			// Remove the old table.
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name_old );

			// Update settings.
			if ( ! get_option( 'mt_bbb_url' ) ) {
				update_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' );
			} else {
				update_option( 'bigbluebutton_url', get_option( 'mt_bbb_url' ) );
				delete_option( 'mt_bbb_url' );
			}

			if ( ! get_option( 'mt_salt' ) ) {
				update_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' );
			} else {
				update_option( 'bigbluebutton_salt', get_option( 'mt_salt' ) );
				delete_option( 'mt_salt' );
			}

			delete_option( 'mt_waitForModerator' );
			delete_option( 'bbb_db_version' );
		}
	}

	/**
	 * Import permissions from older versions to 1.4.6 before updating to 3.0.0
	 *
	 * @since   1.4.6
	 */
	public function import_from_older_versions_permissions() {
		global $wp_roles;
		// Load roles if not set.
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$bigbluebutton_plugin_version_installed = get_option( 'bigbluebutton_plugin_version' );
		// Set the new permission schema.
		if ( $bigbluebutton_plugin_version_installed && strcmp( $bigbluebutton_plugin_version_installed, '1.3.3' ) < 0 ) {
			$roles              = $wp_roles->role_names;
			$roles['anonymous'] = 'Anonymous';
			$permissions        = [];
			if ( get_option( 'bigbluebutton_permissions' ) ) {
				$old_permissions = get_option( 'bigbluebutton_permissions' );
				foreach ( $roles as $key => $value ) {
					if ( ! isset( $old_permissions[ $key ]['participate'] ) ) {
						$permissions[ $key ]['participate'] = true;
						if ( 'Administrator' == $value ) {
							$permissions[ $key ]['manageRecordings'] = true;
							$permissions[ $key ]['defaultRole']      = 'moderator';
						} elseif ( 'Anonymous' == $value ) {
							$permissions[ $key ]['manageRecordings'] = false;
							$permissions[ $key ]['defaultRole']      = 'none';
						} else {
							$permissions[ $key ]['manageRecordings'] = false;
							$permissions[ $key ]['defaultRole']      = 'attendee';
						}
					} else {
						$permissions[ $key ] = $old_permissions[ $key ];
					}
				}
				update_option( 'bigbluebutton_permissions', $permissions );
			}
		}
	}

	/**
	 * Initialize 1.4.6 database before moving to new version
	 *
	 * @since   1.4.6
	 */
	public function bigbluebutton_init_old_database() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		// Sets the name of the table.
		$table_name      = $wpdb->prefix . 'bigbluebutton';
		$table_logs_name = $wpdb->prefix . 'bigbluebutton_logs';

		// Execute SQL.
		$sql = 'CREATE TABLE ' . $table_name . ' (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        meetingID text NOT NULL,
        meetingName text NOT NULL,
        meetingVersion int NOT NULL,
        attendeePW text NOT NULL,
        moderatorPW text NOT NULL,
        waitForModerator BOOLEAN NOT NULL DEFAULT FALSE,
        recorded BOOLEAN NOT NULL DEFAULT FALSE,
        UNIQUE KEY id (id)
        );';
		dbDelta( $sql );

		$sql = 'INSERT INTO ' . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW)
        VALUES ('" . Bigbluebutton_Admin_Helper::generate_random_code( 6 ) . "','Demo meeting', '" . time() . "', 'ap', 'mp');";
		dbDelta( $sql );

		$sql = 'INSERT INTO ' . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW, recorded)
        VALUES ('" . Bigbluebutton_Admin_Helper::generate_random_code( 6 ) . "','Demo meeting (recorded)', '" . time() . "', 'ap', 'mp', TRUE);";
		dbDelta( $sql );

		$sql = 'CREATE TABLE ' . $table_logs_name . ' (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        meetingID text NOT NULL,
        recorded BOOLEAN NOT NULL DEFAULT FALSE,
        timestamp int NOT NULL,
        event text NOT NULL,
        UNIQUE KEY id (id)
        );';
		dbDelta( $sql );
	}
}
