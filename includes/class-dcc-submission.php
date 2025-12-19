<?php

class Donmai_Career_Submission {

    public function __construct() {
        // 1. Init & Assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // 2. Form Fields
        add_filter( 'submit_job_form_fields', array( $this, 'clean_default_fields' ), 10 );
        add_filter( 'submit_job_form_fields', array( $this, 'inject_acf_fields_into_form' ), 20 );
        add_filter( 'submit_job_form_fields', array( $this, 'convert_fields_types' ), 9999 );
        add_filter( 'submit_job_form_fields', array( $this, 'apply_custom_priorities' ), 10000 );
        add_filter( 'submit_job_form_fields', array( $this, 'apply_field_visibility' ), PHP_INT_MAX );

        // 3. Save Data
        add_action( 'job_manager_update_job_data', array( $this, 'save_acf_fields' ), 10, 2 );
        add_action( 'job_manager_update_job_data', array( __CLASS__, 'sync_region_to_location_text' ), 20, 2 );
        add_action( 'job_manager_update_job_data', array( __CLASS__, 'sync_train_data' ), 20, 2 );
    }

    public function enqueue_assets() {
        // Using time() to FORCE reload JS/CSS
        wp_enqueue_style( 'dcc-submission-css', DCC_PLUGIN_URL . 'assets/css/dcc-submission.css', array(), time() );
        wp_enqueue_script( 'dcc-submission-js', DCC_PLUGIN_URL . 'assets/js/dcc-submission.js', array( 'jquery' ), time(), true );
        
        wp_localize_script( 'dcc-submission-js', 'dcc_submission_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dcc_drilldown_nonce' )
        ));
    }

    public function clean_default_fields( $fields ) {
        unset( $fields['company']['company_video'] );
        unset( $fields['company']['company_twitter'] );
        unset( $fields['company']['company_tagline'] );
        return $fields;
    }

    public function inject_acf_fields_into_form( $fields ) {
        if( ! function_exists('acf_get_field_groups') ) return $fields;
        $groups = acf_get_field_groups(array('post_type' => 'job_listing'));
        $skip = array('job_title', 'job_location', 'job_region', 'job_category', 'job_type', 'job_description', 'job_train');

        foreach( $groups as $group ) {
            $acf_fields = acf_get_fields( $group['key'] );
            if( $acf_fields ) {
                foreach( $acf_fields as $acf ) {
                    $key = $acf['name'];
                    if ( in_array($key, $skip) ) continue;

                    $type = 'text';
                    if( $acf['type'] == 'textarea' ) $type = 'textarea';
                    if( $acf['type'] == 'wysiwyg' )  $type = 'wp-editor';
                    if( $acf['type'] == 'select' )   $type = 'select';
                    
                    $new_field = array(
                        'label'       => $acf['label'],
                        'type'        => $type,
                        'required'    => $acf['required'] ? true : false,
                        'priority'    => $acf['menu_order'] ? $acf['menu_order'] : 20,
                    );
                    if( $type == 'select' && !empty($acf['choices']) ) $new_field['options'] = $acf['choices'];
                    $fields['job'][ $key ] = $new_field;
                }
            }
        }
        return $fields;
    }

    public function convert_fields_types( $fields ) {
        
        // 1. Category -> USE CUSTOM 'dcc-multiselect'
        if ( isset( $fields['job']['job_category'] ) ) {
            $terms = get_terms( array( 'taxonomy' => 'job_listing_category', 'hide_empty' => false ) );
            $cat_options = array();
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) $cat_options[ $term->term_id ] = $term->name;
            }
            // CHANGED: Use 'dcc-multiselect' so it matches your uploaded file 'dcc-multiselect-field.php'
            $fields['job']['job_category']['type'] = 'dcc-multiselect'; 
            $fields['job']['job_category']['options'] = $cat_options;
            $fields['job']['job_category']['description'] = 'Select one or more categories.';
        }

        // 2. Region
        if ( taxonomy_exists( 'job_listing_region' ) ) {
            unset( $fields['job']['job_location'] );
            $fields['job']['job_region'] = array(
                'label'       => __( 'Location', 'donmai-career-core' ),
                'type'        => 'dcc-region-drilldown',
                'required'    => true,
                'priority'    => 2,
                'default'     => '',
            );
        }

        // 3. Train
        if ( taxonomy_exists( 'job_listing_train' ) ) {
            $fields['job']['job_train'] = array(
                'label'       => __( 'Train Line / Station', 'donmai-career-core' ),
                'type'        => 'dcc-train-picker',
                'required'    => false,
                'priority'    => 3,
                'default'     => '',
            );
        }
        return $fields;
    }

    public function apply_custom_priorities( $fields ) {
        $saved_priorities = get_option( 'dcc_field_priorities', array() );
        if ( empty( $saved_priorities ) ) return $fields;
        if ( isset( $fields['job'] ) ) { foreach ( $fields['job'] as $key => $config ) { if ( isset( $saved_priorities[$key] ) ) $fields['job'][$key]['priority'] = floatval( $saved_priorities[$key] ); } }
        if ( isset( $fields['company'] ) ) { foreach ( $fields['company'] as $key => $config ) { if ( isset( $saved_priorities[$key] ) ) $fields['company'][$key]['priority'] = floatval( $saved_priorities[$key] ); } }
        return $fields;
    }

    public function apply_field_visibility( $fields ) {
        if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'dcc-fields' ) return $fields;
        $hidden_fields = get_option( 'dcc_hidden_fields', array() );
        if ( empty( $hidden_fields ) ) return $fields;
        if ( isset( $fields['job'] ) ) { foreach ( $hidden_fields as $key ) { if ( isset( $fields['job'][$key] ) ) unset( $fields['job'][$key] ); } }
        if ( isset( $fields['company'] ) ) { foreach ( $hidden_fields as $key ) { if ( isset( $fields['company'][$key] ) ) unset( $fields['company'][$key] ); } }
        return $fields;
    }

    public function save_acf_fields( $job_id, $values ) {
        if( ! function_exists('acf_get_field_groups') ) return;
        $groups = acf_get_field_groups(array('post_type' => 'job_listing'));
        $protected = array('job_category', 'job_region', 'job_type', 'job_train');
        foreach( $groups as $group ) {
            $acf_fields = acf_get_fields( $group['key'] );
            if( $acf_fields ) {
                foreach( $acf_fields as $acf ) {
                    $key = $acf['name'];
                    if ( in_array($key, $protected) ) continue;
                    if ( isset( $values['job'][ $key ] ) ) {
                        $value = $values['job'][ $key ];
                        $clean_value = ($acf['type'] == 'wysiwyg') ? wp_kses_post( $value ) : sanitize_text_field( $value );
                        update_post_meta( $job_id, $key, $clean_value );
                        update_field( $key, $clean_value, $job_id );
                    }
                }
            }
        }
    }

    public static function sync_region_to_location_text( $job_id, $values ) {
        if ( isset( $values['job']['job_region'] ) ) {
            $term_ids = $values['job']['job_region'];
            if ( ! is_array( $term_ids ) ) $term_ids = array( $term_ids );
            if ( ! empty( $term_ids ) ) {
                $location_names = array();
                foreach ( $term_ids as $term_id ) {
                    $term = get_term( intval( $term_id ), 'job_listing_region' );
                    if ( $term && ! is_wp_error( $term ) ) $location_names[] = $term->name;
                }
                if ( ! empty( $location_names ) ) {
                    update_post_meta( $job_id, '_job_location', implode( ', ', $location_names ) );
                }
                wp_set_object_terms( $job_id, array_map( 'intval', $term_ids ), 'job_listing_region' );
            }
        }
    }

    public static function sync_train_data( $job_id, $values ) {
        if ( isset( $values['job']['job_train'] ) ) {
            $term_ids = $values['job']['job_train'];
            if ( ! is_array( $term_ids ) ) $term_ids = array( $term_ids );
            $term_ids = array_map( 'intval', $term_ids );
            wp_set_object_terms( $job_id, $term_ids, 'job_listing_train' );
        }
    }
}