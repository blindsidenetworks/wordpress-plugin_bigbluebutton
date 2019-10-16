<div class="bbb-top-bottom-margin">
	<p>
		<label for="<?php echo $text_id; ?>" class="bbb-width-30 bbb-inline-block"><?php esc_html_e( 'Tokens (separated by comma)', 'bigbluebutton' ); ?>:</label>
		<input class="widefat" id=<?php echo esc_attr( $text_id ); ?> name="<?php echo esc_attr( $text_name ); ?>" type="text" value="<?php echo esc_attr( $text_value ); ?>" />
	</p>
</div>
