<form id="joinroom" method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="validate">
    <input type="hidden" name="action" value="join_room">
    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
    <input type="hidden" id="bbb_join_room_meta_nonce" name="bbb_join_room_meta_nonce" value="<?php echo $meta_nonce; ?>">
    <input type="submit" class="button button-primary" value="<?php esc_html_e('Join', 'bigbluebutton'); ?>">
</form>
