<?php
/**
 * The shortcode for the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 */

/**
 * The shortcode for the plugin.
 *
 * Registers the shortcode and handles displaying the shortcode.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class Bigbluebutton_Public_Shortcode {

	/**
	 * Register bigbluebutton shortcodes.
	 *
	 * @since   3.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'bigbluebutton', array( $this, 'display_bigbluebutton_shortcode' ) );
		add_shortcode( 'bigbluebutton_recordings', array( $this, 'display_bigbluebutton_old_recordings_shortcode' ) );
	}

	/**
	 * Handle shortcode attributes.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array  $atts       Parameters in the shortcode.
	 * @param   String $content    Content of the shortcode.
	 *
	 * @return  String $content    Content of the shortcode with rooms and recordings.
	 */
	public function display_bigbluebutton_shortcode( $atts = [], $content = null ) {
		global $pagenow;
		$type           = 'room';
		$author         = (int) get_the_author_meta( 'ID' );
		$display_helper = new Bigbluebutton_Display_Helper( plugin_dir_path( __FILE__ ) );
		$tokens_string  = '';
		$list_tokens    = false;

		if ( 'edit.php' == $pagenow || 'post.php' == $pagenow || 'post-new.php' == $pagenow ) {
			return $content;
		}

		foreach ( $atts as $key => $param ) {
			if ( 'type' == $key && 'recording' == $param ) {
				$type = 'recording';
			} elseif ( 'token' == $key ) {
				if ( 'token' == substr( $param, 0, 5 ) ) {
					$param = substr( $param, 5 );
				}
				$tokens_string = $param;
				$list_tokens = true;
			} elseif ( $list_tokens ) {
				if ( 'token' == substr( $param, 0, 5 ) ) {
					$param = substr( $param, 5 );
				}
				$tokens_string .= ',' . $param;
			}
		}

		if ( 'room' == $type ) {
			$content .= Bigbluebutton_Tokens_Helper::join_form_from_tokens_string( $display_helper, $tokens_string, $author );
		} elseif ( 'recording' == $type ) {
			$content .= Bigbluebutton_Tokens_Helper::recordings_table_from_tokens_string( $display_helper, $tokens_string, $author );
		}
		return $content;
	}

	/**
	 * Shows recordings for the old recordings shortcode format.
	 *
	 * @since   3.0.0
	 * @param   Array  $atts       Parameters in the shortcode.
	 * @param   String $content    Content of the shortcode.
	 *
	 * @return  String $content    Content of the shortcode with recordings.
	 */
	public function display_bigbluebutton_old_recordings_shortcode( $atts = [], $content = null ) {
		$atts['type'] = 'recording';
		return $this->display_bigbluebutton_shortcode( $atts, $content );
	}
}
