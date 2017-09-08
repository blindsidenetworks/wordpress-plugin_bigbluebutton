<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Uninstall code goes here
global $wpdb;

// Remove old tables if exist
$tables = array('bigbluebutton', 'bigbluebutton_logs');
foreach ($tables as $table) {
    $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . $table;
    $wpdb->query($sql);
}

// Remove old options if exist
delete_option('bigbluebutton_url');
delete_option('bigbluebutton_salt');

// Remove rooms
$args = array (
    'post_type' => 'room',
    'nopaging' => true
);
$query = new WP_Query($args);
while ($query->have_posts()) {
    $query->the_post();
    $id = get_the_ID();
    wp_delete_post($id, true);
}
wp_reset_postdata();

// Remove options
delete_option('bigbluebutton_version');
delete_option('bigbluebutton_endpoint');
delete_option('bigbluebutton_secret');


