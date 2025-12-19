<?php

class Donmai_Career_Dependencies {

    /**
     * Check if required plugins are active
     */
    public static function check() {
        $missing = array();

        // Check 1: WP Job Manager
        if ( ! is_plugin_active( 'wp-job-manager/wp-job-manager.php' ) ) {
            $missing[] = 'WP Job Manager';
        }

        // Check 2: ACF (Supports Free or Pro)
        if ( ! class_exists( 'ACF' ) ) {
            $missing[] = 'Advanced Custom Fields';
        }

        // If anything is missing, trigger the admin notice
        if ( ! empty( $missing ) ) {
            self::render_notice( $missing );
            return false; // Stop loading core
        }

        return true; // All good
    }

    /**
     * Display Admin Notice
     */
    private static function render_notice( $missing_plugins ) {
        add_action( 'admin_notices', function() use ( $missing_plugins ) {
            $list = implode( ', ', $missing_plugins );
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e( 'Donmai CAREER Core Error:', 'donmai-career-core' ); ?></strong> 
                    <?php 
                    printf( 
                        __( 'This plugin requires the following plugins to be active: <strong>%s</strong>.', 'donmai-career-core' ), 
                        esc_html( $list ) 
                    ); 
                    ?>
                </p>
            </div>
            <?php
        });
    }
}