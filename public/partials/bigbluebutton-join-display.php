<form id="joinroom" method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="validate">
    <input type="hidden" name="action" value="join_room">
    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
    <input type="hidden" id="bbb_join_room_meta_nonce" name="bbb_join_room_meta_nonce" value="<?php echo $meta_nonce; ?>">
    <?php if ( ! $access_as_moderator && ! $access_as_viewer && $access_using_code) { ?>
        <div>
            <label id="bbb_meeting_access_code_label"><?php esc_html_e('Access Code', 'bigbluebutton'); ?>: </label>
            <input type="text" name="bbb_meeting_access_code" size=20 aria-labelledby="bbb_meeting_access_code_label">
        </div>
        <?php if (isset($_REQUEST['password_error'])) { ?>
            <div class="bbb-error">
                <label><?php esc_html_e('The access code you have entered is incorrect. Please try again.', 'bigbluebutton') ?></label>
            </div>
        <?php } ?>
        <br>
    <?php } ?>
    <input class="bbb-button" type="submit" class="button button-primary" value="<?php esc_html_e('Join', 'bigbluebutton'); ?>">
</form>