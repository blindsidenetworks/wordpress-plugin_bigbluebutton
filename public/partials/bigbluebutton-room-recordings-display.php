<div class="bbb-recording-display-block">
    <div id="bbb-recordings-display-<?php echo $room_id; ?>" class="bbb-recordings-display bbb-recordings-shortcode">
        <i class="fa fa-angle-down"></i>
        <p class="bbb-expandable-header"><?php esc_html_e("Recordings", 'bigbluebutton'); ?>
    </div>
    <?php echo $html_recordings; ?>
</div>