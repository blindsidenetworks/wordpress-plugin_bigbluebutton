<label><?php echo $entry_pre_upload_presentation_label; ?>: </label>
<?php if ( $existing_value != '' ) { ?>
	<input name="<?php echo $entry_pre_upload_presentation_name; ?>" type="text" value="<?php echo $existing_value; ?>"><small> (<?php echo $defaultMsg; ?>)</small>
<?php } else { ?>
	<input name="<?php echo $entry_pre_upload_presentation_name; ?>" type="text"><small> (<?php echo $defaultMsg; ?>)</small>
<?php } ?>
