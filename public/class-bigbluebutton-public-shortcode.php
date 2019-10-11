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
	 * Register bigbluebutton shortcode.
	 *
	 * @since   3.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'bigbluebutton', array( $this, 'display_bigbluebutton_shortcode' ) );
	}

	/**
	 * Handle shortcode attributes.
	 *
	 * @since   3.0.0
	 *
	 * @param   Array   $atts       Parameters in the shortcode.
	 * @param   String  $content    Content of the shortcode.
	 *
	 * @return  String  $content    Content of the shortcode with rooms and recordings.
	 */
	public function display_bigbluebutton_shortcode( $atts = [], $content = null ) {
		$type           = 'room';
		$author         = (int) get_the_author_meta( 'ID' );
		$display_helper = new Bigbluebutton_Display_Helper( plugin_dir_path( __FILE__ ) );
		$tokens_string  = '';

		foreach ( $atts as $key => $param ) {
			if ( $key == 'type' && 'recording' == $param ) {
				$type = 'recording';
			} elseif ( 'token' == $key ) {
				$tokens_string = $param;
			}
		}

		if ( 'room' == $type ) {
			$content .= Bigbluebutton_Tokens_Helper::join_form_from_tokens_string( $display_helper, $tokens_string, $author );
		} elseif ( 'recording' == $type ) {
			$content .= Bigbluebutton_Tokens_Helper::recordings_table_from_tokens_string( $display_helper, $tokens_string, $author );
		}
		return $content;
	}
}
