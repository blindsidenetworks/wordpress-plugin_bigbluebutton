<?php

/**
 * Create instance of bigbluebutton widget.
 * 
 * @since   3.0.0
 */
class Bigbluebutton_Public_Widget extends WP_Widget {
    
    /**
     * Construct widget.
     * 
     * @since   3.0.0
     */
    public function __construct() {
        parent::__construct(
            'Bigbluebutton',
            __('Rooms', 'bigbluebutton'),
            array(
                'customize_selective_refresh' => true,
                'description' => __('Displays a BigBlueButton login form.', 'bigbluebutton'),
                'panels_icon' => 'dashicons dashicons-video-alt2'
            )
        );
    }

    /**
     * Display the widget.
     * 
     * @param   Array      $args      List of default values stored for the widget.
     * @param   Array      $instance  List of custom values store for the widget.
     */
    public function widget($args, $instance) {
        extract($args);

        $select = isset($instance['select']) ? $instance['select'] : '';
        $author = isset($instance['author']) ? $instance['author'] : 0;
        $room_args = explode("; ", $select);

        $meta_nonce = wp_create_nonce('bbb_join_room_meta_nonce');
        $access_using_code = current_user_can('join_with_access_code_bbb_room');
		$access_as_moderator = current_user_can('join_as_moderator_bbb_room');
        $access_as_viewer = current_user_can('join_as_viewer_bbb_room');
        $display_helper = new BigbluebuttonDisplayHelper(plugin_dir_path(__FILE__));

        echo $before_widget;

        if ($room_args[0] == "CATEGORY") {
            $category_id = $room_args[1];
            $rooms = $this->find_rooms_by_category($category_id, $author);
            if (sizeof($rooms) > 0) {
                $html_form = $display_helper->get_join_form_as_string($rooms[0]->room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code);
                echo $display_helper->get_room_list_dropdown_as_string($rooms, $html_form);
            } else {
                echo "<p>" . esc_html__("There are no rooms in the selection.", "bigbluebutton") . "</p>";
            }
            
        } else if ($room_args[0] == "ROOM") {
            $room_id = $room_args[1];
            $room = get_post($room_id);
            if ($room === false || $room->post_type != 'bbb-room') {
                return;
            }
            if (!$access_as_moderator) {
                $access_as_moderator = ($room->author == get_current_user_id());
            }
            echo $display_helper->get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code);
        } else {
            echo "<p>" . esc_html__("There are no rooms in the selection.", "bigbluebutton") . "</p>";
        }
        
        echo $after_widget;
    }

    /**
     * Create widget form (for the admin panel).
     * 
     * @since   3.0.0
     * 
     * @param   Array   $instance   Existing widget values to show in the widget form.
     */
    public function form($instance) {
        $select_id = $this->get_field_id('select');
        $select_name = $this->get_field_name('select');
        
        $instance['select'] = isset($instance['select']) ? $instance['select'] : '';

        $bbb_room_categories = get_terms(array(
            'taxonomy' => 'bbb-room-category',
            'hide_empty' => false
        ));

        $bbb_rooms = array();

        $args = array(
            'post_type' => 'bbb-room',
            'no_found_rows' => true,
            'posts_per_page' => -1,
        );

        // hide rooms if author does not have permission
        if ( ! current_user_can('edit_others_bbb_rooms')) {
            $args['author'] = get_current_user_id();
        }

        $query = new WP_Query($args);
        if ($query->posts) {
            foreach($query->posts as $sql_room) {
                $room = (object) array (
                    'value' => $sql_room->ID,
                    'name' => $sql_room->post_title
                );
                $bbb_rooms[] = $room;
            }
        }

        include 'partials/bigbluebutton-create-widget-display.php';
    }
    
    /**
     * Update widget settings.
     * 
     * @since   3.0.0
     * 
     * @param   Array   $new_instance  New values for widget.
     * @param   Array   $old_instance  Previous values for widget.
     * 
     * @return  Array   $instance      Kept values for widget.
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['select'] = isset( $new_instance['select'] ) ? wp_strip_all_tags( $new_instance['select'] ) : '';
        $instance['author'] = isset( $old_instance['author'] ) ? $old_instance['author'] : get_current_user_id();
        return $instance;
    }

    /**
     * Get list of rooms based on category.
     * 
     * @since   3.0.0
     * 
     * @param   Integer     $category   ID of the room category to get associated rooms from.
     * @param   Integer     $author     Author writing the content using this widget.
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
					'field' => 'id',
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
}