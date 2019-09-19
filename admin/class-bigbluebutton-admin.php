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
				'show_ui' => false,
				'labels' => array( 
					'name' => __('All Rooms', 'bigbluebutton'),
					'add_new' => __('Add New', 'bigbluebutton'),
					'add_new_item' => __('Add New Room', 'bigbluebutton'),
					'edit_item' => __('Edit Room', 'bigbluebutton')),
				'description' => __('BBB room description.', 'bigbluebutton'),
				'taxonomies' => array('bbb-room-category'),
				'supports' => array('title', 'editor'),
				'rewrite' => array('slug' => 'bbb-room')
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
			'bbb-room',
			array(
				'labels' => array(
					'name' => __('Categories', 'bigbluebutton'),
					'singular_name' => __('Category', 'bigbluebutton'),
				),
				'description' => __('BBB room category description.', 'bigbluebutton'),
				'hierarchical' => true,
			)
		);
	}

	/**
	 * Add Rooms as its own menu item on the admin page.
	 * 
	 * @since	3.0.0
	 */
	public function create_admin_menu() {
		add_menu_page(__('Rooms', 'bigbluebutton'), __('Rooms', 'bigbluebutton'), 'manage_options', 'rooms-list', 
		array($this, 'display_rooms_list'), 'dashicons-video-alt2');
		add_submenu_page('rooms-list', __('Rooms', 'bigbluebutton'), __('New Room', 'bigbluebutton'), 'manage_options', 
		'new-room', array($this, 'display_new_room_page'));
		add_submenu_page('rooms-list', __('Categories'), __('Categories'), 'manage_options',
		'room-categories', array($this, 'display_categories_page'));
		add_submenu_page('rooms-list', __('Rooms', 'bigbluebutton'), __('Settings'), 'manage_options', 
		'rooms-server-settings', array($this, 'display_room_server_settings'));
	}

	/**
	 * Get all rooms.
	 * 
	 * @since	3.0.0
	 * 
	 * @return	WP_Query	$rooms	The list of rooms.
	 */
	public function get_rooms() {
		$args = array('post_type' => 'bbb-room', 'posts_per_page' => 20);
		$rooms = new WP_Query($args); // contains list of rooms
		return $rooms;
	}

	/**
	 * Get the list of all possible categories a room can be assigned to.
	 * 
	 * @since    3.0.0
	 * 
	 * @param	Integer	$parent 		Item ID of the category that we are getting children and subchildren of
	 * @param	Integer	$depth 			Number of ancestors of the parent category so far
	 * @return  Array	$categories 	2D array of categories under parent category
	 */
	public function get_categories($parent = 0, $depth = 0) {
		$children = $this->get_category_children($parent);

		$categories = array();
		if (count($children) > 0) {
			foreach ($children as $child) {
				$child->depth_level = $depth;
				if (empty($categories)) {
					$categories = array($child);
				} else {
					array_push($categories, $child);
				}
				
				$categories = array_merge($categories, $this->get_categories($child->term_id, $depth + 1));
			}
			return $categories;
		} else {
			return array();
		}
	}

	/**
	 * Get the categories under the current category.
	 * 
	 * @since	3.0.0
	 * 
	 * @param 	Integer	$parent 	Item ID of the parent category.
	 * @return	Array	$children	Immediate category children of the parent.
	 */
	public function get_category_children($parent = 0) {
		$args = array( 
			'hide_empty' => 0,
			'hide_if_empty' => false,
			'taxonomy' => 'bbb-room-category',
			'orderby' => 'name',
			'parent' => $parent,
			'hierarchical' => true,
		);

		$children = get_categories($args); // wordpress native get categories function
		
		return $children;
	}

	/**
	 * Generate default code for moderators/viewers to enter a room.
	 * 
	 * @since	3.0.0
	 * 
	 * @return	String	$default_code	A default entry code into a bigbluebutton meeting.
	 */
	public function generate_random_code() {
		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$default_code = substr(str_shuffle($permitted_chars), 0, 10);
		return $default_code;
	}

	/**
	 * Display rooms list view page.
	 * 
	 * @since	3.0.0
	 */
	public function display_rooms_list() {
		$this->check_delete_room_requests();
		if ( ! $this->edit_room()) {
			$loop = $this->get_rooms();
			$edit_room_nonce = wp_create_nonce('bbb_can_edit_room_meta_nonce');
			$delete_room_nonce = wp_create_nonce('bbb_can_delete_room_meta_nonce');
			require_once('partials/bigbluebutton-all-rooms-display.php');	
		}
	}

	/**
	 * Delete rooms if requested.
	 * 
	 * @since	3.0.0
	 */
	private function check_delete_room_requests() {
		if ( ! empty($_REQUEST['action']) && $_REQUEST['action'] == 'trash') {
			if (wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'bbb_can_delete_room_meta_nonce')) {
				$post_id = intval($_REQUEST['post']);
				if (get_post_status($post_id)) {
					$post = get_post($post_id);
					$post->post_type = 'bbb-room';
					wp_delete_post($post_id);
				}
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Send information to populate edit room form.
	 * 
	 * @since	3.0.0
	 */
	private function edit_room() {
		if ( ! empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
			if (wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'bbb_can_edit_room_meta_nonce')) {
				$post_id = intval($_REQUEST['post']);

				$title = get_the_title($post_id);
				$description = apply_filters('the_content', get_post_field('post_content', $post_id));
				$url = get_permalink($post_id);
				$moderator_code = get_post_meta($post_id, 'bbb-room-moderator-code', true);
				$viewer_code = get_post_meta($post_id, 'bbb-room-viewer-code', true);

				$categories = $this->get_categories();
				$selected_category_ids = wp_list_pluck(wp_get_post_terms($post_id, 'bbb-room-category'), 'term_id');

				$meta_nonce = wp_create_nonce('bbb_edit_room_meta_nonce');
				$delete_room_nonce = wp_create_nonce('bbb_can_delete_room_meta_nonce');
				require_once('partials/bigbluebutton-edit-room-display.php');
				return true;
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
		return false;
	}

	/**
	 * Display new room page.
	 * 
	 * @since	3.0.0
	 */
	public function display_new_room_page() {
		$url = get_site_url() . '/bbb-room';
		$user_id = wp_get_current_user()->ID;
		$post_id = wp_insert_post(array(
			'post_type' => 'bbb-room',
			'post_title' => 'Auto Draft',
			'post_status' => 'auto-draft'
		));
		$moderator_code = $this->generate_random_code();
		$viewer_code = $this->generate_random_code();
		$categories = $this->get_categories();
		$meta_nonce = wp_create_nonce('bbb_create_room_meta_nonce');
		require_once('partials/bigbluebutton-new-room-display.php');
	}

	/**
	 * Create new room.
	 * 
	 * @since	3.0.0
	 */
	public function create_room() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'create_room') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_create_room_meta_nonce']), 'bbb_create_room_meta_nonce')) {
				$post_id = intval($_POST['post_id']);
				$title = sanitize_text_field($_POST['post_title']);
				$description = apply_filters('the_content', $_POST['bbb-room-description']);
				$categories = (isset($_POST['tax_input']) && isset($_POST['tax_input']['bbb-room-category'])) ? (array) $_POST['tax_input']['bbb-room-category'] : array();
				$author = intval($_POST['post_author']);
				$slug = sanitize_title($_POST['slug']);
				$moderator_code = sanitize_text_field($_POST['bbb-moderator-code']);
				$viewer_code = sanitize_text_field($_POST['bbb-viewer-code']);

				wp_update_post(array(
					'ID' => $post_id,
					'post_title' => $title,
					'post_content' => $description,
					'author' => $author,
					'post_status' => 'publish',
					'post_name' => $slug
				));

				// link to categories
				wp_set_post_terms($post_id, $categories, 'bbb-room-category', false);

				// add room codes to postmeta data
				update_post_meta($post_id, 'bbb-room-moderator-code', $moderator_code);
				update_post_meta($post_id, 'bbb-room-viewer-code', $viewer_code);

				BigblueButtonApi::create_meeting($post_id);

				wp_redirect(wp_get_referer());
				return $post_id;
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Save the changes made to the room.
	 * 
	 * @since	3.0.0
	 */
	public function save_room_edits() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'edit_room') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_edit_room_meta_nonce']), 'bbb_edit_room_meta_nonce')) {
				$post_id = intval($_POST['post_id']);
				$title = sanitize_text_field($_POST['post_title']);
				$description = apply_filters('the_content', $_POST['bbb-room-description']);
				$categories = (isset($_POST['tax_input']) && isset($_POST['tax_input']['bbb-room-category'])) ? (array) $_POST['tax_input']['bbb-room-category'] : array();
				$author = sanitize_key($_POST['post_author']);

				wp_update_post(array(
					'ID' => $post_id,
					'post_title' => $title,
					'post_content' => $description,
					'author' => $author,
					'post_status' => 'publish'
				));

				// link to categories
				wp_set_post_terms($post_id, $categories, 'bbb-room-category', false);

				wp_redirect(wp_get_referer());
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Display room categories page.
	 * 
	 * @since	3.0.0
	 */
	public function display_categories_page() {
		$this->check_delete_category_requests();
		if ( ! $this->edit_category()) {
			$base_url = get_site_url() . '/bbb-room-category';
			$meta_nonce = wp_create_nonce('bbb_room_add_category_meta_nonce');
			$edit_categories_nonce = wp_create_nonce('bbb_can_edit_category_meta_nonce');
			$delete_categories_nonce = wp_create_nonce('bbb_can_delete_category_meta_nonce');
			$categories = $this->get_categories(0);
			require_once 'partials/bigbluebutton-room-categories-display.php';
		}
	}

	/**
	 * Delete categories if the request has been made.
	 * 
	 * @since 	3.0.0
	 */
	private function check_delete_category_requests() {
		if ( ! empty($_REQUEST['action']) && $_REQUEST['action'] == 'trash') {
			if (wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'bbb_can_delete_category_meta_nonce')) {
				$term_id = sanitize_key($_REQUEST['category']);
				if (term_exists((int) $term_id, 'bbb-room-category') !== null) {
					wp_delete_term((int) $term_id, 'bbb-room-category');
				}
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Display edit category page.
	 * 
	 * @since	3.0.0
	 */
	public function edit_category() {
		if ( ! empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
			if (wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'bbb_can_edit_category_meta_nonce')) {
				$category_id = intval($_REQUEST['category']);

				$category = get_term($category_id, 'bbb-room-category');
				if (is_wp_error($category)) {
					return false;
				}
				$name = $category->name;
				$slug = $category->slug;
				$description = apply_filters('the_content', $category->description);
				$parent = $category->parent;

				$meta_nonce = wp_create_nonce('bbb_room_edit_category_meta_nonce');
				require_once('partials/bigbluebutton-edit-category-display.php');
				return true;
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
		return false;
	}

	/**
	 * Save changes made to existing category.
	 * 
	 * @since	3.0.0
	 */
	public function save_category_edits() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'edit_category') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_room_edit_category_meta_nonce']), 'bbb_room_edit_category_meta_nonce')) {

				$category_id = intval($_POST['tag_ID']);
				$name = sanitize_text_field($_POST['name']);
				$slug = sanitize_title($_POST['slug']);
				$parent = sanitize_text_field($_POST['parent']);
				$description = apply_filters('the_content', $_POST['description']);

				$category_args = array(
					'name' => $name,
					'description' => $description,
					'parent' => $parent,
					'slug' => $slug
				);

				wp_update_term($category_id, 'bbb-room-category', $category_args);
				wp_redirect(wp_get_referer());
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
	}

	/**
	 * Submit data for new category.
	 * 
	 * @since	3.0.0
	 */
	public function create_category() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'create_category') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_room_add_category_meta_nonce']), 'bbb_room_add_category_meta_nonce')) {

				$name = sanitize_text_field($_POST['tag-name']);
				$slug = sanitize_title($_POST['slug']);
				$parent = intval($_POST['parent']);
				$description = apply_filters('the_content', $_POST['description']);

				$category_args = array(
					'description' => $description,
					'parent' => $parent,
					'slug' => $slug
				);
				wp_insert_term($name, 'bbb-room-category', $category_args);
				wp_redirect(wp_get_referer());
			} else {
				wp_die(_('The form has expired or is invalid. Please try again.', 'bigbluebutton'));
			}
		}
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
	 * @return	Boolean	true|false	If the room servers have been changed or not.
	 */
	private function room_server_settings_change() {
		if ( ! empty($_POST['action']) && $_POST['action'] == 'bbb_general_settings') {
			if (wp_verify_nonce(sanitize_text_field($_POST['bbb_edit_server_settings_meta_nonce']), 'bbb_edit_server_settings_meta_nonce')) {

				$bbb_url = sanitize_text_field($_POST['bbb_url']);
				$bbb_salt = sanitize_text_field($_POST['bbb_salt']);
			
				update_option('bigbluebutton_endpoint_url', $bbb_url);
				update_option('bigbluebutton_salt', $bbb_salt);

				return true;
			}
		}
		return false;
	}
}
