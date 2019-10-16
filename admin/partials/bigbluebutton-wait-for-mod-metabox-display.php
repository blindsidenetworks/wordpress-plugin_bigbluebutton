<?php if ( $existing_value != '' ) { ?>
	<label><?php esc_html_e( 'Wait for Moderator', 'bigbluebutton' ); ?>: <?php esc_html_e( $existing_value, 'bigbluebutton' ); ?></label>
<?php } else { ?>
	<label><?php esc_html_e( 'Wait for Moderator', 'bigbluebutton' ); ?>: </label>
	<input name="bbb-room-wait-for-moderator" type="checkbox" value="checked">
<?php } ?>
