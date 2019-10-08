<?php

/**
 * Create instance of bigbluebutton widget.
 * 
 * @since   3.0.0
 */
class Bigbluebutton_Public_Widget extends WP_Widget {
    
    /**
     * Construct widget.
     * 
     * @since   3.0.0
     */
    public function __construct() {
        parent::__construct(
            'bigbluebuttonwidget',
            __('Rooms', 'bigbluebutton'),
            array(
                'customize_selective_refresh' => true,
                'description' => __('Displays a BigBlueButton login form.', 'bigbluebutton'),
                'panels_icon' => 'dashicons dashicons-video-alt2'
            )
        );
    }

    /**
     * Display the widget.
     * 
     * @param   Array      $args      List of default values stored for the widget.
     * @param   Array      $instance  List of custom values store for the widget.
     */
    public function widget($args, $instance) {
        extract($args);

        $tokens_string = isset($instance['text']) ? $instance['text'] : '';
        $author = isset($instance['author']) ? $instance['author'] : 0;
        $display_helper = new BigbluebuttonDisplayHelper(plugin_dir_path(__FILE__));

        echo $before_widget;

        echo BigbluebuttonTokensHelper::join_form_from_tokens_string($display_helper, $tokens_string, $author);
        
        echo $after_widget;
    }

    /**
     * Create widget form (for the admin panel).
     * 
     * @since   3.0.0
     * 
     * @param   Array   $instance   Existing widget values to show in the widget form.
     */
    public function form($instance) {
        $text_id = $this->get_field_id('text');
        $text_name = $this->get_field_name('text');
        
        $text_value = isset($instance['text']) ? $instance['text'] : '';

        include 'partials/bigbluebutton-create-widget-display.php';
    }
    
    /**
     * Update widget settings.
     * 
     * @since   3.0.0
     * 
     * @param   Array   $new_instance  New values for widget.
     * @param   Array   $old_instance  Previous values for widget.
     * 
     * @return  Array   $instance      Kept values for widget.
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['text'] = isset($new_instance['text']) ? wp_strip_all_tags($new_instance['text']) : '';
        $instance['author'] = isset($old_instance['author']) ? $old_instance['author'] : get_current_user_id();
        return $instance;
    }

}