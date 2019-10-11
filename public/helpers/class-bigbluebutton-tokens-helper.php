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
	 * Get join form as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Bigbluebutton_Display_Helper      $display_helper     Display helper to get HTML from partials.
	 * @param   String                          $token_string       A list of tokens as a string, separated by commas
	 * @param   Integer                         $author             The author of the content that will display the join form.
	 *
	 * @return  String                          $content            HTML string containing join forms for the corresponding rooms.
	 */
	public static function join_form_from_tokens_string( $display_helper, $token_string, $author ) {
		$content             = '';
		$tokens_arr          = preg_split( '/\,/', $token_string );
		$meta_nonce          = wp_create_nonce( 'bbb_join_room_meta_nonce' );
		$access_using_code   = current_user_can( 'join_with_access_code_bbb_room' );
		$access_as_moderator = current_user_can( 'join_as_moderator_bbb_room' );
		$access_as_viewer    = current_user_can( 'join_as_viewer_bbb_room' );
		if ( ! is_user_logged_in() && get_role( 'anonymous' ) ) {
			$current_role        = get_role( 'anonymous' );
			$access_using_code   = $current_role->has_cap( 'join_with_access_code_bbb_room' );
			$access_as_moderator = $current_role->has_cap( 'join_as_moderator_bbb_room' );
			$access_as_viewer    = $current_role->has_cap( 'join_as_viewer_bbb_room' );
		}
		$rooms = array();

		foreach ( $tokens_arr as $raw_token ) {
			if ( sanitize_text_field( $raw_token ) == '' ) {
				continue;
			}
			$token   = preg_replace( '/[^a-zA-Z0-9]+/', '', $raw_token );
			$room_id = self::find_room_id_by_token( $token, $author );
			if ( 0 == $room_id ) {
				$content  = '<p>';
				$content .= sprintf( wp_kses( __( 'The token: %s is not associated with a published room.', 'bigbluebutton' ), array() ), $token );
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
			$join_form = $display_helper->get_join_form_as_string( $rooms[0]->room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code );
			if ( count( $rooms ) > 1 ) {
				$join_form = $display_helper->get_room_list_dropdown_as_string( $rooms, $join_form );
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
	 * @param   Bigbluebutton_Display_Helper      $display_helper     Display helper to get HTML from partials.
	 * @param   String                          $token_string       A list of tokens as a string, separated by commas
	 * @param   Integer                         $author             The author of the content that will display the join form.
	 *
	 * @return  String                          $content            HTML string containing recordings from the corresponding rooms.
	 */
	public static function recordings_table_from_tokens_string( $display_helper, $token_string, $author ) {
		$manage_recordings               = current_user_can( 'manage_bbb_room_recordings' );
		$view_extended_recording_formats = current_user_can( 'view_extended_bbb_room_recording_formats' );
		$tokens_arr                      = preg_split( '/\,/', $token_string );
		$room_ids                        = array();
		$content                         = '';

		foreach ( $tokens_arr as $raw_token ) {
			if ( sanitize_text_field( $raw_token ) == '' ) {
				continue;
			}
			$token   = preg_replace( '/[^a-zA-Z0-9]+/', '', $raw_token );
			$room_id = self::find_room_id_by_token( $token, $author );
			if ( $room_id == 0 ) {
				$content  = '<p>' . sprintf( wp_kses( __( 'The token: %s is not associated with a published room.', 'bigbluebutton' ), array() ), $token );
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
	 * @since 	3.0.0
	 *
	 * @param   String      $token      Meeting ID to get associated room ID from.
	 * @param   Integer     $author     Author writing the content using this shortcode.
	 *
	 * @return  Integer     $room_id    ID of the room, given that the author may access it.
	 */
	public static function find_room_id_by_token( $token, $author ) {
		// New way of creating meeting ID.
		if ( 'meeting' == substr( $token, 0, 7 ) ) {
			$room_id = substr( $token, 7 );
			$room    = get_post( $room_id );
			if ( false !== $room && 'bbb-room' == $room->post_type && 'publish' == $room->post_status ) {
				return $room->ID;
			} else {
				return 0;
			}
		} else {
			// Look for the meeting ID in the post meta of the room.
			$args = array(
				'post_type'      => 'bbb-room',
				'post_status'    => 'publish',
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

			// Only show room if author can create rooms.
			if ( ! user_can( $author, 'edit_bbb_rooms' ) ) {
				return 0;
			}

			$query = new WP_Query( $args );
			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $key => $room_id ) {
					return $room_id;
				}
			}
		}
		return 0;
	}

	/**
	 * Get recordings from recording helper.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array       $room_ids           Room IDs to get recordings from.
	 *
	 * @return  Array       $recordings
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
}
