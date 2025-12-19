<?php

class Donmai_Career_Templates {

    public function __construct() {
        add_filter( 'job_manager_locate_template', array( $this, 'locate_template' ), 10, 3 );
    }

    public function locate_template( $template, $template_name, $template_path ) {
        // 1. Look in OUR plugin folder first
        $plugin_path = DCC_PLUGIN_DIR . 'templates/' . $template_name;
        if ( file_exists( $plugin_path ) ) {
            return $plugin_path;
        }

        // 2. SAFETY NET: If WPJM found nothing (empty) AND we found nothing...
        if ( empty( $template ) ) {
            // Return a "Fallback" template to stop the Fatal Error
            return DCC_PLUGIN_DIR . 'templates/form-fields/dcc-fallback-field.php';
        }

        return $template;
    }
}