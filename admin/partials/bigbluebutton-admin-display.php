<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://blindsidenetworks.com
 * @since      3.0.0
 *
 * @package    Bigbluebutton
 * @subpackage Bigbluebutton/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="bbb-settings-card">
    <h1><?php esc_html_e( 'Room Settings', 'bigbluebutton') ?></h1>
    <h2><?php esc_html_e( 'Server', 'bigbluebutton') ?></h2>
    <p><?php esc_html_e( 'The server settings explanation.', 'bigbluebutton') ?></p>
    <form id="bbb-general-settings-form" method="POST" action="">
        <input type="hidden" name="action" value="bbb_general_settings">
        <div class="bbb-row">
            <p class="bbb-col-left bbb-important-label"><?php esc_html_e( 'EndPoint', 'bigbluebutton') ?>: </p> 
            <input class="bbb-col-right" type="text" name="bbb_url" size=50 value="<?php echo $bbb_settings["bbb_url"] ?>">
        </div>
        <div class="bbb-row">
            <p class="bbb-col-left"></p> 
            <label class="bbb-col-right"><?php esc_html_e( 'Example', 'bigbluebutton') ?>: <?php echo $bbb_settings["bbb_default_url"] ?></label>
        </div>
        <div class="bbb-row">
            <p class="bbb-col-left bbb-important-label"><?php esc_html_e( 'Shared Secret', 'bigbluebutton') ?>: </p> 
            <input class="bbb-col-right" type="text" name="bbb_salt" size=50 value="<?php echo $bbb_settings["bbb_salt"] ?>">
        </div>
        <div class="bbb-row">
            <p class="bbb-col-left"></p> 
            <label class="bbb-col-right"><?php esc_html_e( 'Example', 'bigbluebutton') ?>: <?php echo $bbb_settings["bbb_default_salt"] ?></label>
        </div>
        <?php if ($bbb_settings["bbb_url"] == $bbb_settings["bbb_default_url"]) { ?>
        <label><?php esc_html_e( 'Default server settings 1. Default server settings 2.', 'bigbluebutton') ?></label>
        <?php } ?>
        <?php if ($change_success) { ?>
            <div class="updated">
                <p><?php esc_html_e( 'Save server settings success message.', 'bigbluebutton') ?></p>
            </div>
        <?php } ?>
        <br><br>
        <input class="button button-primary bbb-settings-submit" type="submit" value="<?php esc_html_e( 'Save Setting', 'bigbluebutton') ?>"/>
    </form>
</div>