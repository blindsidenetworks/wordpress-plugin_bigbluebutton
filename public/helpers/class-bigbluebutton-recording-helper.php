<?php

/**
 * The recordings helper to fetch recordings.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 */

/**
 * The recordings helper to fetch recordings.
 *
 * Gets recordings based on rooms, capability, and order.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public/helpers
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Recording_Helper {

	/**
	 * Recordings.
	 *
	 * @since 3.0.0
	 *
	 * @access private
	 * @var Array $recordings List of recordings.
	 */
	private $recordings;

	/**
	 * Initialize the class.
	 *
	 * @since   3.0.0
	 */
	public function __construct() {
		$this->recordings = array();
	}

	/**
	 * Get filtered and ordered recordings for the room based on capability.
	 *
	 * @param   Array  $room_ids       Room IDs for the list of recordings.
	 * @param   String $order          Direction to order in.
	 * @param   String $orderby        Column name to order.
	 * @return  Array  $recordings     List of filtered and sorted recordings.
	 */
	public function get_filtered_and_ordered_recordings_based_on_capability( $room_ids, $order = '', $orderby = '' ) {
		$this->get_recordings_based_on_capability( $room_ids );
		$this->filter_recordings();
		$this->order_recordings( $order, $orderby );
		return $this->recordings;
	}

	/**
	 * Get recordings for the room based on capability.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array $room_ids    List of room IDs.
	 * @return  Array $recordings  List of recordings.
	 */
	public function get_recordings_based_on_capability( $room_ids ) {
		$manage_recordings = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' );
		if ( $manage_recordings ) {
			$this->recordings = Bigbluebutton_Api::get_recordings( $room_ids, 'published,unpublished' );
		} else {
			$this->recordings = Bigbluebutton_Api::get_recordings( $room_ids, 'published' );
		}
		return $this->recordings;
	}

	/**
	 * Filter recordings based on whether the user can manage them or not.
	 *
	 * Assign icon classes and title based on recording published and protected status.
	 * If the user cannot manage recordings, hide them.
	 * Get recording name and description from metadata.
	 *
	 * @since   3.0.0
	 */
	private function filter_recordings() {
		$manage_recordings   = BigBlueButton_Permissions_Helper::user_has_bbb_cap( 'manage_bbb_room_recordings' );
		$filtered_recordings = array();
		foreach ( $this->recordings as $recording ) {
			// Set recording name to be meeting name if recording name is not yet set.
			if ( ! isset( $recording->metadata->{'recording-name'} ) ) {
				$recording->metadata->{'recording-name'} = $recording->name;
			}
			// Set recording description to be an empty string if it is not yet set.
			if ( ! isset( $recording->metadata->{'recording-description'} ) ) {
				$recording->metadata->{'recording-description'} = '';
			}
			if ( $manage_recordings ) {
				$recording = $this->filter_managed_recording( $recording );
				array_push( $filtered_recordings, $recording );
			} elseif ( 'true' == $recording->published ) {
				array_push( $filtered_recordings, $recording );
			}
		}
		$this->recordings = $filtered_recordings;
	}

	/**
	 * Assign classes and title for the icon based on the recording's publish and protect status.
	 *
	 * @since   3.0.0
	 *
	 * @param   SimpleXMLElement $recording  A recording to be inspected.
	 * @return  SimpleXMLElement $recording  A recording that has been inspected.
	 */
	private function filter_managed_recording( $recording ) {
		if ( 'true' == $recording->protected ) {
			$recording->protected_icon_classes = 'bbb-icon bbb_protected_recording is_protected dashicons dashicons-lock';
			$recording->protected_icon_title   = __( 'Protected', 'bigbluebutton' );
		} elseif ( 'false' == $recording->protected ) {
			$recording->protected_icon_classes = 'bbb-icon bbb_protected_recording not_protected dashicons dashicons-unlock';
			$recording->protected_icon_title   = __( 'Unprotected', 'bigbluebutton' );
		}

		if ( 'true' == $recording->published ) {
			$recording->published_icon_classes = 'bbb-icon bbb_published_recording is_published dashicons dashicons-visibility';
			$recording->published_icon_title   = __( 'Published' );
		} else {
			$recording->published_icon_classes = 'bbb-icon bbb_published_recording not_published dashicons dashicons-hidden';
			$recording->published_icon_title   = __( 'Unpublished' );
		}

		$recording->trash_icon_classes = 'bbb-icon bbb_trash_recording dashicons dashicons-trash';
		return $recording;
	}

	/**
	 * Order recordings based on parameters.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $order     Direction to order in.
	 * @param   String $order_by  Column name to order.
	 */
	public function order_recordings( $order = '', $order_by = '' ) {
		if ( '' !== $order && '' !== $order_by ) {

			$direction = sanitize_text_field( $_GET['order'] );
			$field     = sanitize_text_field( $_GET['orderby'] );
			$self      = $this;

			usort(
				$this->recordings, function( $first, $second ) use ( $direction, $field, $self ) {
					if ( $direction == 'asc' ) {
						return ( strcasecmp( $self->get_recording_field( $first, $field ), $self->get_recording_field( $second, $field ) ) > 0 );
					} else {
						return ( strcasecmp( $self->get_recording_field( $first, $field ), $self->get_recording_field( $second, $field ) ) < 0 );
					}
				}
			);

		}
	}

	/**
	 * Get recording field value based on property name.
	 *
	 * @since   3.0.0
	 *
	 * @param   SimpleXMLElement $recording      A recording to get the field name from.
	 * @param   String           $field_name     Name of the field.
	 *
	 * @return  String           $field_value    Value of the field.
	 */
	private function get_recording_field( $recording, $field_name ) {
		$field_value = '';
		if ( 'name' == $field_name ) {
			$field_value = strval( $recording->metadata->{'recording-name'} );
		} elseif ( 'description' == $field_name ) {
			$field_value = strval( $recording->metadata->{'recording-description'} );
		} elseif ( 'date' == $field_name ) {
			$field_value = strval( $recording->startTime );
		}
		return $field_value;
	}
}
