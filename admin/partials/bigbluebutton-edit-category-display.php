<div class="wrap">
    <h1>Edit Category</h1>

    <form name="edittag" id="edittag" method="post" action="admin-post.php" class="validate">
        <input type="hidden" name="action" value="edit_category">
        <input type="hidden" name="tag_ID" value="<?php echo $category_id; ?>">
        <input type="hidden" name="taxonomy" value="bbb-room-category">
        <input type="hidden" name="_wp_original_http_referer" value="<?php esc_attr(menu_page_url('room-categories')); ?>">
        <input type="hidden" id="bbb_room_edit_category_meta_nonce" name="bbb_room_edit_category_meta_nonce" value="<?php echo $meta_nonce; ?>">
        <input type="hidden" name="_wp_http_referer" value="<?php esc_attr(menu_page_url('room-categories')); ?>">	
        <table class="form-table" role="presentation">
            <tbody>
                <tr class="form-field form-required term-name-wrap">
                    <th scope="row"><label for="name"><?php esc_html_e('Name'); ?></label></th>
                    <td>
                        <input name="name" id="name" type="text" value="<?php echo $name; ?>" size="40" aria-required="true">
                    </td>
                </tr>
                <tr class="form-field term-slug-wrap">
                    <th scope="row"><label for="slug"><?php esc_html_e('Slug'); ?></label></th>
                                <td><input name="slug" id="slug" type="text" value="<?php echo $slug; ?>" size="40">
                    <p class="description"><?php _e('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'); ?></p></td>
                </tr>
                <tr class="form-field term-parent-wrap">
                    <th scope="row"><label for="parent"><?php esc_html_e('Parent Category'); ?></label>
                    </th>
                    <td>
                    <?php
						$dropdown_args = array(
							'hide_empty'       => 0,
							'hide_if_empty'    => false,
							'taxonomy'         => 'bbb-room-category',
							'name'             => 'parent',
							'orderby'          => 'name',
							'hierarchical'     => true,
							'show_option_none' => __('None'),
							'selected' => $parent
						);

						/** This filter is documented in wp-admin/edit-tags.php */
						$dropdown_args = apply_filters('taxonomy_parent_dropdown_args', $dropdown_args, 'bbb-room-category', 'edit');
						wp_dropdown_categories($dropdown_args);
						?>
                        <p class="description">
                            <?php _e('Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="form-field term-description-wrap">
                    <th scope="row">
                        <label for="description"><?php esc_html_e('Description'); ?></label>
                    </th>
                    <td>
                        <textarea name="description" id="description" rows="5" cols="50" class="large-text"><?php echo $description; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="edit-tag-actions">
            <input type="submit" class="button button-primary" value="<?php esc_html_e('Update'); ?>">
            <span id="delete-link">
                <a class="delete" href="<?php esc_attr(menu_page_url('rooms-categories')); ?>&category=<?php echo $category_id; ?>&action=trash"><?php esc_html_e('Delete'); ?></a>
            </span>
        </div>
    </form>
</div>