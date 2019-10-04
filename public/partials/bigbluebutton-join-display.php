<form id="joinroom" method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="validate">
    <input type="hidden" name="action" value="join_room">
    <input id="bbb_join_room_id" type="hidden" name="room_id" value="<?php echo $room_id; ?>">
    <input type="hidden" id="bbb_join_room_meta_nonce" name="bbb_join_room_meta_nonce" value="<?php echo $meta_nonce; ?>">
    <input type="hidden" name="REQUEST_URI" value="<?php echo $current_url; ?>">
    <?php if ( ! $access_as_moderator && ! $access_as_viewer && $access_using_code) { ?>
        <div id="bbb_join_with_password">
    <?php } else { ?>
        <div id="bbb_join_with_password" style="display:none;">
    <?php } ?>
            <label id="bbb_meeting_access_code_label"><?php esc_html_e('Access Code', 'bigbluebutton'); ?>: </label>
            <input type="text" name="bbb_meeting_access_code" size=20 aria-labelledby="bbb_meeting_access_code_label">
        </div>
        <?php if (isset($_REQUEST['password_error']) && $_REQUEST['room_id'] == $room_id) { ?>
            <div class="bbb-error">
                <label><?php esc_html_e('The access code you have entered is incorrect. Please try again.', 'bigbluebutton') ?></label>
            </div>
        <?php } ?>
    <br>
    <?php if (isset($_REQUEST['wait_for_mod']) && $_REQUEST['room_id'] == $room_id) { ?>
        <div>
            <label id="bbb-wait-for-mod-msg"
                data-room-id="<?php echo $room_id; ?>"
                <?php if (isset($_REQUEST['entry_code'])) { ?>
                    data-room-code="<?php echo $_REQUEST['entry_code']; ?>"
                <?php } ?>>
                <?php if ($heartbeat_available) { ?>
                    <?php esc_html_e('The meeting has not started yet. You will be automatically redirected to the meeting when it starts.', 'bigbluebutton'); ?>
                <?php } else { ?>
                    <?php esc_html_e('The meeting has not started yet. Please wait for a moderator to start the meeting before joining.', 'bigbluebutton'); ?>
                <?php } ?>
            </label>
        </div>
    <?php } ?>
    <input class="bbb-button" type="submit" class="button button-primary" value="<?php esc_html_e('Join', 'bigbluebutton'); ?>">
</form>