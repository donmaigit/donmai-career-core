<?php
/**
 * Frontend Taxonomy Modal Field (Used for Categories)
 * Renders a "Select" button + Modal + Selected Bucket
 */

$taxonomy = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'job_listing_category';
$selected = isset( $field['value'] ) ? $field['value'] : array();
if ( ! is_array( $selected ) && ! empty( $selected ) ) $selected = array( $selected );

// Fetch Terms (Hierarchy)
$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
$parents = array(); 
$children = array();

if ( ! is_wp_error( $terms ) ) {
    foreach ( $terms as $term ) {
        if ( $term->parent == 0 ) $parents[] = $term;
        else $children[$term->parent][] = $term;
    }
}
?>

<div class="dcc-fe-modal-wrapper">
    
    <div id="dcc-fe-cat-bucket" class="dcc-fe-bucket">
        <?php 
        if ( ! empty( $selected ) ) {
            foreach( $selected as $id ) {
                $term = get_term( $id, $taxonomy );
                if ( $term && ! is_wp_error($term) ) {
                    echo '<div class="dcc-fe-bucket-item" data-id="'.esc_attr($id).'">'.esc_html($term->name).'<span class="dcc-fe-remove-cat">×</span><input type="hidden" name="'.esc_attr($name).'[]" value="'.esc_attr($id).'"></div>';
                }
            }
        } else {
            echo '<span class="dcc-fe-placeholder">No categories selected.</span>';
        }
        ?>
    </div>
    <input type="hidden" name="<?php echo esc_attr($name); ?>[]" value="">

    <button type="button" class="dcc-btn-secondary dcc-open-cat-modal">
        Select Category
    </button>

    <div id="dcc-fe-cat-modal" class="dcc-fe-modal-overlay" style="display:none;">
        <div class="dcc-fe-modal-box">
            <div class="dcc-fe-modal-header">
                <h3>Select Category</h3>
                <span class="dcc-fe-modal-close">&times;</span>
            </div>
            
            <div class="dcc-fe-modal-body">
                <div class="dcc-fe-checklist">
                    <?php if ( ! empty( $parents ) ) : ?>
                        <?php foreach ( $parents as $parent ) : ?>
                            <div class="dcc-fe-cat-group">
                                <label class="dcc-fe-cat-label parent">
                                    <input type="checkbox" class="dcc-fe-cat-check" value="<?php echo esc_attr($parent->term_id); ?>" data-name="<?php echo esc_attr($parent->name); ?>" name="ignore_me[]" <?php checked( in_array($parent->term_id, $selected) ); ?>>
                                    <strong><?php echo esc_html($parent->name); ?></strong>
                                </label>

                                <?php if ( isset( $children[ $parent->term_id ] ) ) : ?>
                                    <div class="dcc-fe-cat-children">
                                        <?php foreach ( $children[ $parent->term_id ] as $child ) : ?>
                                            <label class="dcc-fe-cat-label child">
                                                <input type="checkbox" class="dcc-fe-cat-check" value="<?php echo esc_attr($child->term_id); ?>" data-name="<?php echo esc_attr($child->name); ?>" name="ignore_me[]" <?php checked( in_array($child->term_id, $selected) ); ?>>
                                                <?php echo esc_html($child->name); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No categories found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dcc-fe-modal-footer">
                <button type="button" class="dcc-fe-modal-close dcc-btn-primary">Done</button>
            </div>
        </div>
    </div>

</div>