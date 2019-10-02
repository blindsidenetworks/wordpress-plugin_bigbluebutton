<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/admin
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Admin {

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
	 * @since	3.0.0
	 * @param	String    $plugin_name       The name of this plugin.
	 * @param	String    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/bigbluebutton-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bigbluebutton-admin.js', array('jquery'), $this->version, false);

	}

	/**
	 * Register room as custom post.
	 * 
	 * @since	3.0.0
	 */
	public function bbb_room_as_post_type() {
		register_post_type('bbb-room',
			array(
				'public' => true,
				'show_ui' => true,
				'labels' => array( 
					'name' => __('Rooms', 'bigbluebutton'),
					'add_new' => __('Add New', 'bigbluebutton'),
					'add_new_item' => __('Add New Room', 'bigbluebutton'),
					'edit_item' => __('Edit Room', 'bigbluebutton'),
				),
				'taxonomies' => array('bbb-room-category'),
				'capability_type' => 'bbb_room',
				'has_archive' => true,
				'supports' => array('title', 'editor'),
				'rewrite' => array('slug' => 'bbb-room'),
				'show_in_menu' => 'bbb_room',
				'map_meta_cap' => true,
				// enables block editing in the rooms editor
				'show_in_rest' => true,
				'supports' => array('editor')
			)
		);
	}

	/**
	 * Register category as custom taxonomy.
	 * 
	 * @since	3.0.0
	 */
	public function bbb_room_category_as_taxonomy_type() {
		register_taxonomy('bbb-room-category',
			array('bbb-room'),
			array(
				'labels' => array(
					'name' => __('Categories'),
					'singular_name' => __('Category'),
				),
				'hierarchical' => true,
				'query_var'    => true,
				'show_in_ui' => true,
				'show_in_menu' => 'bbb_room'
			)
		);
	}

	/**
	 * Create moderator and viewer code metaboxes on room creation and edit.
	 * 
	 * @since	3.0.0
	 */
	public function register_room_code_metaboxes() {
		add_meta_box('bbb-moderator-code', __('Moderator Code', 'bigbluebutton'), array($this, 'display_moderator_code_metabox'), 'bbb-room');
		add_meta_box('bbb-viewer-code', __('Viewer Code', 'bigbluebutton'), array($this, 'display_viewer_code_metabox'), 'bbb-room');
	}

	/**
	 * Show recordable option in room creation to users who have the corresponding capability.
	 * 
	 * @since	3.0.0
	 */
	public function register_record_room_metabox() {
		if (current_user_can('create_recordable_bbb_room')) {
			add_meta_box('bbb-room-recordable', __('Recordable', 'bigbluebutton'), array($this, 'display_allow_record_metabox'), 'bbb-room');
		}
	}

	/**
	 * Display moderator code metabox.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Object	$object		The object that has the room ID.
	 */
	public function display_moderator_code_metabox($object) {
		$entry_code = $this->generate_random_code();
		$entry_code_label = __('Moderator Code', 'bigbluebutton');
		$entry_code_name = 'bbb-moderator-code';
		$existing_value = get_post_meta($object->ID, 'bbb-room-moderator-code', true);
		wp_nonce_field('bbb-room-moderator-code-nonce', 'bbb-room-moderator-code-nonce');
		require('partials/bigbluebutton-room-code-metabox-display.php');
	}

	/**
	 * Display viewer code metabox.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Object	$object		The object that has the room ID.
	 */
	public function display_viewer_code_metabox($object) {
		$entry_code = $this->generate_random_code();
		$entry_code_label = __('Viewer Code', 'bigbluebutton');
		$entry_code_name = 'bbb-viewer-code';
		$existing_value = get_post_meta($object->ID, 'bbb-room-viewer-code', true);
		wp_nonce_field('bbb-room-viewer-code-nonce', 'bbb-room-viewer-code-nonce');
		require('partials/bigbluebutton-room-code-metabox-display.php');
	}

	/**
	 * Display recordable metabox.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Object	$object		The object that has the room ID.
	 */
	public function display_allow_record_metabox($object) {
		$existing_value = get_post_meta($object->ID, 'bbb-room-recordable', true);

		wp_nonce_field('bbb-room-recordable-nonce', 'bbb-room-recordable-nonce');
		require('partials/bigbluebutton-recordable-metabox-display.php');
	}

	/**
	 * Add Rooms as its own menu item on the admin page.
	 * 
	 * @since	3.0.0
	 */
	public function create_admin_menu() {
		add_menu_page(__('Rooms', 'bigbluebutton'), __('Rooms', 'bigbluebutton'), 'view_bbb_room_list', 'bbb_room', 
			'', 'dashicons-video-alt2');

		add_submenu_page('bbb_room', __('Rooms', 'bigbluebutton'), __('Categories'), 'view_bbb_room_list', 
			'edit-tags.php?taxonomy=bbb-room-category', '');

		add_submenu_page('bbb_room', __('Rooms', 'bigbluebutton'), __('Settings'), 'view_bbb_room_list', 
			'bbb-room-server-settings', array($this, 'display_room_server_settings'));
	}

	/**
	 * Add filter to highlight custom menu category submenu.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	String	$parent_file	Current parent page that the user is on.
	 * @return	String	$parent_file	Custom menu slug.
	 */
	public function bbb_set_current_menu($parent_file) {
    	global $submenu_file, $current_screen, $pagenow;

		# Set the submenu as active/current while anywhere in your Custom Post Type
        if ($current_screen->taxonomy == 'bbb-room-category' && $pagenow == 'edit-tags.php') {
            $submenu_file = 'edit-tags.php?taxonomy=bbb-room-category';
			$parent_file = 'bbb_room';
        }
        return $parent_file;
	}

	/**
	 * Save custom post meta to the room.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer		$post_id	Post ID of the new room.
	 * @return	Integer		$post_id	Post ID of the new room.
	 */
	public function save_room($post_id) {

		if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
			return $post_id;
		}
        
		if ($this->can_save_room()) {
			$moderator_code = sanitize_text_field($_POST['bbb-moderator-code']);
			$viewer_code = sanitize_text_field($_POST['bbb-viewer-code']);
			$recordable = (array_key_exists('bbb-room-recordable', $_POST) && 
				sanitize_text_field($_POST['bbb-room-recordable']) == 'checked');

			// add room codes to postmeta data
			update_post_meta($post_id, 'bbb-room-moderator-code', $moderator_code);
			update_post_meta($post_id, 'bbb-room-viewer-code', $viewer_code);
			update_post_meta($post_id, 'bbb-room-token', 'meeting-' . $post_id);

			// update room recordable value
			if ($recordable) {
				update_post_meta($post_id, 'bbb-room-recordable', 'true');
			} else {
				update_post_meta($post_id, 'bbb-room-recordable', 'false');
			}
			
		} else {
			return $post_id;
		}
	}

	/**
	 * Helper function to check if metadata has been submitted with correct nonces.
	 * 
	 * @since 3.0.0
	 */
	private function can_save_room() {
		return (isset($_POST['bbb-moderator-code']) &&
			isset($_POST['bbb-viewer-code']) &&
			isset($_POST['bbb-room-moderator-code-nonce']) && 
			wp_verify_nonce($_POST['bbb-room-moderator-code-nonce'], 'bbb-room-moderator-code-nonce') &&
			isset($_POST['bbb-room-viewer-code-nonce']) && 
			wp_verify_nonce($_POST['bbb-room-viewer-code-nonce'], 'bbb-room-viewer-code-nonce') &&
			(!current_user_can('create_recordable_bbb_room') || 
				(isset($_POST['bbb-room-recordable-nonce']) &&
				wp_verify_nonce($_POST['bbb-room-recordable-nonce'], 'bbb-room-recordable-nonce')))
			);
	}

	/**
	 * Add custom room column headers to rooms list table. 
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Array	$columns	Array of existing column headers.
	 * @return	Array	$columns	Array of existing column headers and custom column headers.
	 */
	public function add_custom_room_column_to_list($columns) {
		$custom_columns = array(
			'category' => __('Category'), 
			'author' => __('Author'), 
			'permalink' => __('Permalink'), 
			'token' => __('Token', 'bigbluebutton'),
			'moderator-code' => __('Moderator Code', 'bigbluebutton'), 
			'viewer-code' => __('Viewer Code', 'bigbluebutton')
		);

		$columns = array_merge($columns, $custom_columns);

		return $columns;
	}

	/**
	 * Fill in custom column information on rooms list table. 
	 * 
	 * @since 3.0.0
	 * 
	 * @param	String	$column		Name of the column.
	 * @param	Integer	$post_id	Room ID of the current room.
	 */
	public function bbb_room_custom_columns($column, $post_id) {
		switch ($column) {
			case 'category' :
				$categories = wp_get_object_terms($post_id, 'bbb-room-category', array('fields' => 'names'));
				if (!is_wp_error($categories)) {
					echo implode(', ', wp_get_object_terms($post_id, 'bbb-room-category', array('fields' => 'names')));
				}
				break;
			case 'author' :
				echo get_the_author_meta('display_name', (int) get_post($post_id)->post_author);
				break;
			case 'permalink' :
				echo '<a>' . (get_permalink($post_id) ? get_permalink($post_id) : '') . '</a>';
				break;
			case 'token':
				echo (string) get_post_meta($post_id, 'bbb-room-token', true);
				break;
			case 'moderator-code' :
				echo (string) get_post_meta($post_id, 'bbb-room-moderator-code', true);
				break;
			case 'viewer-code' :
				echo (string) get_post_meta($post_id, 'bbb-room-viewer-code', true);
				break;
		}
	}

	/**
	 * Generate random alphanumeric string.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer	$length			Length of random string.
	 * @return	String	$default_code	The resulting random string.
	 */
	public function generate_random_code($length = 10) {
		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$default_code = substr(str_shuffle($permitted_chars), 0, $length);
		return $default_code;
	}

	/**
	 * Render the server settings page for plugin.
	 * 
	 * @since	3.0.0
	 */
	public function display_room_server_settings() {
		$change_success = $this->room_server_settings_change();
		$bbb_settings = $this->fetch_room_server_settings();
		$meta_nonce = wp_create_nonce('bbb_edit_server_settings_meta_nonce');
		require_once 'partials/bigbluebutton-settings-display.php';
	}

	/**
	 * Retrieve the room server settings.
	 * 
	 * @since 	3.0.0
	 * 
	 * @return	Array	$settings	Room server default and current settings.
	 */
	public function fetch_room_server_settings() {
		$settings = array(
			'bbb_url' => get_option('bigbluebutton_endpoint_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/'),
			'bbb_salt' => get_option('bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6'),
			'bbb_default_url' => 'http://test-install.blindsidenetworks.com/bigbluebutton/',
			'bbb_default_salt' => '8cd8ef52e8e101574e400365b55e11a6'
		);

		return $settings;
	}

	/**
	 * Check for room server settings change requests.
	 * 
	 * @since	3.0.0
	 * 
	 * @return	Integer	1|2|3	If the room servers have been changed or not.
	 * 							0 - failure
	 * 							1 - success
	 * 							2 - bad url format
	 */
	private function room_server_settings_change() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'bbb_general_settings') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_edit_server_settings_meta_nonce']), 'bbb_edit_server_settings_meta_nonce')) {

				$bbb_url = sanitize_text_field($_POST['bbb_url']);
				$bbb_salt = sanitize_text_field($_POST['bbb_salt']);
			
				$bbb_url .= (substr($bbb_url, -1) == '/' ? '' : '/');

				if (substr_compare($bbb_url, 'bigbluebutton/', strlen($bbb_url) - 14) !== 0) {
					return 2;
				}

				update_option('bigbluebutton_endpoint_url', $bbb_url);
				update_option('bigbluebutton_salt', $bbb_salt);

				return 1;
			}
		}
		return 0;
	}

	/**
	 * Generate admin notice for missing the font awesome plugin.
	 * 
	 * @since	3.0.0
	 */
	public function missing_font_awesome_admin_notice() {
		$bbb_admin_error_message = __(ucfirst($this->plugin_name) . " depends on the font awesome plugin. Please install and activate it.", 'bigbluebutton');
		require_once 'partials/bigbluebutton-error-admin-notice-display.php';
	}
}
