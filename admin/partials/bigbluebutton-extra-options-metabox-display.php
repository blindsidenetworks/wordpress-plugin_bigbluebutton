<div id="duration" class="extra-options-item">
	<label><?php echo $duration_label; ?></label>
	<input name="bbb-room-duration" type="text" value="<?php echo $duration_value ? $duration_value: 0; ?>"><small> (<?php echo __( 'Duration Msg', 'bigbluebutton'); ?>)</small>
</div>

<div id="guestPolicy" class="extra-options-item">
	<label><?php echo $guestPolicy_label; ?></label>
	<?php echo $guestPolicy_select; ?>
</div>

