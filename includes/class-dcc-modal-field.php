<?php
class Donmai_Career_Modal_Field {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // 1. Force Category to use this type (Double check)
        add_filter( 'submit_job_form_fields', array( $this, 'force_category_modal' ), 99999 );
        
        // 2. Render HTML
        add_action( 'job_manager_field_dcc-taxonomy-modal', array( $this, 'render_field_html' ), 10, 2 );
    }

    public function enqueue_assets() {
        wp_enqueue_script( 'dcc-modal-js', DCC_PLUGIN_URL . 'assets/js/dcc-modal.js', array('jquery'), DCC_VERSION, true );
        wp_enqueue_style( 'dcc-modal-css', DCC_PLUGIN_URL . 'assets/css/dcc-modal.css', array(), DCC_VERSION );
    }

    public function force_category_modal( $fields ) {
        if ( isset( $fields['job']['job_category'] ) ) {
            $fields['job']['job_category']['type'] = 'dcc-taxonomy-modal';
            $fields['job']['job_category']['taxonomy'] = 'job_listing_category';
        }
        return $fields;
    }

    public function render_field_html( $key, $field ) {
        $taxonomy = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'job_listing_category';
        
        // 1. Fetch Terms & Build Hierarchy
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        $parents = array(); 
        $children = array();

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                if ( $term->parent == 0 ) $parents[] = $term;
                else $children[$term->parent][] = $term;
            }
        }
        
        // 2. Handle Current Values
        $current_value = ! empty( $field['value'] ) ? $field['value'] : array();
        if ( is_string( $current_value ) ) $current_value = explode( ',', $current_value );
        $current_value = array_map('intval', $current_value);

        // 3. Button Text Logic
        $btn_text = 'Select ' . ( isset($field['label']) ? $field['label'] : 'Items' );
        $selected_names = array();
        if ( ! empty( $current_value ) ) {
            foreach ( $current_value as $tid ) {
                $t = get_term( $tid, $taxonomy );
                if ( $t && ! is_wp_error( $t ) ) $selected_names[] = $t->name;
            }
            if ( ! empty( $selected_names ) ) $btn_text = count($selected_names) . ' Selected';
        }

        ?>
        <div class="dcc-modal-wrapper field-<?php echo esc_attr( $key ); ?>">
            
            <button type="button" 
                    class="dcc-modal-trigger <?php echo !empty($selected_names) ? 'has-selection' : ''; ?>" 
                    data-target="dcc-modal-<?php echo esc_attr( $key ); ?>">
                <span><?php echo esc_html( $btn_text ); ?></span>
            </button>

            <div id="dcc-hidden-inputs-<?php echo esc_attr( $taxonomy ); ?>">
                <?php foreach ( $current_value as $val ) : ?>
                    <input type="hidden" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $val ); ?>">
                <?php endforeach; ?>
            </div>

            <div id="dcc-modal-<?php echo esc_attr( $key ); ?>" class="dcc-modal-overlay" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-field-name="<?php echo esc_attr( $key ); ?>[]" style="display:none;">
                <div class="dcc-modal-box">
                    <div class="dcc-modal-header">
                        <h3><?php echo esc_html( $field['label'] ); ?></h3>
                        <span class="dcc-modal-close">&times;</span>
                    </div>
                    
                    <div class="dcc-modal-body no-padding">
                        <?php if ( ! empty( $parents ) ) : ?>
                            <?php foreach ( $parents as $parent ) : ?>
                                
                                <div class="dcc-accordion-heading js-dcc-accordion-trigger">
                                    <span><?php echo esc_html( $parent->name ); ?></span>
                                    <span class="dcc-arrow">▼</span>
                                </div>
                                
                                <div class="dcc-accordion-content" style="display:none;">
                                    <div class="dcc-modal-checklist">
                                        
                                        <div class="dcc-modal-item-all">
                                            <label class="dcc-modal-item-label">
                                                <input type="checkbox" class="dcc-checkbox-all" value="<?php echo $parent->term_id; ?>" <?php checked( in_array($parent->term_id, $current_value) ); ?>>
                                                <span><strong>ALL</strong></span>
                                            </label>
                                        </div>

                                        <?php if ( isset( $children[ $parent->term_id ] ) ) : ?>
                                            <?php foreach ( $children[ $parent->term_id ] as $l2 ) : ?>
                                                <div class="dcc-level-2-wrapper">
                                                    <label class="dcc-modal-item-label">
                                                        <input type="checkbox" class="dcc-level-2-checkbox" value="<?php echo $l2->term_id; ?>" <?php checked( in_array($l2->term_id, $current_value) ); ?>>
                                                        <span><?php echo esc_html( $l2->name ); ?></span>
                                                    </label>

                                                    <?php if ( isset( $children[ $l2->term_id ] ) ) : ?>
                                                        <div class="dcc-level-3-container" style="display:none;">
                                                            <?php foreach ( $children[ $l2->term_id ] as $l3 ) : ?>
                                                                <div class="dcc-level-3-wrapper">
                                                                    <label class="dcc-modal-item-label">
                                                                        <input type="checkbox" class="dcc-level-3-checkbox" value="<?php echo $l3->term_id; ?>" <?php checked( in_array($l3->term_id, $current_value) ); ?>>
                                                                        <span><?php echo esc_html( $l3->name ); ?></span>
                                                                    </label>

                                                                    <?php if ( isset( $children[ $l3->term_id ] ) ) : ?>
                                                                        <div class="dcc-level-4-container" style="display:none;">
                                                                            <?php foreach ( $children[ $l3->term_id ] as $l4 ) : ?>
                                                                                <label class="dcc-modal-item-label">
                                                                                    <input type="checkbox" class="dcc-level-4-checkbox" value="<?php echo $l4->term_id; ?>" <?php checked( in_array($l4->term_id, $current_value) ); ?>>
                                                                                    <span><?php echo esc_html( $l4->name ); ?></span>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="dcc-modal-footer">
                        <button type="button" class="dcc-modal-add">Add Checked Items</button>
                        <button type="button" class="dcc-modal-cancel">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}