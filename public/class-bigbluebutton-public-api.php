<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Answers the API calls made from public facing pages.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Public_Api {
    /**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

    }
    
    /**
	 * Handle authenticated user joining room.
	 * 
	 * @since 	3.0.0
	 */
	public function bbb_user_join_room() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'join_room') {
			if (wp_verify_nonce($_POST['bbb_join_room_meta_nonce'], 'bbb_join_room_meta_nonce')) {
				$room_id = $_POST['room_id'];
				$user = wp_get_current_user();
				$entry_code = '';
				$username = $this->get_meeting_username($user);
				$moderator_code = strval(get_post_meta($room_id, 'bbb-room-moderator-code', true));
				$viewer_code = strval(get_post_meta($room_id, 'bbb-room-viewer-code', true));

				if (current_user_can('join_as_moderator_bbb_room') || $user->ID == get_post($room_id)->post_author) {
					$entry_code = $moderator_code;
				} else if (current_user_can('join_as_viewer_bbb_room')) {
					$entry_code = $viewer_code;
				} else if (current_user_can('join_with_access_code_bbb_room') && isset($_POST['bbb_meeting_access_code'])) {
					$entry_code = sanitize_text_field($_POST['bbb_meeting_access_code']);
					if ($entry_code != $moderator_code && $entry_code != $viewer_code) {
						wp_redirect(esc_url(add_query_arg('password_error', '1', get_post_permalink($room_id))));
						return;
					}
				} else {
					wp_die(_('You do not have permission to enter the room. Please request permission.', 'bigbluebutton'));
				}
				$join_url = BigbluebuttonAPI::get_join_meeting_url($room_id, $username, $entry_code);
				wp_redirect($join_url);
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
    }

    /**
	 * Handle publishing/unpublishing a recording
	 * 
	 * @since	3.0.0
	 * 
	 * @return	String	$response	JSON response to changing a recording's publication status.
	 */
	public function set_bbb_recording_publish_state() {
		$response = array();
		$response['success'] = false;
		if (current_user_can('manage_bbb_room_recordings')) {
			if (array_key_exists('meta_nonce', $_POST) && array_key_exists('record_id', $_POST) && 
				array_key_exists('value', $_POST) && 
				(sanitize_text_field($_POST['value']) == 'true' || sanitize_text_field($_POST['value']) == 'false') &&
				wp_verify_nonce($_POST['meta_nonce'], 'bbb_manage_recordings_nonce')) {

				$record_id = sanitize_text_field($_POST['record_id']);
				$value = sanitize_text_field($_POST['value']);
				$return_code = BigbluebuttonApi::set_recording_publish_state($record_id, $value);

				if ($return_code == 200) {
					$response['success'] = true;
				}	
			}
		}
		wp_send_json($response);
	}
    
    /**
	 * Handle protect/unprotect a recording
	 * 
	 * @since	3.0.0
	 * 
	 * @return	String	$response	JSON response to changing a recording's protection status.
	 */
	public function set_bbb_recording_protect_state() {
		$response = array();
		$response['success'] = false;
		if (current_user_can('manage_bbb_room_recordings')) {
			if (array_key_exists('meta_nonce', $_POST) && array_key_exists('record_id', $_POST) && 
				array_key_exists('value', $_POST) && 
				(sanitize_text_field($_POST['value']) == 'true' || sanitize_text_field($_POST['value']) == 'false') &&
				wp_verify_nonce($_POST['meta_nonce'], 'bbb_manage_recordings_nonce')) {

				$record_id = sanitize_text_field($_POST['record_id']);
				$value = sanitize_text_field($_POST['value']);
				$return_code = BigbluebuttonApi::set_recording_protect_state($record_id, $value);

				if ($return_code == 200) {
					$response['success'] = true;
				}	
			}
		}
		wp_send_json($response);
    }

    /**
	 * Handle deleting a recording.
	 * 
	 * @since	3.0.0
	 * 
	 * @return	String	$response 	JSON response to deleting a recording.
	 */
	public function trash_bbb_recording() {
		$response = array();
		$response['success'] = false;
		if (current_user_can('manage_bbb_room_recordings')) {
			if (array_key_exists('meta_nonce', $_POST) && array_key_exists('record_id', $_POST) && 
				wp_verify_nonce($_POST['meta_nonce'], 'bbb_manage_recordings_nonce')) {

				$record_id = sanitize_text_field($_POST['record_id']);
				$return_code = BigbluebuttonApi::delete_recording($record_id);

				if ($return_code == 200) {
					$response['success'] = true;
				}	
			}
		}
		wp_send_json($response);
    }
    
    /**
     * Send recording metadata to Bigbluebutton API.
     * 
     * @since   3.0.0
     * 
     * @return  String  $response   JSON response to editing a recording's metadata.
     */
    public function set_bbb_recording_edits() {
        $response = array();
		$response['success'] = false;

		if (current_user_can('manage_bbb_room_recordings')) {
            if (array_key_exists('meta_nonce', $_POST) && 
                array_key_exists('record_id', $_POST) && 
                array_key_exists('type', $_POST) && 
                array_key_exists('value', $_POST) &&
                wp_verify_nonce($_POST['meta_nonce'], 'bbb_manage_recordings_nonce')
                ) {

				$record_id = sanitize_text_field($_POST['record_id']);
                $type = sanitize_text_field($_POST['type']);
                $value = wp_unslash(sanitize_text_field($_POST['value']));

				$return_code = BigbluebuttonApi::set_recording_edits($record_id, $type, $value);

				if ($return_code == 200) {
					$response['success'] = true;
				}	
			}
		}
		wp_send_json($response);
	}
	
	/**
	 * Update the join room form on the front end with the room ID and whether the access code input should be shown or not.
	 * 
	 * @since	3.0.0
	 */
	public function get_join_form() {
		$response = array();
		$response['success'] = false;
		
		if (array_key_exists('room_id', $_POST)) {
			$access_using_code = current_user_can('join_with_access_code_bbb_room');
			$access_as_moderator = (current_user_can('join_as_moderator_bbb_room') || (get_current_user_id() == get_post($_POST['room_id'])->post_author));
			$access_as_viewer = current_user_can('join_as_viewer_bbb_room');

			$response["success"] = true;
			$response["hide_access_code_input"] = $access_as_moderator || $access_as_viewer || !$access_using_code;
		}

		wp_send_json($response);
	}
    
    /**
	 * Get user's name for the meeting.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Object	$user		User object.
	 * @return	String	$username	Display of the user for joining the meeting.
	 */
	private function get_meeting_username($user) {
		$username = ($user && $user->display_name) ? $user->display_name : '';
		return $username;
	}
}