(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	/** global: php_vars */
	$( window ).load(function() {
		
		// display/hide recordings
		$("#bbb-recordings-display").click(function() {
			if ($("#bbb-recordings-list").is(":visible")) {
				$("#bbb-recordings-list").slideUp();
				$(this).text(php_vars.view);
			} else {
				$("#bbb-recordings-list").slideDown();
				$(this).text(php_vars.hide);
			}
		});
		
		// publish/unpublish recordings
		$(".bbb_published_recording").click(function() {
			let current_icon = $(this);
			let recordID = $(this).data('record-id');
			let nonce = $(this).data('meta-nonce');
			let curr_class, replace_class, curr_icon_class, replace_icon_class, title, value;

			if ($(this).hasClass("is_published")) {
				value = "false";
				curr_class = "is_published";
				replace_class = "not_published";
				curr_icon_class = "fa-eye";
				replace_icon_class = "fa-eye-slash";
				title = "unpublished";
			} else {
				value = "true";
				curr_class = "not_published";
				replace_class = "is_published";
				curr_icon_class = "fa-eye-slash";
				replace_icon_class = "fa-eye";
				title = "published";
			}

			let data = {
				"action": "set_bbb_recording_publish_state",
				"value" : value,
				"post_type": "POST",
				"meta_nonce": nonce,
				"record_id": recordID
			};
			
			jQuery.post(php_vars.ajax_url, data, function(response) {
				if (response['success']) {
					current_icon.removeClass(curr_icon_class).addClass(replace_icon_class);
					current_icon.attr('title', title);
					current_icon.removeClass(curr_class).addClass(replace_class);	
				}
			}, "json");

		});

		// delete recording
		$(".bbb_trash_recording").click(function() {
			let recordID = $(this).data('record-id');
			let nonce = $(this).data('meta-nonce');

			let data = {
				"action": "trash_bbb_recording",
				"post_type": "POST",
				"meta_nonce": nonce,
				"record_id": recordID
			};

			jQuery.post(php_vars.ajax_url, data, function(response) {
				if (response['success']) {
					$("#bbb-recording-" + recordID).remove();
					// if there are no recordings left, remove the table
					console.log($(".bbb-recording-row").length);
					if ($(".bbb-recording-row").length == 0) {
						$("#bbb-recordings-table").remove();
						$("#bbb-no-recordings-msg").show();
					}
				}
			}, "json");
		});
	 });
})( jQuery );
