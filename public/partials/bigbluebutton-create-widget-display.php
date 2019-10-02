<div class="bbb-top-bottom-margin">
    <label for="<?php echo $select_id; ?>" class="bbb-width-30 bbb-inline-block"><?php esc_html_e('Select'); ?>:</label>
    <select id="<?php echo $select_id; ?>" name="<?php echo $select_name; ?>" class="bbb-width-60 bbb-inline-block">
        <optgroup label=<?php esc_html_e("Categories"); ?>>
            <?php foreach ($bbb_room_categories as $category) { ?>
                <option value="CATEGORY; <?php echo $category->term_taxonomy_id; ?>" <?php echo selected($instance['select'], "CATEGORY; " . $category->term_taxonomy_id, false); ?>><?php echo $category->name; ?></option>
            <?php } ?>
        </optgroup>
        <optgroup label="<?php esc_html_e("Rooms", "bigbluebutton"); ?>">
            <?php foreach ($bbb_rooms as $room) { ?>
                <option value="ROOM; <?php echo $room->value?>" <?php echo selected($instance['select'], "ROOM; " . $room->value, false); ?>><?php echo $room->name; ?></option>
            <?php } ?>
        </optgroup>
    </select>
</div>
