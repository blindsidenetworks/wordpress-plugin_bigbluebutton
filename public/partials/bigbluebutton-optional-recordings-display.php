<div>
    <div>
        <button id="bbb-recordings-display" class="bbb-button"><?php esc_html_e('Hide'); ?></button>
    </div>
    <div id="bbb-recordings-list">
        <?php if (empty($recordings)) { ?>
            <p id="bbb-no-recordings-msg"><?php esc_html_e('This room does not currently have any recordings.', 'bigbluebutton'); ?></p>
        <?php } else { ?>
            <p id="bbb-no-recordings-msg" style="display:none;"><?php esc_html_e('This room does not currently have any recordings.', 'bigbluebutton'); ?></p>
            <div id="bbb-recordings-table" class="table-container" role="table" aria-label="Destinations">
                <div class="flex-table flex-table-<?php echo $columns; ?> header" role="rowgroup">
                    <div class="flex-row flex-row-<?php echo $columns; ?> first" role="columnheader"><?php esc_html_e('Room', 'bigbluebutton'); ?></div>
                    <div class="flex-row flex-row-<?php echo $columns; ?>" role="columnheader"><?php esc_html_e('Date'); ?></div>
                    <div class="flex-row flex-row-<?php echo $columns; ?>" role="columnheader"><?php esc_html_e('Link'); ?></div>
                    <?php if ($manage_bbb_recordings) { ?>
                        <div class="flex-row flex-row-<?php echo $columns; ?>" role="columnheader"><?php esc_html_e('Manage'); ?></div>
                    <?php } ?>
                </div>
                <?php foreach ($recordings as $recording) { ?>
                    <div id="bbb-recording-<?php echo $recording->recordID; ?>" class="flex-table flex-table-<?php echo $columns; ?> row bbb-recording-row" role="rowgroup">
                        <div class="flex-row flex-row-<?php echo $columns; ?> first" role="cell"><?php echo urldecode($recording->name); ?></div>
                        <div class="flex-row flex-row-<?php echo $columns; ?>" role="cell"><?php echo date_i18n($date_format, (int) $recording->startTime / 1000); ?></div>
                        <div class="flex-row flex-row-<?php echo $columns; ?>" role="cell">
                            <div id="bbb-recording-links-block-<?php echo $recording->recordID; ?>" class="bbb-recording-link-block" style="<?php echo ($recording->published == 'false' ? 'display:none;' : ''); ?>">
                                <?php foreach ($recording->playback->format as $format) { ?>
                                    <?php if ($format->type == $default_bbb_recording_format || $view_extended_recording_formats) { ?>
                                        <div class="bbb-recording-link">
                                            <a href="<?php echo $format->url; ?>"><?php esc_html_e(ucfirst($format->type), 'bigbluebutton'); ?></a> 
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if ($manage_bbb_recordings) { ?>
                            <div class="flex-row flex-row-<?php echo $columns; ?>" role="cell">
                                <?php if (isset($recording->protected_icon_classes) && isset($recording->protected_icon_title)) { ?>
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                            data-meta-nonce="<?php echo $meta_nonce; ?>"
                                            class="<?php echo $recording->protected_icon_classes; ?>" title="<?php echo $recording->protected_icon_title; ?>"></i>
                                    &nbsp;
                                <?php } ?>
                                <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="<?php echo $recording->published_icon_classes; ?>" title="<?php echo $recording->published_icon_title; ?>"></i>
                                &nbsp;
                                <i data-record-id="<?php echo $recording->recordID; ?>" 
                                    data-meta-nonce="<?php echo $meta_nonce; ?>"
                                    class="fa fa-trash bbb-icon bbb_trash_recording" title="<?php _ex('Trash', 'post status') ?>"></i>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>