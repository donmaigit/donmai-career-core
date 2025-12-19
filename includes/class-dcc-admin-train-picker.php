<?php

class Donmai_Career_Admin_Train_Picker {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_train_metabox' ), 100 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_train_metabox() {
        remove_meta_box( 'job_listing_traindiv', 'job_listing', 'side' );
        add_meta_box(
            'dcc_train_picker',
            __( 'Train Line / Station', 'donmai-career-core' ),
            array( $this, 'render_metabox' ),
            'job_listing',
            'side',
            'core'
        );
    }

    public function enqueue_assets( $hook ) {
        global $post;
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && 'job_listing' === get_post_type( $post ) ) {
            wp_enqueue_script( 'dcc-admin-train-picker', DCC_PLUGIN_URL . 'assets/js/dcc-admin-train-picker.js', array( 'jquery' ), DCC_VERSION, true );
            wp_enqueue_style( 'dcc-admin-drilldown' ); 
            
            wp_localize_script( 'dcc-admin-train-picker', 'dcc_train_vars', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dcc_drilldown_nonce' )
            ));
        }
    }

    public function render_metabox( $post ) {
        // 1. Get Saved Stations
        $terms = get_the_terms( $post->ID, 'job_listing_train' );
        $saved_data = array();
        
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach( $terms as $t ) {
                $saved_data[] = array( 'id' => $t->term_id, 'name' => $t->name );
            }
        }

        // 2. Fetch Level 1 (Prefectures) - Raw Fetch
        $prefectures = get_terms( array( 
            'taxonomy'   => 'job_listing_train', 
            'parent'     => 0, 
            'hide_empty' => false,
            'orderby'    => 'none'
        ));
        
        // 3. PHP Sort
        $prefectures = $this->sort_terms( $prefectures );

        ?>
        <div class="dcc-drilldown-wrapper">
            
            <label class="dcc-label-heading">Selected Stations</label>
            <div id="dcc-train-bucket">
                <?php if ( empty( $saved_data ) ) : ?>
                    <span class="dcc-bucket-placeholder">No stations selected. Use the filters below to add.</span>
                <?php else: ?>
                    <?php foreach( $saved_data as $item ): ?>
                        <div class="dcc-bucket-item" data-id="<?php echo esc_attr($item['id']); ?>">
                            <?php echo esc_html($item['name']); ?>
                            <span class="dcc-bucket-remove">×</span>
                            <input type="hidden" name="tax_input[job_listing_train][]" value="<?php echo esc_attr($item['id']); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="hidden" name="tax_input[job_listing_train][]" value=""> 

            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">

            <div class="dcc-dd-row">
                <label class="dcc-label-heading">1. Prefecture</label>
                <select id="dcc-train-l1" class="dcc-dd-select">
                    <option value="">Select Prefecture</option>
                    <?php foreach ( $prefectures as $pref ) : ?>
                        <option value="<?php echo $pref->term_id; ?>"><?php echo esc_html( $pref->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dcc-dd-row" id="dcc-t-row-l2" style="display:none;">
                <label class="dcc-label-heading">2. Operator</label>
                <select id="dcc-train-l2" class="dcc-dd-select"><option value="">Select Operator</option></select>
                <span class="spinner"></span>
            </div>

            <div class="dcc-dd-row" id="dcc-t-row-l3" style="display:none;">
                <label class="dcc-label-heading">3. Line</label>
                <select id="dcc-train-l3" class="dcc-dd-select"><option value="">Select Line</option></select>
                <span class="spinner"></span>
            </div>

            <div class="dcc-dd-row" id="dcc-t-row-l4" style="display:none;">
                <label class="dcc-label-heading">4. Stations (Check to Add)</label>
                <input type="text" class="dcc-search-box" placeholder="Filter stations..." data-target="dcc-train-list">
                <div class="dcc-scroll-list" id="dcc-train-list"></div>
            </div>

        </div>
        <?php
    }

    /**
     * HELPER: PHP Sort (Duplicated to avoid dependencies)
     */
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