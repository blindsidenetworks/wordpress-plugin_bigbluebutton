<?php

/**
 * The public-facing permissions of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 */

/**
 * The public-facing permissions of the plugin.
 *
 * Answers the API calls made from public facing pages about recordings.
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/public
 * @author     Blindside Networks <contact@blindsidenetworks.com>
 */
class BigBlueButton_Permissions_Helper {

	/**
	 * Check if user can access room using type.
	 *
	 * @param  String $type         Type to check if user can access room as.
	 *
	 * @return Boolean $user_can    Whether the user can access room using that type.
	 */
	public static function user_has_bbb_cap( $type ) {
		$user_can = false;
		if ( is_user_logged_in() ) {
			$user_can = current_user_can( $type );
		} elseif ( get_role( 'anonymous' ) ) {
			$user_can = get_role( 'anonymous' )->has_cap( $type );
		}
		return $user_can;
	}
}
