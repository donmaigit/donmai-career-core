<?php

class Donmai_Career_Dashboard {

    public function __construct() {
        // Add Header
        add_filter( 'job_manager_job_dashboard_columns', array( $this, 'add_dashboard_columns' ) );
        // Add Content
        add_action( 'job_manager_job_dashboard_column_dcc_internal_id', array( $this, 'render_dashboard_column' ), 10, 2 );
    }

    public function add_dashboard_columns( $columns ) {
        // Insert Internal ID after Job Title
        $new_columns = array();
        foreach ( $columns as $key => $column ) {
            $new_columns[ $key ] = $column;
            if ( 'job_title' === $key ) {
                $new_columns['dcc_internal_id'] = __( 'Internal ID', 'donmai-career-core' );
            }
        }
        return $new_columns;
    }

    public function render_dashboard_column( $job ) {
        $internal_id = get_post_meta( $job->ID, 'dcc_internal_id', true );
        if ( $internal_id ) {
            echo '<code style="background:#f1f3f4; padding:2px 6px; border-radius:4px; color:#3c4043;">' . esc_html( $internal_id ) . '</code>';
        } else {
            echo '<span style="color:#ccc;">—</span>';
        }
    }
}