<?php
/**
 * Frontend Region Drilldown
 */
$selected = isset( $field['value'] ) ? $field['value'] : array();
if ( ! is_array( $selected ) && ! empty( $selected ) ) $selected = array( $selected );

$l1_id = 0; $l2_id = 0;
if ( ! empty( $selected ) ) {
    $deepest = $selected[0];
    $ancestors = get_ancestors( $deepest, 'job_listing_region', 'taxonomy' );
    $ancestors = array_reverse( $ancestors );
    if ( isset( $ancestors[0] ) ) $l1_id = $ancestors[0];
    if ( isset( $ancestors[1] ) ) $l2_id = $ancestors[1];
    if ( empty($ancestors) ) $l1_id = $deepest;
}

// 1. Fetch ALL Prefectures
$prefectures = get_terms( array(
    'taxonomy' => 'job_listing_region', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'none'
));

// 2. Sort robustly in PHP (Fixes random order issue)
if ( ! empty($prefectures) && ! is_wp_error($prefectures) ) {
    usort( $prefectures, function( $a, $b ) {
        $order_a = (int) get_term_meta( $a->term_id, 'dcc_sort_order', true );
        $order_b = (int) get_term_meta( $b->term_id, 'dcc_sort_order', true );
        if ( $order_a === $order_b ) return $a->term_id - $b->term_id;
        return $order_a - $order_b;
    });
}
?>

<div class="dcc-fe-drilldown-wrapper">
    <div id="dcc-fe-region-collector">
        <?php foreach($selected as $id): ?>
            <input type="hidden" name="job_region[]" value="<?php echo esc_attr($id); ?>" class="dcc-saved-region">
        <?php endforeach; ?>
    </div>

    <div class="dcc-fe-row">
        <label>Prefecture</label>
        <select id="dcc-fe-region-l1" class="dcc-fe-select">
            <option value="">Select Prefecture</option>
            <?php foreach ( $prefectures as $pref ) : ?>
                <option value="<?php echo $pref->term_id; ?>" <?php selected( $l1_id, $pref->term_id ); ?>><?php echo esc_html( $pref->name ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="dcc-fe-row" id="dcc-fe-row-l2" style="<?php echo $l1_id ? '' : 'display:none;'; ?>" data-selected="<?php echo $l2_id; ?>">
        <label>City / Area</label>
        <input type="text" class="dcc-fe-search" placeholder="Search cities..." data-target="dcc-fe-list-l2">
        <div class="dcc-fe-list" id="dcc-fe-list-l2"><span class="dcc-loader"></span></div>
    </div>

    <div class="dcc-fe-row" id="dcc-fe-row-l3" style="display:none;">
        <label>Ward / District</label>
        <input type="text" class="dcc-fe-search" placeholder="Search wards..." data-target="dcc-fe-list-l3">
        <div class="dcc-fe-list" id="dcc-fe-list-l3"></div>
    </div>
</div>