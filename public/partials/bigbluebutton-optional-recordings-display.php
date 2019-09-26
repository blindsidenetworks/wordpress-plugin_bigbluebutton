<div>
    <div>
        <button id="bbb-recordings-display"><?php esc_html_e('Hide'); ?></button>
    </div>
    <div id="bbb-recordings-list">
        <?php if (empty($recordings)) { ?>
            <p id="bbb-no-recordings-msg"><?php esc_html_e('This room does not currently have any recordings.', 'bigbluebutton'); ?></p>
        <?php } else { ?>
            <p id="bbb-no-recordings-msg" style="display:none;"><?php esc_html_e('This room does not currently have any recordings.', 'bigbluebutton'); ?></p>
            <div id="bbb-recordings-table" class="table-container" role="table" aria-label="Destinations">
                <div class="flex-table flex-table-<?php echo $columns; ?> header" role="rowgroup">
                    <div class="flex-row first" role="columnheader"><?php esc_html_e('Room', 'bigbluebutton'); ?></div>
                    <div class="flex-row" role="columnheader"><?php esc_html_e('Date'); ?></div>
                    <div class="flex-row" role="columnheader"><?php esc_html_e('Link'); ?></div>
                    <?php if ($manage_bbb_recordings) { ?>
                        <div class="flex-row" role="columnheader"><?php esc_html_e('Manage'); ?></div>
                    <?php } ?>
                </div>
                <?php foreach ($recordings as $recording) { ?>
                    <div id="bbb-recording-<?php echo $recording->recordID; ?>" class="flex-table flex-table-<?php echo $columns; ?> row bbb-recording-row" role="rowgroup">
                        <div class="flex-row first" role="cell"><?php echo urldecode($recording->name); ?></div>
                        <div class="flex-row" role="cell"><?php echo date_i18n($date_format, (int) $recording->startTime / 1000); ?></div>
                        <div class="flex-row" role="cell">
                            <div id="bbb-recording-links-block-<?php echo $recording->recordID; ?>" style="<?php echo ($recording->published == 'false' ? 'display:none;' : ''); ?>">
                                <?php foreach ($recording->playback->format as $format) { ?>
                                    <div>
                                        <a href="<?php echo $format->url; ?>"><?php esc_html_e($format->type, 'bigbluebutton'); ?></a> 
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if ($manage_bbb_recordings) { ?>
                            <div class="flex-row" role="cell">
                                <?php if ( ! isset($recording->protected)) { ?>
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="fa fa-unlock fa-disabled" title="<?php esc_html_e('Unprotected', 'bigbluebutton'); ?>"></i>
                                <?php } else if ($recording->protected == 'true') { ?>
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="fa fa-lock icon bbb_protected_recording is_protected" title="<?php esc_html_e('Protected', 'bigbluebutton'); ?>"></i>
                                <?php } else { ?> 
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="fa fa-unlock icon bbb_protected_recording not_protected" title="<?php esc_html_e('Unprotected', 'bigbluebutton'); ?>"></i>
                                <?php } ?>
                                &nbsp;
                                <?php if ($recording->published == 'true') { ?>
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="fa fa-eye icon bbb_published_recording is_published" title="<?php esc_html_e('Published'); ?>"></i>
                                <?php } else { ?> 
                                    <i data-record-id="<?php echo $recording->recordID; ?>" 
                                        data-meta-nonce="<?php echo $meta_nonce; ?>"
                                        class="fa fa-eye-slash icon bbb_published_recording not_published" title="<?php esc_html_e('Unpublished'); ?>"></i>
                                <?php } ?>
                                &nbsp;
                                <i data-record-id="<?php echo $recording->recordID; ?>" 
                                    data-meta-nonce="<?php echo $meta_nonce; ?>"
                                    class="fa fa-trash icon bbb_trash_recording" title="trash"></i>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>