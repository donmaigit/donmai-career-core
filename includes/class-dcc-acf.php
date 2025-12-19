<?php

class Donmai_Career_ACF {

    public function __construct() {
        add_action( 'acf/init', array( $this, 'register_fields' ) );
    }

    public function register_fields() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group( array(
            'key' => 'group_donmai_job_details',
            'title' => __( 'Google Career Details', 'donmai-career-core' ),
            'fields' => array(
                array(
                    'key' => 'field_dcc_internal_id',
                    'label' => __( 'Internal Job ID', 'donmai-career-core' ),
                    'name' => 'dcc_internal_id',
                    'type' => 'text',
                    'instructions' => 'e.g., GOOG-2025-JP',
                    'wrapper' => array( 'width' => '50' ),
                ),
                array(
                    'key' => 'field_dcc_department',
                    'label' => __( 'Department / Team', 'donmai-career-core' ),
                    'name' => 'dcc_department',
                    'type' => 'text',
                    'instructions' => 'e.g., Google Cloud or Android Team',
                    'wrapper' => array( 'width' => '50' ),
                ),
                array(
                    'key' => 'field_dcc_qualifications',
                    'label' => __( 'Minimum Qualifications', 'donmai-career-core' ),
                    'name' => 'dcc_qualifications',
                    'type' => 'wysiwyg', // Rich Text Editor for Bullet Points
                    'instructions' => __( 'Enter bullet points here.', 'donmai-career-core' ),
                    'media_upload' => false,
                    'toolbar' => 'basic',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'job_listing',
                    ),
                ),
            ),
        ));
    }
}