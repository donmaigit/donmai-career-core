<?php

class Donmai_Career_Admin_Drilldown {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'replace_region_metabox' ), 100 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // AJAX: Register for BOTH Admin and Frontend (Guests)
        add_action( 'wp_ajax_dcc_get_term_children', array( $this, 'ajax_get_children' ) );
        add_action( 'wp_ajax_nopriv_dcc_get_term_children', array( $this, 'ajax_get_children' ) );
    }

    public function replace_region_metabox() {
        remove_meta_box( 'job_listing_regiondiv', 'job_listing', 'side' );
        add_meta_box( 'dcc_region_drilldown', __( 'Job Region', 'donmai-career-core' ), array( $this, 'render_metabox' ), 'job_listing', 'side', 'core' );
    }

    public function enqueue_assets( $hook ) {
        global $post;
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && 'job_listing' === get_post_type( $post ) ) {
            wp_enqueue_script( 'dcc-admin-drilldown', DCC_PLUGIN_URL . 'assets/js/dcc-admin-drilldown.js', array( 'jquery' ), DCC_VERSION, true );
            wp_enqueue_style( 'dcc-admin-drilldown', DCC_PLUGIN_URL . 'assets/css/dcc-admin-drilldown.css', array(), DCC_VERSION );
            
            wp_localize_script( 'dcc-admin-drilldown', 'dcc_admin_vars', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dcc_drilldown_nonce' )
            ));
        }
    }

    public function render_metabox( $post ) {
        $terms = get_the_terms( $post->ID, 'job_listing_region' );
        $saved_ids = ($terms && !is_wp_error($terms)) ? wp_list_pluck($terms, 'term_id') : array();

        $l1_id = 0; $l2_id = 0;
        if ( ! empty( $saved_ids ) ) {
            $deepest = $saved_ids[0]; 
            $ancestors = get_ancestors( $deepest, 'job_listing_region', 'taxonomy' );
            $ancestors = array_reverse( $ancestors );
            if ( isset( $ancestors[0] ) ) $l1_id = $ancestors[0];
            if ( isset( $ancestors[1] ) ) $l2_id = $ancestors[1];
            if ( empty($ancestors) ) $l1_id = $deepest;
        }

        // Fetch Prefectures & Sort PHP-side
        $prefectures = get_terms( array(
            'taxonomy'   => 'job_listing_region',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'none'
        ));
        $prefectures = $this->sort_terms( $prefectures );

        ?>
        <div class="dcc-drilldown-wrapper">
            <div id="dcc-submission-container">
                <?php foreach($saved_ids as $id): ?>
                    <input type="hidden" name="tax_input[job_listing_region][]" value="<?php echo esc_attr($id); ?>" class="dcc-saved-term">
                <?php endforeach; ?>
                <input type="hidden" name="tax_input[job_listing_region][]" value=""> 
            </div>

            <div class="dcc-dd-row">
                <label class="dcc-label-heading">Prefecture</label>
                <select id="dcc-region-l1" class="dcc-dd-select">
                    <option value="">Select Prefecture</option>
                    <?php foreach ( $prefectures as $pref ) : ?>
                        <option value="<?php echo $pref->term_id; ?>" <?php selected( $l1_id, $pref->term_id ); ?>><?php echo esc_html( $pref->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dcc-dd-row" id="dcc-row-l2" style="<?php echo $l1_id ? '' : 'display:none;'; ?>" data-selected="<?php echo $l2_id; ?>">
                <label class="dcc-label-heading">City / Area</label>
                <input type="text" class="dcc-search-box" placeholder="Search cities..." data-target="dcc-list-l2">
                <div class="dcc-scroll-list" id="dcc-list-l2"><span class="spinner is-active" style="float:none; margin:10px;"></span></div>
            </div>

            <div class="dcc-dd-row" id="dcc-row-l3" style="display:none;">
                <label class="dcc-label-heading">Ward / District</label>
                <input type="text" class="dcc-search-box" placeholder="Search wards..." data-target="dcc-list-l3">
                <div class="dcc-scroll-list" id="dcc-list-l3"></div>
            </div>
        </div>
        <?php
    }

    public function ajax_get_children() {
        check_ajax_referer( 'dcc_drilldown_nonce', 'security' );
        
        $parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
        $taxonomy  = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : 'job_listing_region'; 
        
        if ( ! in_array( $taxonomy, array( 'job_listing_region', 'job_listing_train' ) ) ) wp_send_json_error( 'Invalid taxonomy' );
        if ( ! $parent_id ) wp_send_json_error();

        $terms = get_terms( array( 
            'taxonomy'   => $taxonomy, 
            'parent'     => $parent_id, 
            'hide_empty' => false,
            'orderby'    => 'none' 
        ));

        if ( is_wp_error( $terms ) ) wp_send_json_error();

        // PHP Sort
        $terms = $this->sort_terms( $terms );

        $options = array();
        foreach ( $terms as $term ) {
            $options[] = array( 'id' => $term->term_id, 'name' => $term->name );
        }
        wp_send_json_success( $options );
    }

    private function sort_terms( $terms ) {
        if ( empty($terms) || is_wp_error($terms) ) return array();
        usort( $terms, function( $a, $b ) {
            $order_a = (int) get_term_meta( $a->term_id, 'dcc_sort_order', true );
            $order_b = (int) get_term_meta( $b->term_id, 'dcc_sort_order', true );
            if ( $order_a === $order_b ) return $a->term_id - $b->term_id;
            return $order_a - $order_b;
        });
        return $terms;
    }
}