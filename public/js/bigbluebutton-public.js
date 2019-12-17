( function( $ ) {
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

	$( window ).load( function() {

		// display/hide recordings
		$( '.bbb-recordings-display' ).click( function() {
			let room_id = $( this )[0].id.substring( 23 );

			/** global: php_vars */
			if ( $( '#bbb-recordings-list-' + room_id ).is( ':visible' ) ) {
				$( '#bbb-recordings-list-' + room_id ).slideUp();
				$( this )
					.children( 'i' )
					.removeClass( 'dashicons-arrow-down-alt2' )
					.addClass( 'dashicons-arrow-right-alt2' );
				$( this )
					.children( '.bbb-expandable-header' )
					.text( php_vars.expand_recordings );
			} else {
				$( '#bbb-recordings-list-' + room_id ).slideDown();
				$( this )
					.children( 'i' )
					.removeClass( 'dashicons-arrow-right-alt2' )
					.addClass( 'dashicons-arrow-down-alt2' );
				$( this )
					.children( '.bbb-expandable-header' )
					.text( php_vars.collapse_recordings );
			}
		});

		// show sorting indicator on hover and hide on mouse away
		$( '.bbb-recordings-unselected-sortable-column' ).hover(
			function() {
				$( this )
					.children( 'i' )
					.removeClass( 'bbb-hidden' );
			},
			function() {
				$( this )
					.children( 'i' )
					.addClass( 'bbb-hidden' );
			}
		);

		// check if moderator has entered the meeting yet
		jQuery( document ).on( 'heartbeat-send', function( event, data ) {
			if ( 0 < $( '#bbb-wait-for-mod-msg' ).length ) {
				data.check_bigbluebutton_meeting_state = true;
				data.bigbluebutton_room_id = $( '#bbb-wait-for-mod-msg' ).data(
					'room-id'
				);
				if ( $( '#bbb-wait-for-mod-msg' ).data( 'temp-room-pass' ) ) {
					data.bigbluebutton_temp_room_pass = $(
						'#bbb-wait-for-mod-msg'
					).data( 'temp-room-pass' );
				}
				if ( $( '#bbb-wait-for-mod-msg' ).data( 'room-username' ) ) {
					data.bigbluebutton_room_username = $(
						'#bbb-wait-for-mod-msg'
					).data( 'room-username' );
				}
			}
		});

		// handle response to checking if moderator has entered the meeting yet
		jQuery( document ).on( 'heartbeat-tick', function( event, data ) {
			if ( ! data.bigbluebutton_admin_has_entered ) {
				return;
			}
			window.location.replace( data.bigbluebutton_join_url );
		});

		// update join room form with new room id and access code input, if necessary
		$( '.bbb-room-selection' ).change( function() {
			let self = this;
			let room_id = this.value;
			let data = {
				action: 'view_join_form',
				room_id: room_id,
				post_type: 'POST'
			};
			jQuery.post(
				php_vars.ajax_url,
				data,
				function( response ) {
					if ( response.success ) {
						$( self )
							.siblings( '#joinroom' )
							.children( '#bbb_join_room_id' )
							.val( room_id );
						$( self )
							.siblings( '#joinroom' )
							.children( '.bbb-error' )
							.hide();

						if ( response['hide_access_code_input']) {
							$( self )
								.siblings( '#joinroom' )
								.children( '#bbb_join_with_password' )
								.hide();
						} else {
							$( self )
								.siblings( '#joinroom' )
								.children( '#bbb_join_with_password' )
								.show();
						}
					}
				},
				'json'
			);
		});

		// publish/unpublish recordings
		$( '.bbb_published_recording' ).click( function() {

			/** global: php_vars */
			let current_icon = $( this );
			let recordID = $( this ).data( 'record-id' );
			let nonce = $( this ).data( 'meta-nonce' );
			let curr_class,
				replace_class,
				curr_icon_class,
				replace_icon_class,
				title,
				value;

			if ( $( this ).hasClass( 'is_published' ) ) {
				value = 'false';
				curr_class = 'is_published';
				replace_class = 'not_published';
				curr_icon_class = 'dashicons-visibility';
				replace_icon_class = 'dashicons-hidden';
				title = php_vars.unpublished;
				$( '#bbb-recording-links-block-' + recordID ).hide();
			} else {
				value = 'true';
				curr_class = 'not_published';
				replace_class = 'is_published';
				curr_icon_class = 'dashicons-hidden';
				replace_icon_class = 'dashicons-visibility';
				title = php_vars.published;
				$( '#bbb-recording-links-block-' + recordID ).show();
			}

			let data = {
				action: 'set_bbb_recording_publish_state',
				value: value,
				post_type: 'POST',
				meta_nonce: nonce,
				record_id: recordID
			};

			jQuery.post(
				php_vars.ajax_url,
				data,
				function( response ) {
					if ( response.success ) {
						current_icon
							.removeClass( curr_icon_class )
							.addClass( replace_icon_class );
						current_icon.attr( 'title', title );
						current_icon
							.removeClass( curr_class )
							.addClass( replace_class );
					}
				},
				'json'
			);
		});

		// protect/unprotect recordings
		$( '.bbb_protected_recording' ).click( function() {

			/** global: php_vars */
			let current_icon = $( this );
			let recordID = $( this ).data( 'record-id' );
			let nonce = $( this ).data( 'meta-nonce' );
			let curr_class,
				replace_class,
				curr_icon_class,
				replace_icon_class,
				title,
				value;

			if ( $( this ).hasClass( 'is_protected' ) ) {
				value = 'false';
				curr_class = 'is_protected';
				replace_class = 'not_protected';
				curr_icon_class = 'dashicons-lock';
				replace_icon_class = 'dashicons-unlock';
				title = php_vars.unprotected;
			} else {
				value = 'true';
				curr_class = 'not_protected';
				replace_class = 'is_protected';
				curr_icon_class = 'dashicons-unlock';
				replace_icon_class = 'dashicons-lock';
				title = php_vars.protected;
			}

			let data = {
				action: 'set_bbb_recording_protect_state',
				value: value,
				post_type: 'POST',
				meta_nonce: nonce,
				record_id: recordID
			};

			jQuery.post(
				php_vars.ajax_url,
				data,
				function( response ) {
					if ( response.success ) {
						current_icon
							.removeClass( curr_icon_class )
							.addClass( replace_icon_class );
						current_icon.attr( 'title', title );
						current_icon
							.removeClass( curr_class )
							.addClass( replace_class );
					}
				},
				'json'
			);
		});

		// delete recording
		$( '.bbb_trash_recording' ).click( function() {

			/** global: php_vars */
			let recordID = $( this ).data( 'record-id' );
			let nonce = $( this ).data( 'meta-nonce' );

			let data = {
				action: 'trash_bbb_recording',
				post_type: 'POST',
				meta_nonce: nonce,
				record_id: recordID
			};

			jQuery.post(
				php_vars.ajax_url,
				data,
				function( response ) {
					if ( response.success ) {
						$( '#bbb-recording-' + recordID ).remove();

						// if there are no recordings left, remove the table
						if ( 0 == $( '.bbb-recording-row' ).length ) {
							$( '#bbb-recordings-table' ).remove();
							$( '#bbb-no-recordings-msg' ).show();
						}
					}
				},
				'json'
			);
		});

		// edit recording data in the table
		$( document ).on( 'click', '.bbb_edit_recording_data', function() {

			/** global: php_vars */
			let recordID = $( this ).data( 'record-id' );
			let old_value = $( this ).data( 'record-value' );
			let type = $( this ).data( 'record-type' );
			let nonce = $( this ).data( 'meta-nonce' );
			let form = '#bbb-recording-' + type + '-' + recordID;
			let original_content = $( form ).contents();

			$( form ).empty();

			$( '<input>', {
				type: 'text',
				id: 'submit-recording-' + type + '-' + recordID,
				value: old_value,
				class: 'bbb-recording-table-input'
			})
				.appendTo( form )
				.focus();

			// submit changed recording data
			$( '#submit-recording-' + type + '-' + recordID ).keyup( function( e ) {
				if ( 'Enter' === e.key ) {
					let new_value = $( this ).val();

					let data = {
						action: 'set_bbb_recording_edits',
						post_type: 'POST',
						meta_nonce: nonce,
						record_id: recordID,
						type: type,
						value: new_value
					};

					jQuery.post(
						php_vars.ajax_url,
						data,
						function( response ) {
							if ( response.success ) {
								$( form ).text( new_value );
								$( '<i>', {
									class:
										'dashicons dashicons-edit bbb-icon bbb_edit_recording_data',
									id: 'edit-recording-' + type + recordID,
									title: php_vars.edit,
									'data-record-id': recordID,
									'data-record-type': type,
									'data-record-value': new_value,
									'data-meta-nonce': nonce
								}).appendTo( form );
							}
						},
						'json'
					);
				} else if ( 'Escape' === e.key ) {

					// restore previous data
					$( form ).html( original_content );
				}
			});
		});
	});
}( jQuery ) );
