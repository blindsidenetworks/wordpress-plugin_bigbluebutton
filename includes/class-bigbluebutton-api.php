<?php
/**
 * Static API calls to the Bigbluebutton Server.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 */

/**
 * Static API calls to the Bigbluebutton Server.
 *
 * This class defines all code necessary to interact with the remote Bigbluebutton server.
 *
 * @since      3.0.0
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Api {

	/**
	 * Create new meeting.
	 *
	 * @since   3.0.0
	 * 
	 * @param   Integer $room_id            Custom post id of the room the user is creating a meeting for.
	 * @param   String  $logout_url         URL to return to after logging out.
	 * 
	 * @return  Integer $return_code|404    HTML response of the bigbluebutton server.
	 */
	public static function create_meeting( $room_id, $logout_url ) {
		$rid = intval( $room_id );

		if ( get_post( $rid ) === false || 'bbb-room' != get_post_type( $rid ) ) {
			return 404;
		}

		$name           = html_entity_decode( get_the_title( $rid ) );
		$moderator_code = get_post_meta( $rid, 'bbb-room-moderator-code', true );
		$viewer_code    = get_post_meta( $rid, 'bbb-room-viewer-code', true );
		$recordable     = get_post_meta( $rid, 'bbb-room-recordable', true );
		$meeting_id     = get_post_meta( $rid, 'bbb-room-meeting-id', true );
		$arr_params     = array(
			'name'        => esc_attr( $name ),
			'meetingID'   => rawurlencode( $meeting_id ),
			'attendeePW'  => rawurlencode( $viewer_code ),
			'moderatorPW' => rawurlencode( $moderator_code ),
			'logoutURL'   => esc_url( $logout_url ),
			'record'      => $recordable,
		);

		$url = self::build_url( 'create', $arr_params );

		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return 404;
		}

		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return 200;
		} elseif ( property_exists( $response, 'returncode' ) && 'FAILURE' == $response->returncode ) {
			return 403;
		}

		return 500;

	}

	/**
	 * Join meeting.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $room_id    Custom post id of the room the user is trying to join.
	 * @param   String  $username   Full name of the user trying to join the room.
	 * @param   String  $password   Entry code of the meeting that the user is attempting to join with.
	 * @param   String  $logout_url URL to return to after logging out.
	 * 
	 * @return  String  $url|null   URL to enter the meeting.
	 */
	public static function get_join_meeting_url( $room_id, $username, $password, $logout_url = null) {

		$rid   = intval( $room_id );
		$uname = sanitize_text_field( $username );
		$pword = sanitize_text_field( $password );
		$lo_url = ( $logout_url ? esc_url( $logout_url ) : get_permalink( $rid ) );

		if ( get_post( $rid ) === false || 'bbb-room' != get_post_type( $rid ) ) {
			return null;
		}

		if ( ! self::is_meeting_running( $rid ) ) {
			$code = self::create_meeting( $rid, $lo_url );
			if ( 200 !== $code ) {
				wp_die( esc_html__( 'It is currently not possible to create rooms on the server. Please contact support for help.', 'bigbluebutton' ) );
			}
		}

		$meeting_id = get_post_meta( $rid, 'bbb-room-meeting-id', true );
		$arr_params = array(
			'meetingID' => rawurlencode( $meeting_id ),
			'fullName'  => $uname,
			'password'  => rawurlencode( $pword ),
		);

		$url = self::build_url( 'join', $arr_params );

		return $url;
	}

	/**
	 * Check if meeting is running.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $room_id            Custom post id of a room.
	 * @return  Boolean true|false|null     If the meeting is running or not.
	 */
	public static function is_meeting_running( $room_id ) {

		$rid = intval( $room_id );

		if ( get_post( $rid ) === false || 'bbb-room' != get_post_type( $rid ) ) {
			return null;
		}

		$meeting_id = get_post_meta( $rid, 'bbb-room-meeting-id', true );
		$arr_params = array(
			'meetingID' => rawurlencode( $meeting_id ),
		);

		$url           = self::build_url( 'isMeetingRunning', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return null;
		}

		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'running' ) && 'true' == $response->running ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all recordings for selected room.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array  $room_ids               List of custom post ids for rooms.
	 * @param   String $recording_state        State of recordings to get.
	 * @return  Array  $recordings             List of recordings for this room.
	 */
	public static function get_recordings( $room_ids, $recording_state = 'published' ) {
		$state       = sanitize_text_field( $recording_state );
		$recordings  = [];
		$meeting_ids = '';

		foreach ( $room_ids as $rid ) {
			$meeting_ids .= get_post_meta( sanitize_text_field( $rid ), 'bbb-room-meeting-id', true ) . ',';
		}

		substr_replace( $meeting_ids, '', -1 );

		$arr_params = array(
			'meetingID' => $meeting_ids,
			'state'     => $state,
		);

		$url           = self::build_url( 'getRecordings', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return $recordings;
		}

		$response = self::response_to_xml( $full_response );
		if ( property_exists( $response, 'recordings' ) && property_exists( $response->recordings, 'recording' ) ) {
			$recordings = $response->recordings->recording;
		}

		return $recordings;
	}

	/**
	 * Publish/unpublish a recording.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $recording_id   The ID of the recording that will be published/unpublished.
	 * @param   String $state          Set publishing state of the recording.
	 * @return  Integer 200|404|500     Status of the request.
	 */
	public static function set_recording_publish_state( $recording_id, $state ) {
		$record = sanitize_text_field( $recording_id );

		if ( 'true' != $state && 'false' != $state ) {
			return 404;
		}

		$arr_params = array(
			'recordID' => rawurlencode( $record ),
			'publish'  => rawurlencode( $state ),
		);

		$url           = self::build_url( 'publishRecordings', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return 404;
		}
		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return 200;
		}
		return 500;
	}

	/**
	 * Protect/unprotect a recording.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $recording_id   The ID of the recording that will be protected/unprotected.
	 * @param   String $state          Set protected state of the recording.
	 * @return  Integer 200|404|500     Status of the request.
	 */
	public static function set_recording_protect_state( $recording_id, $state ) {
		$record = sanitize_text_field( $recording_id );

		if ( 'true' != $state && 'false' != $state ) {
			return 404;
		}

		$arr_params = array(
			'recordID' => rawurlencode( $record ),
			'protect'  => rawurlencode( $state ),
		);

		$url           = self::build_url( 'updateRecordings', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return 404;
		}
		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return 200;
		}
		return 500;
	}

	/**
	 * Delete recording.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $recording_id   ID of the recording that will be deleted.
	 * @return  Integer 200|404|500     Status of the request.
	 */
	public static function delete_recording( $recording_id ) {
		$record = sanitize_text_field( $recording_id );

		$arr_params = array(
			'recordID' => rawurlencode( $record ),
		);

		$url           = self::build_url( 'deleteRecordings', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return 404;
		}
		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return 200;
		}
		return 500;
	}

	/**
	 * Change recording meta fields.
	 *
	 * @param   String $recording_id   ID of the recording that will be edited.
	 * @param   String $type           Type of meta field that will be changed.
	 * @param   String $value          Value of the meta field.
	 *
	 * @return  Integer 200|404|500     Status of the request.
	 */
	public static function set_recording_edits( $recording_id, $type, $value ) {
		$record         = sanitize_text_field( $recording_id );
		$recording_type = sanitize_text_field( $type );
		$new_value      = sanitize_text_field( $value );
		$meta_key       = 'meta_recording-' . $recording_type;

		$arr_params = array(
			'recordID' => rawurlencode( $record ),
			$meta_key  => rawurlencode( $new_value ),
		);

		$url           = self::build_url( 'updateRecordings', $arr_params );
		$full_response = self::get_response( $url );

		if ( is_wp_error( $full_response ) ) {
			return 404;
		}

		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return 200;
		}

		return 500;
	}

	/**
	 * Verify that the endpoint is a BigBlueButton server, the salt is correct, and the server is running.
	 *
	 * @since 3.0.0
	 *
	 * @param String $url         BigBlueButton URL endpoint to be tested.
	 * @param String $salt        BigBlueButton server salt to be tested.
	 *
	 * @return Boolean true|false Whether the BigBlueButton server settings are correctly configured or not.
	 */
	public static function test_bigbluebutton_server( $url, $salt ) {
		$test_url      = $url . 'api/getMeetings?checksum=' . sha1( 'getMeetings' . $salt );
		$full_response = self::get_response( $test_url );

		if ( is_wp_error( $full_response ) ) {
			return false;
		}

		$response = self::response_to_xml( $full_response );

		if ( property_exists( $response, 'returncode' ) && 'SUCCESS' == $response->returncode ) {
			return true;
		}

		return false;
	}

	/**
	 * Fetch response from remote url.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $url        URL to get response from.
	 * @return  Array|WP_Error  $response   Server response in array format.
	 */
	private static function get_response( $url ) {
		$result = wp_remote_get( esc_url_raw( $url ) );
		return $result;
	}

	/**
	 * Convert website response to XML Object.
	 *
	 * @since   3.0.0
	 *
	 * @param  Array $full_response       Website response to convert to XML object.
	 * @return Object $xml                 XML Object of the body.
	 */
	private static function response_to_xml( $full_response ) {
		try {
			$xml = new SimpleXMLElement( wp_remote_retrieve_body( $full_response ) );
		} catch ( Exception $exception ) {
			return new stdClass();
		}
		return $xml;
	}

	/**
	 * Returns the complete url for the bigbluebutton server request.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $request_type   Type of request to the bigbluebutton server.
	 * @param   Array  $args           Parameters of the request stored in an array format.
	 * @return  String $url            URL with all parameters and calculated checksum.
	 */
	private static function build_url( $request_type, $args ) {
		$type = sanitize_text_field( $request_type );

		$url_val  = strval( get_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' ) );
		$salt_val = strval( get_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' ) );

		$url = $url_val . 'api/' . $type . '?';

		$params = http_build_query( $args );

		$url .= $params . '&checksum=' . sha1( $type . $params . $salt_val );

		return $url;
	}
}
