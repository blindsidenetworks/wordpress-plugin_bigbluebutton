<label><?php echo $entry_max_participants_label; ?>: </label>
<?php if ( $existing_value != '' ) { ?>
	<input name="<?php echo $entry_max_participants_name; ?>" type="text" value="<?php echo $existing_value; ?>"><small> (<?php echo $defaultMsg; ?>)</small>
<?php } else { ?>
	<input name="<?php echo $entry_max_participants_name; ?>" type="text" value="<?php echo $default_max_participants; ?>"><small> (<?php echo $defaultMsg; ?>)</small>
<?php } ?>
