<?php
/**
 * Handle the majority of Bigbluebutton API calls to admin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/admin
 */

/**
 * Handle the majority of Bigbluebutton API calls to admin.
 *
 * Handles saving rooms as custom post type, with custom fields.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/admin
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Admin_Api {

	/**
	 * Save custom post meta to the room.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $post_id    Post ID of the new room.
	 * @return  Integer $post_id    Post ID of the new room.
	 */
	public function save_room( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( $this->can_save_room() ) {
			$moderator_code = sanitize_text_field( $_POST['bbb-moderator-code'] );
			$viewer_code    = sanitize_text_field( $_POST['bbb-viewer-code'] );
			$recordable     = ( array_key_exists( 'bbb-room-recordable', $_POST ) && sanitize_text_field( $_POST['bbb-room-recordable'] ) == 'checked' );

			$wait_for_mod = ( isset( $_POST['bbb-room-wait-for-moderator'] ) && sanitize_text_field( $_POST['bbb-room-wait-for-moderator'] ) == 'checked' );

			// Ensure neither code is empty.
			if ( '' == $moderator_code ) {
				$moderator_code = Bigbluebutton_Admin_Helper::generate_random_code();
			}
			if ( '' == $viewer_code ) {
				$viewer_code = Bigbluebutton_Admin_Helper::generate_random_code();
			}

			// Ensure the moderator code is not the same as the viewer code.
			if ( $moderator_code === $viewer_code ) {
				$viewer_code = $moderator_code . Bigbluebutton_Admin_Helper::generate_random_code( 1 );
			}

			// Add room codes to postmeta data.
			update_post_meta( $post_id, 'bbb-room-moderator-code', $moderator_code );
			update_post_meta( $post_id, 'bbb-room-viewer-code', $viewer_code );

			if ( ! get_post_meta( $post_id, 'bbb-room-meeting-id', true ) ) {
				update_post_meta( $post_id, 'bbb-room-meeting-id', sha1( home_url() . Bigbluebutton_Admin_Helper::generate_random_code( 12 ) ) );
			}

			// Update room recordable value.
			update_post_meta( $post_id, 'bbb-room-recordable', ( $recordable ? 'true' : 'false' ) );
			update_post_meta( $post_id, 'bbb-room-wait-for-moderator', ( $wait_for_mod ? 'true' : 'false' ) );

		} else {
			return $post_id;
		}
	}

	/**
	 * Dismiss admin notices.
	 *
	 * @since 3.0.0
	 */
	public function dismiss_admin_notices() {
		if ( isset( $_POST['type'] ) && 'bbb-' === substr( $_POST['type'], 0, 4 ) ) {
			$type = sanitize_text_field( $_POST['type'] );
			if ( wp_verify_nonce( $_POST['nonce'], $type ) ) {
				update_option( 'dismissed-' . $type, true );
			}
		}
	}

	/**
	 * Helper function to check if metadata has been submitted with correct nonces.
	 *
	 * @since 3.0.0
	 */
	private function can_save_room() {
		return ( isset( $_POST['bbb-moderator-code'] ) &&
			isset( $_POST['bbb-viewer-code'] ) &&
			isset( $_POST['bbb-room-moderator-code-nonce'] ) &&
			wp_verify_nonce( $_POST['bbb-room-moderator-code-nonce'], 'bbb-room-moderator-code-nonce' ) &&
			isset( $_POST['bbb-room-viewer-code-nonce'] ) &&
			wp_verify_nonce( $_POST['bbb-room-viewer-code-nonce'], 'bbb-room-viewer-code-nonce' ) &&
			isset( $_POST['bbb-room-wait-for-moderator-nonce'] ) &&
			wp_verify_nonce( $_POST['bbb-room-wait-for-moderator-nonce'], 'bbb-room-wait-for-moderator-nonce' ) &&
			( ! current_user_can( 'create_recordable_bbb_room' ) ||
				( isset( $_POST['bbb-room-recordable-nonce'] ) &&
				wp_verify_nonce( $_POST['bbb-room-recordable-nonce'], 'bbb-room-recordable-nonce' ) ) )
			);
	}
}
