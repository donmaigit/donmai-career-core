<?php

class Donmai_Career_Admin {

    public function __construct() {
        // Add columns to Job Dashboard
        add_filter( 'manage_edit-job_listing_columns', array( $this, 'add_columns' ) );
        add_action( 'manage_job_listing_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'wp_terms_checklist_args', array( $this, 'disable_checked_ontop' ) );
    }

    public function add_columns( $columns ) {
        // Insert 'Department' after 'Title'
        $new_columns = array();
        foreach($columns as $key => $value) {
            $new_columns[$key] = $value;
            if($key == 'title') {
                $new_columns['dcc_dept'] = __( 'Department', 'donmai-career-core' );
                $new_columns['dcc_id']   = __( 'Job ID', 'donmai-career-core' );
            }
        }
        return $new_columns;
    }

    public function render_columns( $column, $post_id ) {
        if ( 'dcc_dept' === $column ) {
            $dept = get_field( 'dcc_department', $post_id );
            echo $dept ? esc_html( $dept ) : '—';
        }
        if ( 'dcc_id' === $column ) {
            $jid = get_field( 'dcc_internal_id', $post_id );
            echo $jid ? '<code>' . esc_html( $jid ) . '</code>' : '—';
        }
    }

    public function disable_checked_ontop( $args ) {
        // Fix for Regions and Trains
        if ( isset($args['taxonomy']) && in_array($args['taxonomy'], array('job_listing_region', 'job_listing_train')) ) {
            $args['checked_ontop'] = false;
        }
        return $args;
    }
}