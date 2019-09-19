<div class="wrap nosubsub">
    <h1><?php esc_html_e( 'Categories' ); ?></h1>
    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <h2><?php esc_html_e( 'Add New Category' ); ?></h2>
                        <form id="addtag" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="validate">
                            <input type="hidden" name="action" value="create_category">
                            <input type="hidden" name="screen" value="edit-bbb-room-category">
                            <input type="hidden" name="taxonomy" value="bbb-room-category">
                            <input type="hidden" name="post_type" value="bbb-room">
                            <input type="hidden" id="bbb_room_add_category_meta_nonce" name="bbb_room_add_category_meta_nonce" value="<?php echo $meta_nonce; ?>">
                            <input type="hidden" name="_wp_http_referer" value="<?php esc_attr( menu_page_url( 'room-categories', true ) ); ?>">
                            <div class="form-field form-required term-name-wrap">
                                <label for="tag-name"><?php esc_html_e( 'Name' ); ?></label>
                                <input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true">
                            </div>
	                        <div class="form-field term-slug-wrap">
                                <label for="tag-slug"><?php esc_html_e( 'Slug' ); ?></label>
                                <input name="slug" id="tag-slug" type="text" value="" size="40">
                                <p><?php esc_html_e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ); ?></p>
                            </div>
                            <tr class="form-field term-parent-wrap">
                                <th scope="row">
                                    <label for="parent"><?php echo esc_html_e( 'Parent Category' ); ?></label>
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
                                        'show_option_none' => __( 'None' ),
                                    );

                                    /** This filter is documented in wp-admin/edit-tags.php */
                                    $dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, 'bbb-room-category', 'edit' );
                                    wp_dropdown_categories( $dropdown_args );
                                    ?>
                                    <p class="description"><?php esc_html_e( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.' ); ?></p>
                                </td>
                            </tr>
	                    <div class="form-field term-description-wrap">
                            <label for="tag-description"><?php esc_html_e( 'Description' ); ?></label>
                            <textarea name="description" id="tag-description" rows="5" cols="40"></textarea>
                        </div>
                        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Add New Category' ); ?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
            <form id="posts-filter" method="post">
                <input type="hidden" name="taxonomy" value="bbb-room-category">
                <input type="hidden" name="post_type" value="bbb-room">
		        <h2 class="screen-reader-text">Categories list</h2><table class="wp-list-table widefat fixed striped tags">
	            <thead>
                    <tr>
                        <th scope="col" id="name" class="manage-column column-name column-primary sortable desc">
                            <a>
                                <span><?php esc_html_e( 'Name' ); ?></span>
                            </a>
                        </th>
                        <th scope="col" id="description" class="manage-column column-description sortable desc">
                            <a>
                                <span><?php esc_html_e( 'Description' ); ?></span>
                            </a>
                        </th>
                        <th scope="col" id="slug" class="manage-column column-slug sortable desc">
                            <a>
                                <span><?php esc_html_e( 'Slug' ); ?></span>
                            </a>
                        </th>
                        <th scope="col" id="posts" class="manage-column column-posts num sortable desc">
                            <a>
                                <span><?php _ex( 'Count', 'Number/count of items' ); ?></span>
                            </a>
                        </th>	
                    </tr>
                </thead>
	            <tbody id="the-list" data-wp-lists="list:tag">
                    <?php foreach( $categories as $category ) { ?>
                        <tr id="tag-<?php echo $category->term_id; ?>" class="level-0">
                            <td class="name column-name has-row-actions column-primary" data-colname="Name">
                                <strong>
                                    <a class="row-title" href="<?php echo $base_url . '/' . $category->slug ; ?>" aria-label="“CS” (Edit)">
                                        <?php echo str_repeat('—', $category->depth_level) . '&nbsp;' . $category->name; ?>
                                    </a>
                                </strong>
                                <br>
                                <div class="hidden" id="inline_<?php echo $category->term_id; ?>">
                                    <div class="name"><?php echo $category->name; ?></div>
                                    <div class="slug">cs</div>
                                    <div class="parent"><?php echo $category->parent; ?></div>
                                </div>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php esc_attr( menu_page_url( 'room-categories', true ) ); ?>&category=<?php echo $category->term_id; ?>&action=edit&nonce=<?php echo $edit_categories_nonce; ?>" aria-label="Edit “CS”"><?php esc_html_e( 'Edit' ); ?></a> | 
                                    </span>
                                    <span class="delete">
                                        <a href="<?php esc_attr( menu_page_url( 'room-categories', true ) ); ?>&category=<?php echo $category->term_id; ?>&action=trash&nonce=<?php echo $delete_categories_nonce; ?>" class="delete-tag aria-button-if-js" role="button"><?php esc_html_e( 'Delete' ); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="description column-description" data-colname="Description">
                                <span aria-hidden="true"><?php echo $category->description; ?></span>
                            </td>
                            <td class="slug column-slug" data-colname="Slug"><?php echo $category->slug; ?></td>
                            <td class="posts column-posts" data-colname="Count">
                                <p><?php echo $category->count; ?></p>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </form>
        </div>
    </div>
</div>