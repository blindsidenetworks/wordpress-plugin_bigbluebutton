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
			'view' => __('View', 'bigbluebutton'),
			'hide' => __('Hide')
		);

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bigbluebutton-public.js', array('jquery'), $this->version, false);
		wp_localize_script($this->plugin_name, 'php_vars', $translations);
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

		if ($room_id === null || ! isset(get_post($room_id)->post_type) || get_post($room_id)->post_type != 'bbb-room') {
			return $content;
		}

		// add join form to post content
		$html_form = $this->get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code);
		$content .= $html_form;

		// add recordings list to post content if the room is recordable
		$room_can_record = get_post_meta($room_id, 'bbb-room-recordable', true);
		if ($room_can_record == 'true') {
			$recordings = BigbluebuttonApi::get_recordings($room_id);
			$html_recordings = $this->get_optional_recordings_view_as_string($room_id, $recordings);
			$content .= $html_recordings;
		}
		
		return $content;
	}
	
	/**
	 * Handle authenticated user joining room.
	 * 
	 * @since 	3.0.0
	 */
	public function bbb_user_join_room() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'join_room') {
			if (wp_verify_nonce($_POST['bbb_join_room_meta_nonce'], 'bbb_join_room_meta_nonce')) {
				$room_id = $_POST['room_id'];
				$user = wp_get_current_user();
				$entry_code = '';
				$username = $this->get_meeting_username($user);
				$moderator_code = strval(get_post_meta($room_id, 'bbb-room-moderator-code', true));
				$viewer_code = strval(get_post_meta($room_id, 'bbb-room-viewer-code', true));

				if (current_user_can('join_as_moderator_bbb_room') || $user->ID == get_post($room_id)->post_author) {
					$entry_code = $moderator_code;
				} else if (current_user_can('join_as_viewer_bbb_room')) {
					$entry_code = $viewer_code;
				} else if (current_user_can('join_with_access_code_bbb_room') && isset($_POST['bbb_meeting_access_code'])) {
					$entry_code = sanitize_text_field($_POST['bbb_meeting_access_code']);
					if ($entry_code != $moderator_code && $entry_code != $viewer_code) {
						wp_redirect(esc_url(add_query_arg('password_error', '1', get_post_permalink($room_id))));
						return;
					}
				} else {
					wp_die(_('You do not have permission to enter the room. Please request permission.', 'bigbluebutton'));
				}
				$join_url = BigbluebuttonAPI::get_join_meeting_url($room_id, $username, $entry_code);
				wp_redirect($join_url);
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Get user's name for the meeting.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	User	$user	User object.
	 */
	private function get_meeting_username($user) {
		$username = ($user && $user->display_name) ? $user->display_name : '';
		return $username;
	}

	private function get_join_form_as_string($room_id, $meta_nonce, $access_as_moderator, $access_as_viewer, $access_using_code) {
		ob_start();
		include('partials/bigbluebutton-join-display.php');
		$form = ob_get_contents();
		ob_end_clean();
		return $form;
	}

	private function get_optional_recordings_view_as_string($room_id, $recordings) {
		ob_start();
		$date_format = (get_option( 'date_format' ) ? get_option( 'date_format' ) : 'Y-m-d');
		include('partials/bigbluebutton-optional-recordings-display.php');
		$recordings = ob_get_contents();
		ob_end_clean();
		return $recordings;
	}
}
