<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

error_log("Uninstalling");
// Uninstall code goes here
global $wpdb;

$tables = array('bigbluebutton', 'bigbluebutton_log');
foreach ($tables as $table) {
    $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . $table;
    $wpdb->query($sql);
}

delete_option('bigbluebutton_endpoint');
delete_option('bigbluebutton_secret');


