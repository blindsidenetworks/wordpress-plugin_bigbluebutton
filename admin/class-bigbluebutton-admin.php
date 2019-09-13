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
	 * @since    3.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bigbluebutton-admin.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bigbluebutton-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add new admin page the BigBlueButton server settings
	 */
	public function room_server_settings() {
		add_menu_page( __('Rooms', 'bigbluebutton'), __('Rooms', 'bigbluebutton'), 'manage_options', 'rooms-server-settings', 
		array( $this, 'display_room_server_settings'), 'dashicons-video-alt2' );
	}

	/**
	 * Render the server settings page for plugin
	 */
	public function display_room_server_settings() {
		$change_success = $this->room_server_settings_change();
		$bbb_settings = $this->fetch_room_server_settings();
		include_once 'partials/bigbluebutton-admin-display.php';
	}

	/**
	 * Retrieve the 
	 */
	public function fetch_room_server_settings() {
		return array (
			'bbb_url' => get_option( 'bigbluebutton_endpoint_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' ),
			'bbb_salt' => get_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' ),
			'bbb_default_url' => 'http://test-install.blindsidenetworks.com/bigbluebutton/',
			'bbb_default_salt' => '8cd8ef52e8e101574e400365b55e11a6'
		);
	}

	private function room_server_settings_change() {
		if (!empty($_POST['action']) && $_POST['action'] == 'bbb_general_settings') {

			$bbb_url =  sanitize_text_field($_POST['bbb_url']);
			$bbb_salt =  sanitize_text_field($_POST['bbb_salt']);
		
			update_option( 'bigbluebutton_endpoint_url', $bbb_url );
			update_option( 'bigbluebutton_salt', $bbb_salt );

			return true;
		}
		return false;
	}
}
