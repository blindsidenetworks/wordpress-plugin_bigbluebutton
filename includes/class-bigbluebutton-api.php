<?php 

/**
 * Static API calls to the Bigbluebutton Server.
 * 
 * @Since 3.0.0
 */
class BigbluebuttonApi {

	/**
	 * Create new meeting.
	 * 
	 * @since   3.0.0
	 * @param   Integer $room_id            Custom post id of the room the user is creating a meeting for.
	 * @return  Integer $return_code|404    HTML response of the bigbluebutton server.
	 */
	public static function create_meeting($room_id) {
		$rid = intval($room_id);

		if (get_post($rid) === false || get_post_type($rid) != 'bbb-room') {
			return 404;
		}

		$name = get_the_title($rid);
		$moderator_code = get_post_meta($rid, 'bbb-room-moderator-code', true);
		$viewer_code = get_post_meta($rid, 'bbb-room-viewer-code', true);
		$recordable = get_post_meta($rid, 'bbb-room-recordable', true);
		$entry_token = get_post_meta($rid, 'bbb-room-token', true);
		$logout_url = get_permalink($rid);
		$arr_params = array(
			'name' => urlencode($name),
			'meetingID' => urlencode($entry_token),
			'attendeePW' => urlencode($viewer_code),
			'moderatorPW' => urlencode($moderator_code),
			'logoutURL' => $logout_url,
			'record' => $recordable
		);

		$url = self::build_url('create', $arr_params);

		$response = self::get_response($url);

        if (is_wp_error($response)) {
            return 404;
        }

		$return_code = $response['response']['code'];

		return $return_code;

	}

	/**
	 * Join meeting.
	 * 
	 * @since   3.0.0
	 * 
	 * @param   Integer   $room_id    Custom post id of the room the user is trying to join.
	 * @param   String    $username   Full name of the user trying to join the room.
	 * @param   String    $password   Entry code of the meeting that the user is attempting to join with.
	 * @return  String    $url|null   URL to enter the meeting.
	 */
	public static function get_join_meeting_url($room_id, $username, $password) {

		$rid = intval($room_id);
		$uname = sanitize_text_field($username);
		$pword = sanitize_text_field($password);

		if (get_post($rid) === false || get_post_type($rid) != 'bbb-room') {
			return null;
		}

		if ( ! self::is_meeting_running($rid)) {
			self::create_meeting($rid);
		}

		$entry_token = get_post_meta($rid, 'bbb-room-token', true);
		$arr_params = array(
			'meetingID' => urlencode($entry_token),
			'fullName' => urlencode($uname),
			'password' => urlencode($pword),
		);

		$url = self::build_url('join', $arr_params);

		return $url;
	}

