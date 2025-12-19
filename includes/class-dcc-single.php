<?php
class Donmai_Career_Single {
    public function __construct() {
        // ONLY override Single Page Content. Do NOT touch form fields.
        add_filter( 'job_manager_locate_template', array( $this, 'override_single_template' ), 99, 3 );
    }

    public function override_single_template( $template, $template_name, $template_path ) {
        if ( 'content-single-job_listing.php' === $template_name ) {
            $plugin_template = DCC_PLUGIN_DIR . 'templates/content-single-job_listing.php';
            if ( file_exists( $plugin_template ) ) return $plugin_template;
        }
        return $template;
    }
}