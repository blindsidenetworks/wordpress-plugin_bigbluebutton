<?php if ( '' !== $existing_value ) { ?>
	<label><?php esc_html_e( 'Recordable', 'bigbluebutton' ); ?>: <?php esc_html_e( $existing_value, 'bigbluebutton' ); ?></label>
<?php } else { ?>
	<label><?php esc_html_e( 'Recordable', 'bigbluebutton' ); ?>: <input name="bbb-room-recordable" type="checkbox" value="checked"></label>
<?php } ?>
