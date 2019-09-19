<div class="wrap">
    <h1 class="wp-heading-inline"> <?php esc_html_e('Add New Room', 'bigbluebutton'); ?></h1>
    <hr class="wp-header-end">
    <form name="post" action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="post">
        <input type="hidden" id="user-id" name="user_ID" value="<?php echo $user_id; ?>">
        <input type="hidden" id="hiddenaction" name="action" value="create_room">
        <input type="hidden" id="post_author" name="post_author" value="<?php echo $user_id; ?>">
        <input type="hidden" id="post_type" name="post_type" value="bbb-room">
        <input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id; ?>">
        <input type="hidden" name="bbb_create_room_meta_nonce" value="<?php echo $meta_nonce; ?>">
        <input type="hidden" name="_wp_http_referer" value="<?php esc_attr(menu_page_url('rooms-list', true)); ?>">
        <div id="form_top-sortables" class="meta-box-sortables ui-sortable"></div>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content" style="position: relative;">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label id="title-prompt-text" for="title"><?php esc_html_e('Add title'); ?></label>
                            <input type="text" name="post_title" size="30" value="" id="title" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                    <div id="after_title-sortables" class="meta-box-sortables ui-sortable"></div>
                        <?php
							wp_editor(
								'',
								'bbb-room-description',
								array(
									'tabfocus_elements'   => 'content-html,save-post',
									'editor_height'       => 300,
									'tinymce'             => array(
										'resize'                  => false,
										'add_unload_trigger'      => false,
									),
								)
							);
						?>
                    <div id="after_editor-sortables" class="meta-box-sortables ui-sortable"></div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
                        <div id="submitdiv" class="postbox">
                            <h2 class="hndle ui-sortable-handle"><span> <?php esc_html_e('Publish'); ?></span></h2>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <span class="spinner"></span>
                                            <input name="original_publish" type="hidden" id="original_publish" value="Publish">
                                            <input type="submit" name="bbb-room-status" id="publish" class="button button-primary button-large" value="<?php esc_html_e('Publish'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="bbb-room-categorydiv" class="postbox categorydiv">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php esc_html_e('Categories'); ?></span>
                            </h2>
                            <div class="inside">
                                <div id="bbb-room-category-all" class="tabs-panel">
                                    <input type="hidden" name="tax_input[bbb-room-category][]" value="0">
                                    <ul id="bbb-room-categorychecklist" data-wp-lists="list:bbb-room-category" class="categorychecklist form-no-clear">
                                        <?php foreach ($categories as $category) { ?>
                                            <?php if ($category->parent == 0) {?>
                                                <li id="bbb-room-category-<?php echo $category->term_id; ?>">
                                                    <label class="selectit"><input value="<?php echo $category->term_id; ?>" type="checkbox" name="tax_input[bbb-room-category][]" id="in-bbb-room-category-<?php echo $category->term_id; ?>"> <?php echo $category->name; ?></label>
                                                </li>
                                            <?php } else { ?>
                                                <ul class="children">
                                                    <li id="bbb-room-category-<?php echo $category->term_id; ?>" class="popular-category">
                                                        <label class="selectit"><input value="<?php echo $category->term_id; ?>" type="checkbox" name="tax_input[bbb-room-category][]" id="in-bbb-room-category-<?php echo $category->term_id; ?>"> <?php echo $category->name; ?></label>
                                                    </li>
                                                </ul>
                                            <?php }?> 
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                    <div id="advanced-sortables" class="meta-box-sortables ui-sortable">
                        <div id="bbb-moderator-code" class="postbox">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php esc_html_e('Moderator Code', 'bigbluebutton'); ?></span>
                            </h2>
                            <div class="inside">
                                <label><?php esc_html_e('Moderator Code', 'bigbluebutton'); ?>: </label>
                                <input name="bbb-moderator-code" type="text" value="<?php echo $moderator_code; ?>">
                            </div>
                        </div>
                        <div id="bbb-viewer-code" class="postbox ">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php esc_html_e('Viewer Code', 'bigbluebutton'); ?></span>
                            </h2>
                            <div class="inside">
                                <label><?php esc_html_e('Viewer Code', 'bigbluebutton'); ?>: </label>
                                <input name="bbb-viewer-code" type="text" value="<?php echo $viewer_code; ?>">
                            </div>
                        </div>
                        <div id="bbb-room-permalink" class="postbox ">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php esc_html_e('Permalink'); ?></span>
                            </h2>
                            <div class="inside">
                                <input type="hidden" id="permalink" name="permalink" value="">
                                <label> <?php echo $url; ?>/ </label>
                                <input id="bbb-room-slug-text" name="slug" type="text" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
        </div>
    </form>
</div>