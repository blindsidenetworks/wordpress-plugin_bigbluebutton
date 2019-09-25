<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      3.0.0
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/includes
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Bigbluebutton_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {
		if (defined('BIGBLUEBUTTON_VERSION')) {
			$this->version = BIGBLUEBUTTON_VERSION;
		} else {
			$this->version = '3.0.0';
		}
		$this->plugin_name = 'bigbluebutton';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Bigbluebutton_Loader. Orchestrates the hooks of the plugin.
	 * - Bigbluebutton_i18n. Defines internationalization functionality.
	 * - Bigbluebutton_Admin. Defines all hooks for the admin area.
	 * - Bigbluebutton_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bigbluebutton-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bigbluebutton-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-bigbluebutton-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-bigbluebutton-public.php';

		/**
		 * Bigbluebutton API
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bigbluebutton-api.php';

		$this->loader = new Bigbluebutton_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Bigbluebutton_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Bigbluebutton_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Bigbluebutton_Admin($this->get_plugin_name(), $this->get_version());

		// register bbb-rooms and custom fields
		$this->loader->add_action('init', $plugin_admin, 'bbb_room_as_post_type');
		$this->loader->add_action('init', $plugin_admin, 'bbb_room_category_as_taxonomy_type');

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// create admin menu
		$this->loader->add_action('admin_menu', $plugin_admin, 'create_admin_menu');
		$this->loader->add_filter('parent_file', $plugin_admin, 'bbb_set_current_menu');

		// add room metadata hooks
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'register_room_code_metaboxes');
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'register_record_room_metabox');
		$this->loader->add_action('save_post', $plugin_admin, 'save_room');

		// show custom fields in rooms table
		$this->loader->add_action('manage_posts_custom_column', $plugin_admin, 'bbb_room_custom_columns', 10, 2);
		$this->loader->add_filter('manage_bbb-room_posts_columns', $plugin_admin, 'add_custom_room_column_to_list');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Bigbluebutton_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// display join room form/button
		$this->loader->add_filter('the_content', $plugin_public, 'bbb_room_join_form_content');

		// join room
		$this->loader->add_action('admin_post_join_room', $plugin_public, 'bbb_user_join_room');
		$this->loader->add_action('admin_post_nopriv_join_room', $plugin_public, 'bbb_user_join_room');

		// manage recording actions
		$this->loader->add_action('wp_ajax_set_bbb_recording_publish_state', $plugin_public, 'set_bbb_recording_publish_state');
		$this->loader->add_action('wp_ajax_nopriv_set_bbb_recording_publish_state', $plugin_public, 'set_bbb_recording_publish_state');
		$this->loader->add_action('wp_ajax_trash_bbb_recording', $plugin_public, 'trash_bbb_recording');
		$this->loader->add_action('wp_ajax_nopriv_trash_bbb_recording', $plugin_public, 'trash_bbb_recording');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     3.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     3.0.0
	 * @return    Bigbluebutton_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
