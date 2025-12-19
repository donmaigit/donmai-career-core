<?php
/**
 * Frontend Train Picker
 */
$selected = isset( $field['value'] ) ? $field['value'] : array();
if ( ! is_array( $selected ) && ! empty( $selected ) ) $selected = array( $selected );

// 1. Fetch Prefectures
$prefectures = get_terms( array(
    'taxonomy' => 'job_listing_train', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'none'
));

// 2. Sort robustly
if ( ! empty($prefectures) && ! is_wp_error($prefectures) ) {
    usort( $prefectures, function( $a, $b ) {
        $order_a = (int) get_term_meta( $a->term_id, 'dcc_sort_order', true );
        $order_b = (int) get_term_meta( $b->term_id, 'dcc_sort_order', true );
        if ( $order_a === $order_b ) return $a->term_id - $b->term_id;
        return $order_a - $order_b;
    });
}
?>

<div class="dcc-fe-train-wrapper">
    <div id="dcc-fe-train-bucket">
        <?php 
        if ( ! empty( $selected ) ) {
            foreach( $selected as $id ) {
                $term = get_term( $id, 'job_listing_train' );
                if ( $term && ! is_wp_error($term) ) {
                    echo '<div class="dcc-fe-bucket-item" data-id="'.esc_attr($id).'">'.esc_html($term->name).'<span class="dcc-fe-remove">×</span><input type="hidden" name="job_train[]" value="'.esc_attr($id).'"></div>';
                }
            }
        } else {
            echo '<span class="dcc-fe-placeholder">No stations selected. Use the filters below.</span>';
        }
        ?>
    </div>
    <input type="hidden" name="job_train[]" value="">

    <div class="dcc-fe-finder">
        <div class="dcc-fe-row">
            <label>1. Prefecture</label>
            <select id="dcc-fe-train-l1" class="dcc-fe-select">
                <option value="">Select Prefecture</option>
                <?php foreach ( $prefectures as $pref ) : ?>
                    <option value="<?php echo $pref->term_id; ?>"><?php echo esc_html( $pref->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dcc-fe-row" id="dcc-fet-row-l2" style="display:none;">
            <label>2. Operator</label>
            <select id="dcc-fe-train-l2" class="dcc-fe-select"><option value="">Select Operator</option></select>
        </div>

        <div class="dcc-fe-row" id="dcc-fet-row-l3" style="display:none;">
            <label>3. Line</label>
            <select id="dcc-fe-train-l3" class="dcc-fe-select"><option value="">Select Line</option></select>
        </div>

        <div class="dcc-fe-row" id="dcc-fet-row-l4" style="display:none;">
            <label>4. Stations</label>
            <input type="text" class="dcc-fe-search" placeholder="Filter stations..." data-target="dcc-fe-train-list">
            <div class="dcc-fe-list" id="dcc-fe-train-list"></div>
        </div>
    </div>
</div>