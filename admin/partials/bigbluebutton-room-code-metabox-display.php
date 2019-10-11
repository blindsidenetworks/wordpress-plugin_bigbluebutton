<?php if ( $existing_value != '' ) { ?>
	<label><?php echo $entry_code_label; ?>: <?php echo $existing_value; ?></label>
<?php } else { ?>
	<label><?php echo $entry_code_label; ?>: </label>
	<input name="<?php echo $entry_code_name; ?>" type="text" value="<?php echo $entry_code; ?>">
<?php } ?>
