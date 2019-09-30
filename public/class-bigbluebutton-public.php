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
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Public {

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
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bigbluebutton_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bigbluebutton_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/bigbluebutton-public.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bigbluebutton_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bigbluebutton_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$translations = array(
			'expand_recordings' => __('Expand recordings', 'bigbluebutton'),
			'collapse_recordings' => __('Collapse recordings', 'bigbluebutotn'),
			'edit' => __('Edit'),
			'published' => __('Published'),
			'unpublished' => __('Unpublished'),
			'protected' => __('Protected', 'bigbluebutton'),
			'unprotected' => __('Unprotected', 'bigbluebutton'),
			'ajax_url' => admin_url('admin-ajax.php')
		);

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bigbluebutton-public.js', array('jquery'), $this->version, false);
		wp_localize_script($this->plugin_name, 'php_vars', $translations);
	}

	/**
	 * Add font awesome icons if not already installed.
	 * 
	 * @since	3.0.0
	 */
	public function enqueue_font_awesome_icons() {
		if( ! wp_style_is('fontawesome', 'enqueued') && ! wp_style_is('font-awesome', 'enqueued')) {
			wp_enqueue_style('fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css', array(), '4.2.0');
		}
	}

	/**
	 * Display join room button in the bbb-room post.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String	$content	Post content as string.
	 * @return	String	$content	Post content as string.
	 */
	public function bbb_room_join_form_content($content) {
		global $pagenow;

		if ($pagenow == 'edit.php' || $pagenow == 'post.php') {
			return $content;
		}

		$room_id = get_the_ID();
		$meta_nonce = wp_create_nonce('bbb_join_room_meta_nonce');

		// only access the meeting using a code if there is no other way
		$access_using_code = current_user_can('join_with_access_code_bbb_room');
		$access_as_moderator = (current_user_can('join_as_moderator_bbb_room') || get_current_user_id() == get_post($room_id)->post_author);
		$access_as_viewer = current_user_can('join_as_viewer_bbb_room');

		if ($room_id === null || $room_id === false || ! isset(get_post($room_id)->post_type) || 
			get_post($room_id)->post_type != 'bbb-room') {
			return $content;
		}

		// add join form to post content
		$html_form = $this->get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code);
		$content .= $html_form;

		// add recordings list to post content if the room is recordable
		$room_can_record = get_post_meta($room_id, 'bbb-room-recordable', true);
		$manage_recordings = current_user_can('manage_bbb_room_recordings');
		$view_extended_recording_formats = current_user_can('view_extended_bbb_room_recording_formats');

		if ($room_can_record == 'true') {
			$recordings = $this->get_recordings($room_id);
			$sort_fields = $this->set_order_by_field();
			$html_recordings = $this->get_optional_recordings_view_as_string($room_id, $recordings, $sort_fields, $manage_recordings, $view_extended_recording_formats);
			$content .= $html_recordings;
		}
		
		return $content;
	}

	/**
	 * Get join meeting form as an HTML string.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer		$room_id				Post ID of the room.
	 * @param	String		$meta_nonce				Nonce for join meeting form.
	 * @param	Boolean		$access_as_moderator	Check for if the current user can enter meetings as a moderator.
	 * @param	Boolean		$access_as_viewer		Check for if the current user can enter meetings as a viewer.
	 * @param	Boolean		$access_using_code		Check for if the current user can enter meetings using an access code.
	 * 
	 * @return	String		$form					Join meeting form stored in a variable.
	 */
	private function get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code) {
		ob_start();
		include('partials/bigbluebutton-join-display.php');
		$form = ob_get_contents();
		ob_end_clean();
		return $form;
	}

	/**
	 * Get recordings with Show/Hide buttons as an HTML string.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer		$room_id							Post ID of the room.
	 * @param	Array		$recordings							List of recordings for the room.
	 * @param	Array		$sort_fields						Array of properties for the sort icons in the recordings table header.
	 * @param	Boolean		$manage_bbb_recordings				User capability to manage recordings.
	 * @param	Boolean		$view_extended_recording_formats	User capability to view extended recording formats.
	 * 
	 * @return	String		$recordings							Recordings table stored in a variable.
	 */
	private function get_optional_recordings_view_as_string($room_id, $recordings, $sort_fields, $manage_bbb_recordings, $view_extended_recording_formats) {
		$columns = 5;
		if ($manage_bbb_recordings) {
			$columns++;
		}
		ob_start();
		$meta_nonce = wp_create_nonce('bbb_manage_recordings_nonce');
		$date_format = (get_option('date_format') ? get_option('date_format') : 'Y-m-d');
		$default_bbb_recording_format = 'presentation';
		include('partials/bigbluebutton-optional-recordings-display.php');
		$recordings = ob_get_contents();
		ob_end_clean();
		return $recordings;
	}

	/**
	 * Get recordings from recording helper.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer							$room_id			Room ID to get recordings of.
	 */
	private function get_recordings($room_id) {
		$recording_helper = new BigbluebuttonRecordingHelper();

		if (isset($_GET['order']) && isset($_GET['orderby'])) {
			$order = sanitize_text_field($_GET['order']);
			$orderby = sanitize_text_field($_GET['orderby']);
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability($room_id, $order, $orderby);
		} else {
			return $recording_helper->get_filtered_and_ordered_recordings_based_on_capability($room_id);
		}
	}

	/**
	 * Create url and classes for new sorting indicators.
	 * Use big arrow to show currently sorted order and triangles to show potential sorting order.
	 * 
	 * @since	3.0.0
	 * 
	 * @return	Array	$custom_sort_fields		Array of sortable fields for recordings.
	 */
	private function set_order_by_field() {
		$sort_asc_classes = 'fa fa-sort-up bbb-header-icon';
		$sort_desc_classes = 'fa fa-sort-down bbb-header-icon';
		$custom_sort_fields = array('name' => NULL, 'description' => NULL, 'date' => NULL);

		if (isset($_GET['order']) && isset($_GET['orderby'])) {
			$new_direction = (sanitize_text_field($_GET['order']) == 'asc' ? 'desc' : 'asc');
			$new_sort_classes = ($new_direction == 'asc' ? $sort_desc_classes : $sort_asc_classes) . ' bbb-current-sort-icon';
			$selected_field = sanitize_text_field($_GET['orderby']);
		}

		foreach ($custom_sort_fields as $field => $values) {
			if (isset($new_direction) && isset($new_sort_classes) && isset($selected_field) && $field == $selected_field) {
				$custom_sort_fields[$field] = (object) array(
					'url' => '?orderby=' . $field . '&order=' . $new_direction,
					'classes' => $new_sort_classes,
					'header_classes' => 'bbb-column-header-highlight'
				);
			} else {
				$custom_sort_fields[$field] = (object) array(
					'url' => '?orderby=' . $field . '&order=asc',
					'classes' => $sort_asc_classes . ' bbb-hidden',
					'header_classes' => 'bbb-recordings-unselected-sortable-column'
				);
			}
		}

		return $custom_sort_fields;
	}
}
