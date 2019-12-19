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
	 * @since   3.0.0
	 * @param   String $plugin_name       The name of this plugin.
	 * @param   String $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

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

		$translations = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bigbluebutton-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'php_vars', $translations );
	}

	/**
	 * Add Rooms as its own menu item on the admin page.
	 *
	 * @since   3.0.0
	 */
	public function create_admin_menu() {
		add_menu_page(
			__( 'Rooms', 'bigbluebutton' ), __( 'Rooms', 'bigbluebutton' ), 'activate_plugins', 'bbb_room',
			'', 'dashicons-video-alt2'
		);

		if ( current_user_can( 'manage_categories' ) ) {
			add_submenu_page(
				'bbb_room', __( 'Rooms', 'bigbluebutton' ), __( 'Categories' ), 'activate_plugins',
				'edit-tags.php?taxonomy=bbb-room-category', ''
			);
		}

		add_submenu_page(
			'bbb_room', __( 'Rooms', 'bigbluebutton' ), __( 'Settings' ), 'activate_plugins',
			'bbb-room-server-settings', array( $this, 'display_room_server_settings' )
		);
	}

	/**
	 * Add filter to highlight custom menu category submenu.
	 *
	 * @since   3.0.0
	 *
	 * @param   String $parent_file    Current parent page that the user is on.
	 * @return  String $parent_file    Custom menu slug.
	 */
	public function bbb_set_current_menu( $parent_file ) {
		global $submenu_file, $current_screen, $pagenow;

		// Set the submenu as active/current while anywhere in your Custom Post Type.
		if ( 'bbb-room-category' == $current_screen->taxonomy && 'edit-tags.php' == $pagenow ) {
			$submenu_file = 'edit-tags.php?taxonomy=bbb-room-category';
			$parent_file  = 'bbb_room';
		}
		return $parent_file;
	}

	/**
	 * Add custom room column headers to rooms list table.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array $columns    Array of existing column headers.
	 * @return  Array $columns    Array of existing column headers and custom column headers.
	 */
	public function add_custom_room_column_to_list( $columns ) {
		$custom_columns = array(
			'category'       => __( 'Category' ),
			'permalink'      => __( 'Permalink' ),
			'token'          => __( 'Token', 'bigbluebutton' ),
			'moderator-code' => __( 'Moderator Code', 'bigbluebutton' ),
			'viewer-code'    => __( 'Viewer Code', 'bigbluebutton' ),
		);

		$columns = array_merge( $columns, $custom_columns );

		return $columns;
	}

	/**
	 * Fill in custom column information on rooms list table.
	 *
	 * @since 3.0.0
	 *
	 * @param   String  $column     Name of the column.
	 * @param   Integer $post_id    Room ID of the current room.
	 */
	public function bbb_room_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'category':
				$categories = wp_get_object_terms( $post_id, 'bbb-room-category', array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $categories ) ) {
					echo esc_attr( implode( ', ', $categories ) );
				}
				break;
			case 'permalink':
				$permalink = ( get_permalink( $post_id ) ? get_permalink( $post_id ) : '' );
				echo '<a href="' . esc_url( $permalink ) . '" target="_blank">' . esc_url( $permalink ) . '</a>';
				break;
			case 'token':
				if ( metadata_exists( 'post', $post_id, 'bbb-room-token' ) ) {
					$token = get_post_meta( $post_id, 'bbb-room-token', true );
				} else {
					$token = 'z' . esc_attr( $post_id );
				}
				echo esc_attr( $token );
				break;
			case 'moderator-code':
				echo esc_attr( get_post_meta( $post_id, 'bbb-room-moderator-code', true ) );
				break;
			case 'viewer-code':
				echo esc_attr( get_post_meta( $post_id, 'bbb-room-viewer-code', true ) );
				break;
		}
	}

	/**
	 * Render the server settings page for plugin.
	 *
	 * @since   3.0.0
	 */
	public function display_room_server_settings() {
		$change_success = $this->room_server_settings_change();
		$bbb_settings   = $this->fetch_room_server_settings();
		$meta_nonce     = wp_create_nonce( 'bbb_edit_server_settings_meta_nonce' );
		require_once 'partials/bigbluebutton-settings-display.php';
	}

	/**
	 * Retrieve the room server settings.
	 *
	 * @since   3.0.0
	 *
	 * @return  Array   $settings   Room server default and current settings.
	 */
	public function fetch_room_server_settings() {
		$settings = array(
			'bbb_url'          => get_option( 'bigbluebutton_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' ),
			'bbb_salt'         => get_option( 'bigbluebutton_salt', '8cd8ef52e8e101574e400365b55e11a6' ),
			'bbb_default_url'  => 'http://test-install.blindsidenetworks.com/bigbluebutton/',
			'bbb_default_salt' => '8cd8ef52e8e101574e400365b55e11a6',
		);

		return $settings;
	}

	/**
	 * Show information about new plugin updates.
	 *
	 * @since   1.4.6
	 *
	 * @param   Array  $current_plugin_metadata    The plugin metadata of the current version of the plugin.
	 * @param   Object $new_plugin_metadata        The plugin metadata of the new version of the plugin.
	 */
	public function bigbluebutton_show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata = null ) {
		if ( ! $new_plugin_metadata ) {
			$new_plugin_metadata = $this->bigbluebutton_update_metadata( $current_plugin_metadata['slug'] );
		}
		// Check "upgrade_notice".
		if ( isset( $new_plugin_metadata->upgrade_notice ) && strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) {
			echo '<div style="background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px"><strong>Important Upgrade Notice:</strong> ';
			echo esc_html( strip_tags( $new_plugin_metadata->upgrade_notice ) ), '</div>';
		}
	}

	/**
	 * Get information about the newest plugin version.
	 *
	 * @since   1.4.6
	 *
	 * @param   String $plugin_slug            The slug of the old plugin version.
	 * @return  Object $new_plugin_metadata    The metadata of the new plugin version.
	 */
	private function bigbluebutton_update_metadata( $plugin_slug ) {
		$plugin_updates = get_plugin_updates();
		foreach ( $plugin_updates as $update ) {
			if ( $update->update->slug === $plugin_slug ) {
				return $update->update;
			}
		}
	}

	/**
	 * Check for room server settings change requests.
	 *
	 * @since   3.0.0
	 *
	 * @return  Integer 1|2|3   If the room servers have been changed or not.
	 *                          0 - failure
	 *                          1 - success
	 *                          2 - bad url format
	 *                          3 - bad bigbluebutton settings configuration
	 */
	private function room_server_settings_change() {
		if ( ! empty( $_POST['action'] ) && 'bbb_general_settings' == $_POST['action'] && wp_verify_nonce( sanitize_text_field( $_POST['bbb_edit_server_settings_meta_nonce'] ), 'bbb_edit_server_settings_meta_nonce' ) ) {
			$bbb_url  = sanitize_text_field( $_POST['bbb_url'] );
			$bbb_salt = sanitize_text_field( $_POST['bbb_salt'] );

			$bbb_url .= ( substr( $bbb_url, -1 ) == '/' ? '' : '/' );

			if ( ! Bigbluebutton_Api::test_bigbluebutton_server( $bbb_url, $bbb_salt ) ) {
				return 3;
			}

			if ( substr_compare( $bbb_url, 'bigbluebutton/', strlen( $bbb_url ) - 14 ) !== 0 ) {
				return 2;
			}

			update_option( 'bigbluebutton_url', $bbb_url );
			update_option( 'bigbluebutton_salt', $bbb_salt );

			return 1;
		}
		return 0;
	}

	/**
	 * Generate missing heartbeat API if missing.
	 *
	 * @since   3.0.0
	 */
	public function check_for_heartbeat_script() {
		$bbb_warning_type = 'bbb-missing-heartbeat-api-notice';
		if ( ! wp_script_is( 'heartbeat', 'registered' ) && ! get_option( 'dismissed-' . $bbb_warning_type, false ) ) {
			$bbb_admin_warning_message = __( 'BigBlueButton works best with the heartbeat API enabled. Please enable it.', 'bigbluebutton' );
			$bbb_admin_notice_nonce    = wp_create_nonce( $bbb_warning_type );
			require 'partials/bigbluebutton-warning-admin-notice-display.php';
		}
	}

	/**
	 * Hide others rooms if user does not have permission to edit them.
	 *
	 * @since  3.0.0
	 *
	 * @param  Object $query   Query so far.
	 * @return Object $query   Query for rooms.
	 */
	public function filter_rooms_list( $query ) {
		global $pagenow;

		if ( 'edit.php' != $pagenow || ! $query->is_admin || 'bbb-room' != $query->query_vars['post_type'] ) {
			return $query;
		}

		if ( ! current_user_can( 'edit_others_bbb_rooms' ) ) {
			$query->set( 'author', get_current_user_id() );
		}
		return $query;
	}
}
