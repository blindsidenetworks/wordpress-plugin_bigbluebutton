<div>
    <div>
        <button id="bbb-recordings-display"><?php esc_html_e('Hide'); ?></button>
    </div>
    <div id="recordings-list">
        <?php if(empty($recordings)) { ?>
            <?php esc_html_e('This room does not yet have any recordings.', 'bigbluebutton'); ?>
        <?php } else { ?>
            <div class="table-container" role="table" aria-label="Destinations">
                <div class="flex-table header" role="rowgroup">
                <div class="flex-row first" role="columnheader"><?php esc_html_e('Room', 'bigbluebutton'); ?></div>
                <div class="flex-row" role="columnheader"><?php esc_html_e('Date'); ?></div>
                <div class="flex-row" role="columnheader"><?php esc_html_e('Link'); ?></div>
            </div>
            <?php foreach($recordings as $recording) { ?>
                <div class="flex-table row" role="rowgroup">
                    <div class="flex-row first" role="cell"><?php echo urldecode($recording->name); ?></div>
                    <div class="flex-row" role="cell"><?php echo date_i18n(get_option( 'date_format' ), (int)$recording->startTime / 1000); ?></div>
                    <div class="flex-row" role="cell"><a href="<?php echo $recording->playback->format->url; ?>"><?php esc_html_e('View', 'bigbluebutton'); ?></a></div>
                </div>
            <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>