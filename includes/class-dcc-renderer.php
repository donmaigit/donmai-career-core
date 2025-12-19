<?php

class Donmai_Career_Renderer {

    public function __construct() {
        add_shortcode( 'donmai_career_jobs', array( $this, 'render_shortcode' ) );
        add_action( 'wp_ajax_dcc_filter_jobs', array( $this, 'ajax_filter_jobs' ) );
        add_action( 'wp_ajax_nopriv_dcc_filter_jobs', array( $this, 'ajax_filter_jobs' ) );
    }

    public function render_shortcode( $atts ) {
        wp_enqueue_style( 'dcc-frontend' );
        wp_enqueue_script( 'dcc-core', DCC_PLUGIN_URL . 'assets/js/dcc-core.js', array('jquery'), DCC_VERSION, true );
        
        wp_enqueue_style( 'dcc-modal-css', DCC_PLUGIN_URL . 'assets/css/dcc-modal.css', array(), DCC_VERSION );
        wp_enqueue_script( 'dcc-modal-js', DCC_PLUGIN_URL . 'assets/js/dcc-modal.js', array('jquery'), DCC_VERSION, true );

        wp_localize_script( 'dcc-core', 'dcc_vars', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dcc_nonce' ) 
        ));

        // Taxonomies for Template
        $regions = get_terms( array( 'taxonomy' => 'job_listing_region', 'hide_empty' => false ) );
        $types   = get_terms( array( 'taxonomy' => 'job_listing_type', 'hide_empty' => false ) );
        $cats    = get_terms( array( 'taxonomy' => 'job_listing_category', 'hide_empty' => false ) );

        ob_start();
        include DCC_PLUGIN_DIR . 'templates/job-filters.php'; 
        return ob_get_clean();
    }

    /**
     * AJAX: Returns JSON { html: '...', count: 123 }
     */
    public function ajax_filter_jobs() {
        check_ajax_referer( 'dcc_nonce', 'security' );

        $filters  = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
        $keywords = isset( $_POST['search_keywords'] ) ? sanitize_text_field( $_POST['search_keywords'] ) : ''; 
        
        $tax_query = array( 'relation' => 'AND' );
        
        if ( ! empty( $filters ) ) {
            $sorted = array();
            foreach ( $filters as $f ) { $sorted[$f['taxonomy']][] = $f['term']; }
            foreach ( $sorted as $tax => $terms ) {
                $tax_query[] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => $terms );
            }
        }

        $args = array(
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 50, // Higher limit for "Load More" feel
            'tax_query'      => $tax_query,
            'orderby'        => 'date',
            'order'          => 'DESC'
        );

        if ( ! empty( $keywords ) ) {
            $args['s'] = $keywords;
        }

        $query = new WP_Query( $args );
        $count = $query->found_posts; // Get total count

        ob_start(); // Buffer HTML output

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include DCC_PLUGIN_DIR . 'templates/content-job-listing.php'; 
            }
            wp_reset_postdata();
        } else {
            echo '<div class="dcc-no-results">' . __( 'No jobs found.', 'donmai-career-core' ) . '</div>';
        }
        
        $html = ob_get_clean();

        // Send JSON
        wp_send_json( array(
            'html' => $html,
            'count' => $count . ' jobs matched'
        ) );
    }
}