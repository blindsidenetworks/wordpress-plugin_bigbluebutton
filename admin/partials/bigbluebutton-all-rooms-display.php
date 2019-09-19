<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('All Rooms', 'bigbluebutton'); ?>
    </h1>
    <table class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <th scope="col" id="title" class="manage-column column-title column-primary">
                    <span><?php esc_html_e('Title'); ?></span>
                </th>
                <th scope="col" id="description" class="manage-column column-description column-primary">
                    <span><?php esc_html_e('Description'); ?></span>
                </th>
                <th scope="col" id="category" class="manage-column column-category column-primary">
                    <span><?php esc_html_e('Category'); ?></span>
                </th>
                <th scope="col" id="author" class="manage-column column-category column-primary">
                    <span><?php esc_html_e('Author'); ?></span>
                </th>
                <th scope="col" id="permalink" class="manage-column column-permalink column-primary">
                    <span><?php esc_html_e('Permalink'); ?></span>
                </th>
                <th scope="col" id="moderator-code" class="manage-column column-moderator-code">
                    <span><?php esc_html_e('Moderator Code', 'bigbluebutton'); ?></span>
                </th>
                <th scope="col" id="viewer-code" class="manage-column column-viewer-code">
                    <span><?php esc_html_e('Viewer Code', 'bigbluebutton'); ?></span>
                </th>
                <th scope="col" id="date" class="manage-column column-date">
                    <span><?php esc_html_e('Date'); ?></span>
                </th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php while ($loop->have_posts()) : $loop->the_post(); $post = $loop->post; ?>
                <?php if ($post->post_status == 'trash') { ?>
                    <?php continue; ?>
                <?php } ?>
                <tr id="post-<?php echo $post->ID; ?>" class="iedit author-self level-0 post-<?php echo $post->ID; ?> type-bbb-room status-<?php echo $post->post_status; ?> hentry">
                    <td class="title column-title has-row-actions column-primary page-title" data-colname="Title">
                        <strong>
                            <a class="row-title" href="<?php echo get_permalink($post->ID); ?>"><?php the_title(); ?></a>
                        </strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php esc_attr(menu_page_url('rooms-list')); ?>&post=<?php echo $post->ID; ?>&amp;action=edit&nonce=<?php echo $edit_room_nonce; ?>"><?php esc_html_e('Edit'); ?></a> | 
                            </span>
                            <span class="trash">
                                <a href="<?php esc_attr(menu_page_url('rooms-list')); ?>&post=<?php echo $post->ID; ?>&amp;action=trash&nonce=<?php echo $delete_room_nonce; ?>" class="submitdelete"><?php esc_html_e('Delete'); ?></a>
                            </span>
                        </div>
                    </td>
                    <td class="description column-description" data-colname="description">
                        <p>
                            <?php the_content(); ?>
                        </p>
                    </td>
                    <td class="category column-category" data-colname="category">
                        <p>
                            <?php echo implode(', ', wp_get_object_terms($post->ID, 'bbb-room-category', array('fields' => 'names'))); ?>
                        </p>
                    </td>
                    <td class="author column-author" data-colname="author">
                        <p>
                            <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                        </p>
                    </td>
                    <td class="permalink column-permalink" data-colname="permalink">
                        <a href=<?php echo get_permalink($post->ID); ?>><?php echo get_permalink($post->ID); ?></a>
                    </td>
                    <td class="moderator-code column-moderator-code" data-colname="moderator-code">
                        <p><?php echo get_post_meta($post->ID, 'bbb-room-moderator-code', true); ?></p>
                    </td>
                    <td class="viewer-code column-viewer-code" data-colname="viewer-code">
                        <p><?php echo get_post_meta($post->ID, 'bbb-room-viewer-code', true); ?></p>
                    </td>
                    <?php if ($post->post_status == "publish") { ?>
                    <td class="date column-date" data-colname="Date"><?php esc_html_e('Published'); ?><br>
                        <abbr title="<?php echo $post->post_date; ?>"><?php echo get_the_date(get_option('date_format'), $post->ID); ?></abbr>
                    </td>
                    <?php } else { ?>
                        <td class="date column-date" data-colname="Date"><?php esc_html_e('Last modified'); ?><br>
                        <abbr title="<?php echo $post->post_date; ?>"><?php echo get_the_date(get_option('date_format'), $post->ID); ?></abbr>
                    </td>
                    <?php } ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>