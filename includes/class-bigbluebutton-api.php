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
	public static function create_meeting( $room_id ) {
		$rid = intval($room_id);

		if (get_post($rid) === false || get_post_type($rid) != 'bbb-room') {
			return 404;
		}

		$name = get_the_title($rid);
		$moderator_code = get_post_meta($rid, 'bbb-room-moderator-code', true);
		$viewer_code = get_post_meta($rid, 'bbb-room-viewer-code', true);
		$recordable = get_post_meta($rid, 'bbb-room-recordable', true);
		$logout_url = get_permalink($rid);
		$arr_params = array(
			'name' => urlencode($name),
			'meetingID' => urlencode('meeting-' . $rid),
			'attendeePW' => urlencode($viewer_code),
			'moderatorPW' => urlencode($moderator_code),
			'logoutURL' => $logout_url,
			'record' => $recordable
		);

		$url = self::build_url('create', $arr_params);

		$response = self::get_response($url);

        if (is_wp_error( $response )) {
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

		$arr_params = array(
			'meetingID' => urlencode('meeting-' . $rid),
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

		$arr_params = array(
			'meetingID' => urlencode('meeting-' . $rid),
		);

		$url = self::build_url('isMeetingRunning', $arr_params);
        $full_response = self::get_response( $url );

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
	 * @param   Integer     $room_id            Custom post id of a room.
	 * @return	Array		$recordings			List of recordings for this room.
	 */
	public static function get_recordings($room_id) {
		$rid = intval($room_id);
		$recordings = [];

		if (get_post($rid) === false || get_post_type($rid) != 'bbb-room') {
			return $recordings;
		}

		$arr_params = array(
			'meetingID' => urlencode('meeting-' . $rid),
		);

		$url = self::build_url('getRecordings', $arr_params);
		$full_response = self::get_response( $url );
		
		if (is_wp_error($full_response)) {
            return $recordings;
		}

		$response = new SimpleXMLElement(wp_remote_retrieve_body($full_response));
		if(property_exists($response, 'recordings') && property_exists($response->recordings, 'recording')) {
			$recordings = $response->recordings->recording;
		}
		return $recordings;
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