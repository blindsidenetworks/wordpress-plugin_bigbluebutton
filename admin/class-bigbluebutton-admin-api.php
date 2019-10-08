<?php

/**
 * Handle the majority of Bigbluebutton API calls to admin.
 * 
 * @since	3.0.0
 */
class Bigbluebutton_Admin_Api {
    
    /**
	 * Save custom post meta to the room.
	 * 
	 * @since	3.0.0
	 * 
	 * @param	Integer		$post_id	Post ID of the new room.
	 * @return	Integer		$post_id	Post ID of the new room.
	 */
	public function save_room($post_id) {

		if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
			return $post_id;
		}
        
		if ($this->can_save_room()) {
			$moderator_code = sanitize_text_field($_POST['bbb-moderator-code']);
			$viewer_code = sanitize_text_field($_POST['bbb-viewer-code']);
			$recordable = (array_key_exists('bbb-room-recordable', $_POST) && 
				sanitize_text_field($_POST['bbb-room-recordable']) == 'checked');
			$wait_for_mod = (sanitize_text_field($_POST['bbb-room-wait-for-moderator']) == 'checked');

			// add room codes to postmeta data
			update_post_meta($post_id, 'bbb-room-moderator-code', $moderator_code);
			update_post_meta($post_id, 'bbb-room-viewer-code', $viewer_code);
			update_post_meta($post_id, 'bbb-room-token', 'meeting' . $post_id);

			// update room recordable value
			update_post_meta($post_id, 'bbb-room-recordable', ($recordable ? 'true' : 'false'));
			update_post_meta($post_id, 'bbb-room-wait-for-moderator', ($wait_for_mod ? 'true' : 'false'));
			
		} else {
			return $post_id;
		}
    }
    
    /**
	 * Helper function to check if metadata has been submitted with correct nonces.
	 * 
	 * @since 3.0.0
	 */
	private function can_save_room() {
		return (isset($_POST['bbb-moderator-code']) &&
			isset($_POST['bbb-viewer-code']) &&
			isset($_POST['bbb-room-moderator-code-nonce']) && 
			wp_verify_nonce($_POST['bbb-room-moderator-code-nonce'], 'bbb-room-moderator-code-nonce') &&
			isset($_POST['bbb-room-viewer-code-nonce']) && 
			wp_verify_nonce($_POST['bbb-room-viewer-code-nonce'], 'bbb-room-viewer-code-nonce') &&
			isset($_POST['bbb-room-wait-for-moderator-nonce']) &&
			wp_verify_nonce($_POST['bbb-room-wait-for-moderator-nonce'], 'bbb-room-wait-for-moderator-nonce') &&
			( ! current_user_can('create_recordable_bbb_room') || 
				(isset($_POST['bbb-room-recordable-nonce']) &&
				wp_verify_nonce($_POST['bbb-room-recordable-nonce'], 'bbb-room-recordable-nonce')))
			);
	}
}