<?php

/**
 * The public-facing recordings API of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 */

/**
 * The public-facing recordings API of the plugin.
 *
 * Answers the API calls made from public facing pages about recordings.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Public_Recording_Api {

	/**
	 * Handle publishing/unpublishing a recording
	 *
	 * @since   3.0.0
	 *
	 * @return  String  $response   JSON response to changing a recording's publication status.
	 */
	public function set_bbb_recording_publish_state() {
		$response            = array();
		$response['success'] = false;
		if ( BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' ) ) {
			if ( array_key_exists( 'meta_nonce', $_POST ) && array_key_exists( 'record_id', $_POST ) &&
				array_key_exists( 'value', $_POST ) &&
				( sanitize_text_field( $_POST['value'] ) == 'true' || sanitize_text_field( $_POST['value'] ) == 'false' ) &&
				wp_verify_nonce( $_POST['meta_nonce'], 'bbb_manage_recordings_nonce' ) ) {

				$record_id   = sanitize_text_field( $_POST['record_id'] );
				$value       = sanitize_text_field( $_POST['value'] );
				$return_code = Bigbluebutton_Api::set_recording_publish_state( $record_id, $value );

				if ( $return_code == 200 ) {
					$response['success'] = true;
				}
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Handle protect/unprotect a recording
	 *
	 * @since   3.0.0
	 *
	 * @return  String  $response   JSON response to changing a recording's protection status.
	 */
	public function set_bbb_recording_protect_state() {
		$response            = array();
		$response['success'] = false;
		if ( BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' ) ) {
			if ( array_key_exists( 'meta_nonce', $_POST ) && array_key_exists( 'record_id', $_POST ) &&
				array_key_exists( 'value', $_POST ) &&
				( sanitize_text_field( $_POST['value'] ) == 'true' || sanitize_text_field( $_POST['value'] ) == 'false' ) &&
				wp_verify_nonce( $_POST['meta_nonce'], 'bbb_manage_recordings_nonce' ) ) {

				$record_id   = sanitize_text_field( $_POST['record_id'] );
				$value       = sanitize_text_field( $_POST['value'] );
				$return_code = Bigbluebutton_Api::set_recording_protect_state( $record_id, $value );

				if ( $return_code == 200 ) {
					$response['success'] = true;
				}
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Handle deleting a recording.
	 *
	 * @since   3.0.0
	 *
	 * @return  String  $response   JSON response to deleting a recording.
	 */
	public function trash_bbb_recording() {
		$response            = array();
		$response['success'] = false;
		if ( BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' ) ) {
			if ( array_key_exists( 'meta_nonce', $_POST ) && array_key_exists( 'record_id', $_POST ) &&
				wp_verify_nonce( $_POST['meta_nonce'], 'bbb_manage_recordings_nonce' ) ) {

				$record_id   = sanitize_text_field( $_POST['record_id'] );
				$return_code = Bigbluebutton_Api::delete_recording( $record_id );

				if ( $return_code == 200 ) {
					$response['success'] = true;
				}
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Send recording metadata to Bigbluebutton API.
	 *
	 * @since   3.0.0
	 *
	 * @return  String  $response   JSON response to editing a recording's metadata.
	 */
	public function set_bbb_recording_edits() {
		$response            = array();
		$response['success'] = false;

		if ( BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' ) ) {
			if ( array_key_exists( 'meta_nonce', $_POST ) &&
				array_key_exists( 'record_id', $_POST ) &&
				array_key_exists( 'type', $_POST ) &&
				array_key_exists( 'value', $_POST ) &&
				wp_verify_nonce( $_POST['meta_nonce'], 'bbb_manage_recordings_nonce' )
				) {

				$record_id = sanitize_text_field( $_POST['record_id'] );
				$type      = sanitize_text_field( $_POST['type'] );
				$value     = wp_unslash( sanitize_text_field( $_POST['value'] ) );

				$return_code = Bigbluebutton_Api::set_recording_edits( $record_id, $type, $value );

				if ( $return_code == 200 ) {
					$response['success'] = true;
				}
			}
		}
		wp_send_json( $response );
	}
}
