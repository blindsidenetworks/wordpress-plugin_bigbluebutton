<?php

/**
 * Defines constants within the bigbluebutton plugin.
 * 
 * @since   3.0.0
 */
class BigbluebuttonConstants {

    /**
     * Define constants for usage within the plugin.
     * 
     * @since   3.0.0
     */
    public static function define() {
        $prefix = "BIGBLUEBUTTON_";

        defined($prefix . 'ROOM_ID') or define ($prefix . 'ROOM_ID', 'bbb-room');
        defined($prefix . 'CATEGORY_ID') or define ($prefix . 'CATEGORY_ID', 'bbb-room-category');
    }
}