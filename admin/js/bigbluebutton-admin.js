(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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

	 $( window ).load(function() {
		// make update success message in save server settings disppear after 2 seconds
		if ($(".updated").length) {
			$(".updated").delay(2000).fadeOut();
		}

		// edit title of room
		$( "#title" ).keyup(function() {
			let title = $(this).val();
			if (title.length > 0) {
				$("#title-prompt-text").attr("class", "screen-reader-text");
				let slug = title.replace(/\s+/g, '-').toLowerCase().replace(/[^A-Za-z0-9\-\_]/g,"");

				$("#bbb-room-slug-text").val(slug);
			} else {
				$("#title-prompt-text").attr("class", "");
			}
			console.log(title);
		});
	 });
})( jQuery );
