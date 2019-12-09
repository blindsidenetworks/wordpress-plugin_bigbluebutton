<?php
/**
 * The display helper to get partials as strings.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 */

/**
 * The display helper to get partials as strings.
 *
 * Gets views stored in strings for the plugin.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Display_Helper {

	/**
	 * File location of the partials.
	 *
	 * @since 3.0.0
	 *
	 * @access private
	 * @var    String $file File location of the partials.
	 */
	private $file;

	/**
	 * Initialize the class.
	 *
	 * @param String $file File location of the partials.
	 */
	public function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * Get join meeting form as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $room_id                Post ID of the room.
	 * @param   String  $meta_nonce             Nonce for join meeting form.
	 * @param   Boolean $access_as_moderator    Check for if the current user can enter meetings as a moderator.
	 * @param   Boolean $access_as_viewer       Check for if the current user can enter meetings as a viewer.
	 * @param   Boolean $access_using_code      Check for if the current user can enter meetings using an access code.
	 *
	 * @return  String      $form                   Join meeting form stored in a variable.
	 */
	public function get_join_form_as_string( $room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code ) {
		global $wp;
		$current_url         = home_url( add_query_arg( array(), $wp->request ) );
		$heartbeat_available = wp_script_is( 'heartbeat', 'registered' );
		ob_start();
		include $this->file . 'partials/bigbluebutton-join-display.php';
		$form = ob_get_contents();
		ob_end_clean();
		return $form;
	}

	/**
	 * Get recordings with for as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $room_id                            Post ID of the room.
	 * @param   Array   $recordings                         List of recordings for the room.
	 * @param   Boolean $manage_bbb_recordings              User capability to manage recordings.
	 * @param   Boolean $view_extended_recording_formats    User capability to view extended recording formats.
	 *
	 * @return  String      $optional_recordings                Recordings table stored in a variable.
	 */
	public function get_collapsable_recordings_view_as_string( $room_id, $recordings, $manage_bbb_recordings, $view_extended_recording_formats ) {
		$html_recordings = $this->get_recordings_as_string( $room_id, $recordings, $manage_bbb_recordings, $view_extended_recording_formats );
		ob_start();
		include $this->file . 'partials/bigbluebutton-collapsable-recordings-display.php';
		$optional_recordings = ob_get_contents();
		ob_end_clean();
		return $optional_recordings;
	}

	/**
	 * Get basic table of recordings as HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Integer $room_id                            Post ID of the room.
	 * @param   Array   $recordings                         List of recordings for the room.
	 * @param   Boolean $manage_bbb_recordings              User capability to manage recordings.
	 * @param   Boolean $view_extended_recording_formats    User capability to view extended recording formats.
	 *
	 * @return  String      $html_recordings                    Recordings table stored in a variable.
	 */
	private function get_recordings_as_string( $room_id, $recordings, $manage_bbb_recordings, $view_extended_recording_formats ) {
		$columns = 5;
		if ( $manage_bbb_recordings ) {
			$columns++;
		}
		$sort_fields = $this->set_order_by_field();
		ob_start();
		$meta_nonce                   = wp_create_nonce( 'bbb_manage_recordings_nonce' );
		$date_format                  = ( get_option( 'date_format' ) ? get_option( 'date_format' ) : 'Y-m-d' );
		$default_bbb_recording_format = 'presentation';
		include $this->file . 'partials/bigbluebutton-recordings-display.php';
		$html_recordings = ob_get_contents();
		ob_end_clean();
		return $html_recordings;
	}

	/**
	 * Create url and classes for new sorting indicators.
	 *
	 * @since   3.0.0
	 *
	 * @return  Array   $custom_sort_fields     Array of sortable fields for recordings.
	 */
	private function set_order_by_field() {
		$sort_asc_classes   = 'dashicons dashicons-arrow-up-alt2 bbb-header-icon';
		$sort_desc_classes  = 'dashicons dashicons-arrow-down-alt2 bbb-header-icon';
		$sort_meta_nounce   = wp_create_nonce( 'bbb_sort_recording_columns_nonce' );
		$custom_sort_fields = array(
			'name'        => null,
			'description' => null,
			'date'        => null,
		);

		if ( isset( $_GET['order'] ) && isset( $_GET['orderby'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'bbb_sort_recording_columns_nonce' ) ) {
			$new_direction    = ( sanitize_text_field( $_GET['order'] ) == 'asc' ? 'desc' : 'asc' );
			$new_sort_classes = ( 'asc' == $new_direction ? $sort_desc_classes : $sort_asc_classes ) . ' bbb-current-sort-icon';
			$selected_field   = sanitize_text_field( $_GET['orderby'] );

			if ( array_key_exists( $selected_field, $custom_sort_fields ) ) {
				$custom_sort_fields[ $selected_field ] = (object) array(
					'url'            => '?orderby=' . $selected_field . '&order=' . $new_direction . '&nonce=' . $sort_meta_nounce,
					'classes'        => $new_sort_classes,
					'header_classes' => 'bbb-column-header-highlight',
				);
			}
		}

		foreach ( $custom_sort_fields as $field => $values ) {
			if ( null === $custom_sort_fields[ $field ] ) {
				$custom_sort_fields[ $field ] = (object) array(
					'url'            => '?orderby=' . $field . '&order=asc&nonce=' . $sort_meta_nounce,
					'classes'        => $sort_asc_classes . ' bbb-hidden',
					'header_classes' => 'bbb-recordings-unselected-sortable-column',
				);
			}
		}

		return $custom_sort_fields;
	}

	/**
	 * Get room list dropdown for short code as an HTML string.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array   $rooms           Array of rooms that were included in the shortcode.
	 * @param   Integer $selected_room   Room ID of selected room.
	 * @param   String  $html_form  Form associated with dropdown.
	 *
	 * @return  String  $dropdown   Dropdown of rooms stored in a variable.
	 */
	public function get_room_list_dropdown_as_string( $rooms, $selected_room, $html_form ) {
		ob_start();
		include $this->file . 'partials/bigbluebutton-room-dropdown-display.php';
		$dropdown = ob_get_contents();
		ob_end_clean();
		return $dropdown;
	}
}
