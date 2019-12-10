<div class="notice notice-warning is-dismissible bbb-warning-notice" data-notice="<?php esc_html_e( $bbb_warning_type ); ?>" data-nonce="<?php esc_html_e( $bbb_admin_notice_nonce ); ?>" >
	<p>
	<?php if ( isset( $bbb_action_link ) ) { ?>
		<a href="<?php echo $bbb_action_link; ?>" target="_blank"><?php esc_html_e( $bbb_admin_warning_message ); ?></a>
	<?php } else { ?>
		<?php esc_html_e( $bbb_admin_warning_message ); ?>
	<?php } ?>
	</p>
</div>
