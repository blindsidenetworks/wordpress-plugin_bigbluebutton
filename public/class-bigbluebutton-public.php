<?php

/**
 * The views of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 */

/**
 * The views of the plugin.
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
		if ( ! wp_style_is('fontawesome', 'enqueued') && ! wp_style_is('font-awesome', 'enqueued')) {
			wp_enqueue_style('fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css', array(), '4.2.0');
		}
	}

	/**
	 * Enqueue heartbeat API for viewers to wait for moderator to join the meeting.
	 * 
	 * @since	3.0.0
	 */
	public function enqueue_heartbeat() {
		if (get_query_var('wait_for_mod')) {
			wp_enqueue_script('heartbeat');
		}
	}

	/**
	 * Add query vars for conditions.
	 * 
	 * @since	3.0.0
	 */
	public function add_query_vars($vars) {
		$vars[] = 'wait_for_mod';
		return $vars;
	}
	/**
	 * Display join room button and recordings in the bbb-room post.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String	$content	Post content as string.
	 * @return	String	$content	Post content as string.
	 */
	public function bbb_room_content($content) {
		global $pagenow;

		if ($pagenow == 'edit.php' || $pagenow == 'post.php') {
			return $content;
		}

		$room_id = get_the_ID();
		// $meta_nonce = wp_create_nonce('bbb_join_room_meta_nonce');

		// only access the meeting using a code if there is no other way
		// $access_using_code = current_user_can('join_with_access_code_bbb_room');
		// $access_as_moderator = (current_user_can('join_as_moderator_bbb_room') || get_current_user_id() == get_post($room_id)->post_author);
		// $access_as_viewer = current_user_can('join_as_viewer_bbb_room');

		if ($room_id === null || $room_id === false || ! isset(get_post($room_id)->post_type) || 
			get_post($room_id)->post_type != 'bbb-room') {
			return $content;
		}

		$token = get_post_meta($room_id, 'bbb-room-token', true);
		// add join form to post content
		$content .= '[bigbluebutton token="' . $token . '"]';
		// $display_helper = new BigbluebuttonDisplayHelper(plugin_dir_path(__FILE__));
		// $html_form = $display_helper->get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code, true);
		// $content .= $html_form;

		// add recordings list to post content if the room is recordable
		$room_can_record = get_post_meta($room_id, 'bbb-room-recordable', true);
		// $manage_recordings = current_user_can('manage_bbb_room_recordings');
		// $view_extended_recording_formats = current_user_can('view_extended_bbb_room_recording_formats');

		if ($room_can_record == 'true') {
			$content .= '[bigbluebutton type="recording" token="' . $token . '"]';
			// $recordings = $this->get_recordings(array($room_id));
			// $html_recordings = $display_helper->get_collapsable_recordings_view_as_string($room_id, $recordings, $manage_recordings, $view_extended_recording_formats);
			// $content .= $html_recordings;
		}
		
		return $content;
	}

	/**
	 * Register bigbluebutton widget.
	 * 
	 * @since	3.0.0
	 */
	public function register_widget() {
		register_widget('Bigbluebutton_Public_Widget');
	}

	/**
	 * Get recordings from recording helper.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer		$room_id			Room ID to get recordings of.
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
