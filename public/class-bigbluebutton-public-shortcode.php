<?php

class Bigbluebutton_Public_Shortcode {

    /**
     * Register bigbluebutton shortcode.
     * 
     * @since   3.0.0
     * 
     */
    public function register_shortcodes() {
		add_shortcode('bigbluebutton', array($this, 'display_bigbluebutton_shortcode'));
    }
    
    /**
     * Handle shortcode attributes.
     * 
     * @since   3.0.0
     * 
     * @param   Array   $atts       Parameters in the shortcode.
     * @param   String  $content    Content of the shortcode.
     * 
     * @return  String  $content    Content of the shortcode with rooms and recordings.
     */
    public function display_bigbluebutton_shortcode($atts = [], $content = null) {
		$rooms = array();
		$type = 'room';
        $author = get_the_author_meta('ID');

		foreach($atts as $key => $param) {
			if ($key == 'type' && $param == 'recording') {
				$type = 'recording';
			} else if ($key == 'token') {
				$room_id = $this->find_room_id_by_token($param, $author);
				if ($room_id == 0) {
					$content .= "<p>The token: " . $param . " is not associated with a room.</p>";
					return $content;
				}
				$rooms[] = (object) array (
					'room_id' => $room_id,
					'room_name' => get_the_title($room_id)
				);
			} else if ($key == 'category') {
				$category = get_term_by('slug', $param, 'bbb-room-category');
				if ($category === false) {
					$content .= "<p>The category: " . $param . " is not associated with a room.</p>";
					return $content;
				}
				$category_rooms = $this->find_rooms_by_category($category->slug, $author);
				$rooms = array_merge($rooms, $category_rooms);
			}
		}

        $content .= $this->generate_shortcode_content($rooms, $type);
		return $content;
    }

    /**
     * Generate HTML content for the shortcode.
     * 
     * @since   3.0.0
     * 
     * @param   Array   $rooms      List of rooms.
     * @param   String  $type       Type of data that will be displayed for the shortcode.
     * 
     * @return  String  $content    The content of the shortcode. 
     */
    private function generate_shortcode_content($rooms, $type) {
        $meta_nonce = wp_create_nonce('bbb_join_room_meta_nonce');
		$access_using_code = current_user_can('join_with_access_code_bbb_room');
		$access_as_moderator = current_user_can('join_as_moderator_bbb_room');
		$access_as_viewer = current_user_can('join_as_viewer_bbb_room');
		$manage_recordings = current_user_can('manage_bbb_room_recordings');
        $view_extended_recording_formats = current_user_can('view_extended_bbb_room_recording_formats');
        $display_helper = new BigbluebuttonDisplayHelper(plugin_dir_path(__FILE__));
        $content = "";

        if (sizeof($rooms) > 0) {
            if(!$access_as_moderator) {
                $access_as_moderator = (get_current_user_id() == get_post($rooms[0]->room_id)->post_author);
            }

            if ($type == 'recording') {
                $room_ids = array_column($rooms, 'room_id');
                $recordings = $this->get_recordings($room_ids);
                $content .= $display_helper->get_shortcode_recordings_as_string($room_ids[0], $recordings, $manage_recordings, $view_extended_recording_formats);
            } else if ($type == 'room') {
                $join_form = $display_helper->get_join_form_as_string($rooms[0]->room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code);
                if (sizeof($rooms) > 1) {
                    $join_form = $display_helper->get_room_list_dropdown_as_string($rooms, $join_form);
                }
                $content .= $join_form;
            }
        }

        return $content;
    }
    
    /**
     * Get list of rooms based on category.
     * 
     * @since   3.0.0
     * 
     * @param   String      $category   Slug of the room category to get associated rooms from.
     * @param   Integer     $author     Author writing the content using this shortcode.
     * 
     * @return  Array       $rooms      List of rooms in the category.
     */
    private function find_rooms_by_category($category, $author) {
		$args = array(
			'post_type' => 'bbb-room',
			'fields' => 'ids',
			'no_found_rows' => true,
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'bbb-room-category',
					'field' => 'slug',
					'terms' => $category
				)
			)
        );

        // hide rooms if author does not have permission
        if ( ! user_can($author, 'edit_others_bbb_rooms')) {
            $args['author'] = $author;
        }

		$rooms = array();
		$query = new WP_Query($args);
		if ($query->posts) {
			foreach($query->posts as $key => $room_id) {
				$room = (object) array(
					'room_id' => $room_id,
					'room_name' => get_the_title($room_id)
				);
				$rooms[] = $room;
			}
		}
		return $rooms;
	}

    /**
     * Get room from token.
     * 
     * @since   3.0.0   
     * 
     * @param   String      $token      Meeting ID to get associated room ID from.
     * @param   Integer     $author     Author writing the content using this shortcode.
     * 
     * @return  Integer     $room_id    ID of the room, given that the author may access it.
     * 
     */
	private function find_room_id_by_token($token, $author) {
		// new way of creating meeting ID
		if (substr($token, 0, 8) == 'meeting-') {
			$room_id = substr($token, 8);
            $room = get_post($room_id);
            if ($room !== false && $room->post_type == 'bbb-room' && 
                (user_can($author, 'edit_others_bbb_rooms') || $room->author == $author)) {
				return $room->ID;
			} else {
				return 0;
			}
		} else {
			// look for the meeting ID in the post meta of the room
			$args = array(
				'post_type' => 'bbb-room',
				'fields' => 'ids',
				'no_found_rows' => true,
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'bbb-room-token',
						'value' => $token
					)
				)
            );

            // hide rooms if author does not have permission
            if ( ! user_can($author, 'edit_others_bbb_rooms')) {
                $args['author'] = $author;
            }

			$query = new WP_Query($args);
			if ($query->posts) {
				foreach($query->posts as $key => $room_id) {
					return $room_id;
				}
			}
		}
		return 0;
    }
    
    /**
	 * Get recordings from recording helper.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Array		$room_ids			Room IDs to get recordings from.
     * 
     * @return  Array       $recordings         
	 */
	private function get_recordings($room_ids) {
		$recording_helper = new BigbluebuttonRecordingHelper();

		if (isset($_GET['order']) && isset($_GET['orderby'])) {
			$order = sanitize_text_field($_GET['order']);
			$orderby = sanitize_text_field($_GET['orderby']);
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability($room_ids, $order, $orderby);
		} else {
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability($room_ids);
		}
	}

}