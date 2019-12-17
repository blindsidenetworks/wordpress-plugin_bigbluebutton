<?php
/**
 * The tokens helper to handle creating public facing views.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 */

/**
 * The tokens helper to handle creating public facing views.
 *
 * Generates rooms for viewing from tokens.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Tokens_Helper {

	/**
	 * The error message if a room cannot be displayed.
	 *
	 * @var String $error_message
	 */
	private static $error_message;

	/**
	 * Get tokens string from shortcode attributes.
	 *
	 * @since   3.0.0
	 * @param   Array $atts              Array of attributes submitted in the shortcode.
	 * @return  String $tokens_string    List of tokens separated by commas.
	 */
	public static function get_token_string_from_atts( $atts ) {
		$tokens_string = '';

		foreach ( $atts as $key => $param ) {
			if ( 'token' == $key ) {
				if ( 'token' == substr( $param, 0, 5 ) ) {
					$param = substr( $param, 5 );
				}
				$tokens_string .= $param;
			} else {
				if ( 'token' == substr( $param, 0, 5 ) ) {
					$param = substr( $param, 5 );
				}
				$tokens_string .= ',' . $param;
			}
		}
		return $tokens_string;
	}

	/**
	 * Get join form as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Bigbluebutton_Display_Helper $display_helper     Display helper to get HTML from partials.
	 * @param   String                       $token_string       A list of tokens as a string, separated by commas.
	 * @param   Integer                      $author             The author of the content that will display the join form.
	 *
	 * @return  String                          $content            HTML string containing join forms for the corresponding rooms.
	 */
	public static function join_form_from_tokens_string( $display_helper, $token_string, $author ) {
		$content             = '';
		$tokens_arr          = preg_split( '/\,/', $token_string );
		$meta_nonce          = wp_create_nonce( 'bbb_join_room_meta_nonce' );
		$access_using_code   = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'join_with_access_code_bbb_room' );
		$access_as_moderator = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'join_as_moderator_bbb_room' );
		$access_as_viewer    = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'join_as_viewer_bbb_room' );
		$rooms               = array();

		foreach ( $tokens_arr as $raw_token ) {
			if ( sanitize_text_field( $raw_token ) == '' ) {
				continue;
			}
			$token   = preg_replace( '/[^a-zA-Z0-9]+/', '', $raw_token );
			$room_id = self::find_room_id_by_token( $token, $author );
			if ( 0 == $room_id ) {
				$content .= '<p>';
				$content .= self::$error_message;
				$content .= '</p>';
				return $content;
			}
			$rooms[] = (object) array(
				'room_id'   => $room_id,
				'room_name' => get_the_title( $room_id ),
			);
		}

		if ( count( $rooms ) > 0 ) {
			if ( ! $access_as_moderator ) {
				$access_as_moderator = ( get_current_user_id() == get_post( $rooms[0]->room_id )->post_author );
			}
			$selected_room = $rooms[0]->room_id;
			if ( isset( $_REQUEST['room_id'] ) ) {
				$selected_room = $_REQUEST['room_id'];
			}
			$join_form = $display_helper->get_join_form_as_string( $selected_room, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code );
			if ( count( $rooms ) > 1 ) {
				$join_form = $display_helper->get_room_list_dropdown_as_string( $rooms, $selected_room, $join_form );
			}
			$content .= $join_form;
		} else {
			$content = '<p>' . esc_html__( 'There are no rooms in the selection.', 'bigbluebutton' ) . '</p>';
		}
		return $content;
	}

	/**
	 * Get recordings table as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Bigbluebutton_Display_Helper $display_helper     Display helper to get HTML from partials.
	 * @param   String                       $token_string       A list of tokens as a string, separated by commas.
	 * @param   Integer                      $author             The author of the content that will display the join form.
	 *
	 * @return  String                       $content            HTML string containing recordings from the corresponding rooms.
	 */
	public static function recordings_table_from_tokens_string( $display_helper, $token_string, $author ) {
		$manage_recordings               = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' );
		$view_extended_recording_formats = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'view_extended_bbb_room_recording_formats' );
		$tokens_arr                      = preg_split( '/\,/', $token_string );
		$room_ids                        = array();
		$content                         = '';

		foreach ( $tokens_arr as $raw_token ) {
			if ( sanitize_text_field( $raw_token ) == '' ) {
				continue;
			}
			$token   = preg_replace( '/[^a-zA-Z0-9]+/', '', $raw_token );
			$room_id = self::find_room_id_by_token( $token, $author );
			if ( 0 == $room_id ) {
				$content .= '<p>';
				$content .= self::$error_message;
				$content .= '</p>';
				return $content;
			}
			$room_ids[] = $room_id;
		}

		$recordings = self::get_recordings( $room_ids );
		if ( count( $room_ids ) > 0 ) {
			$content .= $display_helper->get_collapsable_recordings_view_as_string( $room_ids[0], $recordings, $manage_recordings, $view_extended_recording_formats );
		}
		return $content;
	}

	/**
	 * Get room from token.
	 *
	 * @since   3.0.0
	 *
	 * @param   String  $token      Token to get associated room ID from.
	 * @param   Integer $author     Author writing the content using this shortcode.
	 *
	 * @return  Integer $room_id    ID of the room, given that the author may access it.
	 */
	public static function find_room_id_by_token( $token, $author ) {
		// Only show room if author can create rooms.
		if ( ! user_can( $author, 'edit_bbb_rooms' ) ) {
			self::$error_message = esc_html__( 'This user does not have permission to display any rooms in a shortcode or widget.', 'bigbluebutton' );
			return 0;
		}

		if ( 'z' == substr( $token, 0, 1 ) ) {
			return self::check_if_room_exists_for_new_token_format( $token, $author );
		} else {
			return self::check_if_room_exists_for_old_token_format( $token, $author );
		}
	}

	/**
	 * Check if rooms and recordings should load on this page.
	 *
	 * @since  3.0.0
	 * @return Boolean  $can_view          Boolean value of whether join room form and recordings should show on this page.
	 */
	public static function can_display_room_on_page() {
		global $pagenow;
		$can_view = true;
		if ( 'edit.php' == $pagenow || 'post.php' == $pagenow || 'post-new.php' == $pagenow ) {
			$can_view = false;
		}
		return $can_view;
	}

	/**
	 * Get room ID using new token format.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $token     String value of the token.
	 * @param   Integer $author   Author writing the content using this shortcode.
	 * @return  Integer $room_id  Room ID associated with the token.
	 */
	private static function check_if_room_exists_for_new_token_format( $token, $author ) {
		$room_id = (int) substr( $token, 1 );
		$room    = get_post( $room_id );
		if ( false !== $room && null !== $room && 'bbb-room' == $room->post_type ) {
			if ( 'publish' != $room->post_status ) {
				self::set_error_message( sprintf( wp_kses( __( 'The token: %s is not associated with a published room.', 'bigbluebutton' ), array() ), $token ), $author );
				return 0;
			}
			return $room->ID;
		} else {
			return self::check_if_room_exists_for_old_token_format( $token, $author );
		}
	}

	/**
	 * Get room ID using old token format.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $token     String value of the token.
	 * @param   Integer $author   Author writing the content using this shortcode.
	 * @return  Integer $room_id  Room ID associated with the token.
	 */
	private static function check_if_room_exists_for_old_token_format( $token, $author ) {
		$args = array(
			'post_type'      => 'bbb-room',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'bbb-room-token',
					'value' => $token,
				),
			),
		);

		$query = new WP_Query( $args );
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $key => $room_id ) {
				$room = get_post( $room_id );
				if ( 'publish' != $room->post_status ) {
					self::set_error_message( sprintf( wp_kses( __( 'The token: %s is not associated with a published room.', 'bigbluebutton' ), array() ), $token ), $author );
					return 0;
				}
				return $room_id;
			}
		}

		self::set_error_message( sprintf( wp_kses( __( 'The token: %s is not associated with an existing room.', 'bigbluebutton' ), array() ), $token ), $author );
		return 0;
	}

	/**
	 * Get recordings from recording helper.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array $room_ids           Room IDs to get recordings from.
	 *
	 * @return  Array $recordings         List of recordings belonging to the selected rooms.
	 */
	private static function get_recordings( $room_ids ) {
		$recording_helper = new Bigbluebutton_Recording_Helper();

		if ( isset( $_GET['order'] ) && isset( $_GET['orderby'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'bbb_sort_recording_columns_nonce' ) ) {
			$order   = sanitize_text_field( $_GET['order'] );
			$orderby = sanitize_text_field( $_GET['orderby'] );
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability( $room_ids, $order, $orderby );
		} else {
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability( $room_ids );
		}
	}

	/**
	 * Set error message based on if user can see the detailed error message or not.
	 *
	 * @since   3.0.0
	 *
	 * @param  String  $detailed_message                      Detailed error message that describes the issue.
	 * @param  Integer $author                                Author writing the content using this shortcode.
	 */
	private static function set_error_message( $detailed_message, $author ) {
		if ( current_user_can( 'edit_others_bbb_rooms' ) || get_current_user_id() == $author ) {
			self::$error_message = $detailed_message;
		} else {
			self::$error_message = esc_html__( 'The room linked to this resource is not configured correctly.', 'bigbluebutton' );
		}
	}
}