	/**
	 * Check if meeting is running.
	 * 
	 * @since   3.0.0
	 * 
	 * @param   Integer     $room_id            Custom post id of a room.
	 * @return  Boolean     true|false|null     If the meeting is running or not.
	 */
	public static function is_meeting_running($room_id) {

		$rid = intval($room_id);

		if (get_post($rid) === false || get_post_type($rid) != 'bbb-room') {
			return null;
		}

		$entry_token = get_post_meta($rid, 'bbb-room-token', true);
		$arr_params = array(
			'meetingID' => urlencode($entry_token),
		);

		$url = self::build_url('isMeetingRunning', $arr_params);
        $full_response = self::get_response($url);

        if (is_wp_error($full_response)) {
            return null;
        }

		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));

		if (array_key_exists('running', $response) && $response['running'] == "true") {
			return true;
		}

		return false;
	}

	/**
	 * Get all recordings for selected room.
	 * 
	 * @since	3.0.0
	 * 
	 * @param   Array     	$room_ids            	List of custom post ids for rooms.
	 * @param	String		$recording_state		State of recordings to get.
	 * @return	Array		$recordings				List of recordings for this room.
	 */
	public static function get_recordings($room_ids, $recording_state = 'published') {
		$state = sanitize_text_field($recording_state);
		$recordings = [];
		$entry_token = "";

		foreach($room_ids as $rid) {
			$entry_token .= get_post_meta(sanitize_text_field($rid), 'bbb-room-token', true) . ',';
		}

		substr_replace($entry_token ,"", -1);
		
		$arr_params = array(
			'meetingID' => $entry_token,
			'state' => $state
		);

		$url = self::build_url('getRecordings', $arr_params);
		$full_response = self::get_response($url);
		
		if (is_wp_error($full_response)) {
            return $recordings;
		}

		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));
		if (property_exists($response, 'recordings') && property_exists($response->recordings, 'recording')) {
			$recordings = $response->recordings->recording;
		}

		return $recordings;
	}

	/**
	 * Publish/unpublish a recording.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String	$recording_id	The ID of the recording that will be published/unpublished.
	 * @param	String	$state			Set publishing state of the recording.	
	 * @return	Integer	200|404|500		Status of the request.	
	 */
	public static function set_recording_publish_state($recording_id, $state) {
		$record = sanitize_text_field($recording_id);

		if ($state != 'true' && $state != 'false') {
			return 404;
		}

		$arr_params = array(
			'recordID' => urlencode($record),
			'publish' => urlencode($state)
		);

		$url = self::build_url('publishRecordings', $arr_params);
		$full_response = self::get_response($url);

		if (is_wp_error($full_response)) {
            return 404;
		}
		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));

		if (property_exists($response, 'returncode') && $response->returncode == "SUCCESS") {
			return 200;
		}
		return 500;
	}

	/**
	 * Protect/unprotect a recording.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String	$recording_id	The ID of the recording that will be protected/unprotected.
	 * @param	String	$state			Set protected state of the recording.	
	 * @return	Integer	200|404|500		Status of the request.	
	 */
	public static function set_recording_protect_state($recording_id, $state) {
		$record = sanitize_text_field($recording_id);

		if ($state != 'true' && $state != 'false') {
			return 404;
		}

		$arr_params = array(
			'recordID' => urlencode($record),
			'protect' => urlencode($state)
		);

		$url = self::build_url('updateRecordings', $arr_params);
		$full_response = self::get_response($url);

		if (is_wp_error($full_response)) {
            return 404;
		}
		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));

		if (property_exists($response, 'returncode') && $response->returncode == "SUCCESS") {
			return 200;
		}
		return 500;
	}

	/**
	 * Delete recording.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String		$recording_id	ID of the recording that will be deleted.
	 * @return	Integer		200|404|500		Status of the request.
	 */
	public static function delete_recording($recording_id) {
		$record = sanitize_text_field($recording_id);

		$arr_params = array(
			'recordID' => urlencode($record)
		);

		$url = self::build_url('deleteRecordings', $arr_params);
		$full_response = self::get_response($url);

		if (is_wp_error($full_response)) {
            return 404;
		}
		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));

		if (property_exists($response, 'returncode') && $response->returncode == "SUCCESS") {
			return 200;
		}
		return 500;
	}

	/**
	 * Change recording meta fields.
	 * 
	 * @param	String		$recording_id	ID of the recording that will be edited.
	 * @param	String		$type			Type of meta field that will be changed.
	 * @param	String		$value			Value of the meta field.
	 * 
	 * @return	Integer		200|404|500		Status of the request.
	 */
	public static function set_recording_edits($recording_id, $type, $value) {
		$record = sanitize_text_field($recording_id);
		$recording_type = sanitize_text_field($type);
		$new_value = sanitize_text_field($value);
		$meta_key = "meta_recording-" . $recording_type;

		$arr_params = array(
			'recordID' => urlencode($record),
			$meta_key => urlencode($new_value)
		);

		$url = self::build_url('updateRecordings', $arr_params);
		$full_response = self::get_response($url);

		if (is_wp_error($full_response)) {
            return 404;
		}

		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));

		if (property_exists($response, 'returncode') && $response->returncode == "SUCCESS") {
			return 200;
		}

		return 500;
	}

	/**
	 * Fetch response from remote url.
	 * 
	 * @since   3.0.0
	 * 
	 * @param   String          $url        URL to get response from.
	 * @return  Array|WP_Error  $response   Server response in array format.
	 */
	private static function get_response($url) {
		$result = wp_remote_get(esc_url_raw($url));
		return $result;
	}

	/**
	 * Returns the complete url for the bigbluebutton server request.
	 * 
	 * @since   3.0.0
	 * 
	 * @param   String   $request_type   Type of request to the bigbluebutton server.
	 * @param   Array    $args           Parameters of the request stored in an array format.
	 * @return  String   $url            URL with all parameters and calculated checksum.   
	 */
	private static function build_url($request_type, $args) {
		$type = sanitize_text_field($request_type);

		$url_val = strval(get_option('bigbluebutton_endpoint_url'));
		$salt_val = strval(get_option('bigbluebutton_salt'));

		$url = $url_val . 'api/' . $type . '?';

		$params = http_build_query($args);

		$url .= $params . "&checksum=" . sha1($type . $params . $salt_val);

		return $url;
	}
}