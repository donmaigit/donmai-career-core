<?php
/**
 * Job Filters Template (PHP Sorting Version)
 * Handles Sidebar Layout: Nav, Filters (Search/Loc/Type/Train), and Results
 */

// 1. GET CONFIG
$search_config = get_option( 'dcc_search_config', array() );
$filter_order  = get_option( 'dcc_search_filter_order', array('location', 'train', 'category', 'type') );

$loc_mode = isset($search_config['location_mode']) ? $search_config['location_mode'] : 'dropdown';
$cat_mode = isset($search_config['category_mode']) ? $search_config['category_mode'] : 'modal';
$train_active = ! empty($search_config['train']);

if( isset($search_config['location']) && $search_config['location'] == 1 && empty($search_config['location_mode']) ) $loc_mode = 'dropdown';
if( isset($search_config['category']) && $search_config['category'] == 1 && empty($search_config['category_mode']) ) $cat_mode = 'modal';

$nav_menu = get_option( 'dcc_nav_menu', array() ); 
$types    = get_terms( array( 'taxonomy' => 'job_listing_type', 'hide_empty' => false ) );
$cats_flat = get_terms( array( 'taxonomy' => 'job_listing_category', 'hide_empty' => false ) );

// --- HELPER: PHP SORT FUNCTION (FAIL-SAFE) ---
if ( ! function_exists('dcc_sort_terms_by_meta') ) {
    function dcc_sort_terms_by_meta( $terms ) {
        if ( empty($terms) || is_wp_error($terms) ) return array();
        
        usort( $terms, function( $a, $b ) {
            // Get meta (returns '' if missing, casts to 0)
            $order_a = (int) get_term_meta( $a->term_id, 'dcc_sort_order', true );
            $order_b = (int) get_term_meta( $b->term_id, 'dcc_sort_order', true );
            
            // If orders are equal (or both missing), fallback to ID for stability
            if ( $order_a === $order_b ) {
                return $a->term_id - $b->term_id;
            }
            return $order_a - $order_b;
        });
        return $terms;
    }
}

// --- FETCH & SORT: REGIONS ---
// We fetch ALL terms (orderby=none) then sort in PHP to prevent missing items
$raw_regions = get_terms( array( 
    'taxonomy'   => 'job_listing_region', 
    'hide_empty' => false,
    'orderby'    => 'none' 
));
$regions = dcc_sort_terms_by_meta( $raw_regions );

// --- FETCH & SORT: TRAINS ---
$trains = array();
if ( $train_active ) {
    $raw_trains = get_terms( array( 
        'taxonomy'   => 'job_listing_train', 
        'hide_empty' => false,
        'orderby'    => 'none'
    ));
    $trains = dcc_sort_terms_by_meta( $raw_trains );
}

// 3. PREPARE HIERARCHIES
$cat_parents = array(); $cat_children = array();
if ( $cat_mode === 'modal' && ! is_wp_error( $cats_flat ) ) {
    foreach ( $cats_flat as $term ) {
        if ( $term->parent == 0 ) $cat_parents[] = $term;
        else $cat_children[$term->parent][] = $term;
    }
}

$reg_parents = array(); $reg_children = array();
if ( ! is_wp_error( $regions ) ) {
    foreach ( $regions as $term ) {
        if ( $term->parent == 0 ) $reg_parents[] = $term;
        else $reg_children[$term->parent][] = $term;
    }
}

$train_parents = array(); $train_children = array();
if ( $train_active && ! is_wp_error( $trains ) ) {
    foreach ( $trains as $term ) {
        if ( $term->parent == 0 ) $train_parents[] = $term;
        else $train_children[$term->parent][] = $term;
    }
}

if(empty($nav_menu)) {
    $nav_menu = array(array('label' => 'Jobs', 'url' => '#', 'icon' => '<svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>'));
}
?>

<div class="dcc-main-container">
    
    <div class="dcc-col-nav">
        <?php if( !empty($nav_menu) ): foreach($nav_menu as $item): 
            $label = apply_filters( 'wpml_translate_single_string', $item['label'], 'donmai-career-core', 'Nav Label: ' . $item['label'] );
            $icon_html = preg_replace('/fill="[^"]*"/', '', $item['icon']); 
            $icon_html = str_replace('<svg', '<svg fill="' . esc_attr($item['icon_color']) . '"', $icon_html);
            $text_style = 'color: ' . esc_attr($item['text_color']) . ';';
        ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="dcc-nav-item">
                <div class="dcc-nav-icon"><?php echo $icon_html; ?></div>
                <span style="<?php echo $text_style; ?>"><?php echo esc_html($label); ?></span>
            </a>
        <?php endforeach; endif; ?>
    </div>

    <div class="dcc-col-filters">
        <div class="dcc-filter-header-row">
            <span id="dcc-results-count">Loading...</span>
            <a href="#" id="dcc-global-clear">Clear filters</a>
        </div>

        <div class="dcc-search-box">
            <input type="text" id="dcc-keywords" placeholder="Search jobs..." autocomplete="off">
            <svg class="dcc-search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        </div>

        <div class="dcc-scroll-content">
            <?php foreach ( $filter_order as $filter_key ) : ?>
                <?php if ( $filter_key === 'location' && $loc_mode !== 'disabled' ) : ?>
                    <div class="dcc-filter-group open">
                        <h4 class="dcc-accordion-header">Location</h4>
                        <?php if ( $loc_mode === 'dropdown' ) : ?>
                            <div class="dcc-location-row">
                                <div class="dcc-select-wrapper" style="flex-grow:1;">
                                    <select id="dcc-location-select" class="dcc-select">
                                        <option value="">Select Location</option>
                                        <?php foreach ( $regions as $region ) : ?>
                                            <option value="<?php echo esc_attr($region->slug); ?>"><?php echo esc_html($region->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" id="dcc-add-location" class="dcc-btn-add">+Add</button>
                            </div>
                            <div id="dcc-location-tags" class="dcc-active-tags"></div>
                        <?php elseif ( $loc_mode === 'modal' ) : ?>
                             <div style="display:flex; align-items:center;">
                                <button type="button" class="dcc-modal-trigger dcc-sidebar-btn" data-target="dcc-modal-search-location">
                                    <span>Select Location</span>
                                </button>   
                                <span class="dcc-clear-trigger" data-target="dcc-modal-search-location">Clear filter</span>
                            </div>
                            <div id="dcc-modal-search-location" class="dcc-modal-overlay dcc-accordion-modal" data-taxonomy="job_listing_region" data-mode="search" style="display:none;">
                                <div class="dcc-modal-box">
                                    <div class="dcc-modal-header">
                                        <h3>Job Location</h3>
                                        <span class="dcc-modal-close">&times;</span>
                                    </div>
                                    <div class="dcc-modal-instruction-bar">Please choose items then click “Add Checked Items”.</div>
                                    <div class="dcc-modal-body no-padding">
                                        <?php if ( ! empty( $reg_parents ) ) : ?>
                                            <?php foreach ( $reg_parents as $parent ) : ?>
                                                <div class="dcc-accordion-heading js-dcc-accordion-trigger" data-slug="<?php echo esc_attr($parent->slug); ?>">
                                                    <span><?php echo esc_html($parent->name); ?></span>
                                                    <span class="dcc-arrow">▼</span>
                                                </div>
                                                <div class="dcc-accordion-content" style="display:none;">
                                                    <div class="dcc-modal-checklist">
                                                        <div class="dcc-modal-item-all">
                                                            <label class="dcc-modal-item-label">
                                                                <input type="checkbox" class="dcc-filter-input dcc-checkbox-all" value="<?php echo esc_attr($parent->slug); ?>" data-tax="job_listing_region" data-pref-slug="<?php echo esc_attr($parent->slug); ?>">
                                                                <span><strong>ALL</strong></span>
                                                            </label>
                                                        </div>
                                                        <?php if ( isset( $reg_children[ $parent->term_id ] ) ) : ?>
                                                            <?php foreach ( $reg_children[ $parent->term_id ] as $child ) : ?>
                                                                <div class="dcc-level-2-wrapper">
                                                                    <label class="dcc-modal-item-label">
                                                                        <input type="checkbox" class="dcc-filter-input dcc-level-2-checkbox" value="<?php echo esc_attr($child->slug); ?>" data-tax="job_listing_region" data-pref-slug="<?php echo esc_attr($parent->slug); ?>">
                                                                        <span><?php echo esc_html($child->name); ?></span>
                                                                    </label>
                                                                    <?php if ( isset( $reg_children[ $child->term_id ] ) ) : ?>
                                                                        <div class="dcc-level-3-container" style="display:none;">
                                                                            <?php foreach ( $reg_children[ $child->term_id ] as $grandchild ) : ?>
                                                                                <label class="dcc-modal-item-label">
                                                                                    <input type="checkbox" class="dcc-filter-input dcc-level-3-checkbox" value="<?php echo esc_attr($grandchild->slug); ?>" data-tax="job_listing_region" data-pref-slug="<?php echo esc_attr($parent->slug); ?>">
                                                                                    <span><?php echo esc_html($grandchild->name); ?></span>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="padding:20px;">No locations found.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dcc-modal-footer">
                                        <button type="button" class="dcc-modal-add">Add Checked Items</button>
                                        <button type="button" class="dcc-modal-cancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ( $filter_key === 'train' && $train_active ) : ?>
                    <div class="dcc-filter-group open dcc-train-wrapper" style="opacity:0.5; pointer-events:none;">
                        <h4 class="dcc-accordion-header">Train Line / Station</h4>
                        <div style="display:flex; align-items:center;">
                            <button type="button" class="dcc-modal-trigger dcc-sidebar-btn" id="dcc-train-btn" data-target="dcc-modal-search-train">
                                <span>Select Line/Station</span>
                            </button>   
                            <span class="dcc-clear-trigger" data-target="dcc-modal-search-train">Clear filter</span>
                        </div>
                        <small style="display:block; margin-top:5px; color:#999;" id="dcc-train-hint">Please select a Location first.</small>
                        <div id="dcc-modal-search-train" class="dcc-modal-overlay dcc-accordion-modal" data-taxonomy="job_listing_train" data-mode="search" style="display:none;">
                            <div class="dcc-modal-box">
                                <div class="dcc-modal-header">
                                    <h3>Select Train Line / Station</h3>
                                    <span class="dcc-modal-close">&times;</span>
                                </div>
                                <div class="dcc-modal-instruction-bar">Filter by Line and Station</div>
                                <div class="dcc-modal-body no-padding">
                                    <?php if ( ! empty( $train_parents ) ) : ?>
                                        <?php foreach ( $train_parents as $parent ) : ?>
                                            <div class="dcc-train-pref-group" data-pref-slug="<?php echo esc_attr($parent->slug); ?>" style="display:none;">
                                                <div class="dcc-accordion-heading js-dcc-accordion-trigger active">
                                                    <span><?php echo esc_html($parent->name); ?></span>
                                                    <span class="dcc-arrow">▼</span>
                                                </div>
                                                <div class="dcc-accordion-content" style="display:block;">
                                                    <div class="dcc-modal-checklist">
                                                        <?php if ( isset( $train_children[ $parent->term_id ] ) ) : ?>
                                                            <?php foreach ( $train_children[ $parent->term_id ] as $operator ) : ?>
                                                                <div class="dcc-level-2-wrapper">
                                                                    <div style="font-weight:bold; padding:5px 0; color:#0a1f68; border-bottom:1px solid #eee; margin-bottom:5px;">
                                                                        <?php echo esc_html($operator->name); ?>
                                                                    </div>
                                                                    <?php if ( isset( $train_children[ $operator->term_id ] ) ) : ?>
                                                                        <?php foreach ( $train_children[ $operator->term_id ] as $line ) : ?>
                                                                            <div class="dcc-level-3-wrapper" style="margin-left:0;">
                                                                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                                                                                    <label class="dcc-modal-item-label" style="margin-bottom:0; display:flex; align-items:center; gap:6px;">
                                                                                        <input type="checkbox" class="dcc-filter-input dcc-level-3-checkbox" value="<?php echo esc_attr($line->slug); ?>" data-tax="job_listing_train">
                                                                                        <span><?php echo esc_html($line->name); ?></span>
                                                                                        <span class="dcc-station-count" style="display:none; font-size:11px; background:#e8f0fe; color:#1967d2; padding:1px 6px; border-radius:10px; font-weight:bold;"></span>
                                                                                    </label>
                                                                                    <span class="dcc-select-all" style="font-size:11px; color:#1967d2; cursor:pointer; text-decoration:underline;">Select All</span>
                                                                                </div>
                                                                                <?php if ( isset( $train_children[ $line->term_id ] ) ) : ?>
                                                                                    <div class="dcc-level-4-container" style="display:none;">
                                                                                        <?php foreach ( $train_children[ $line->term_id ] as $station ) : ?>
                                                                                            <label class="dcc-modal-item-label">
                                                                                                <input type="checkbox" class="dcc-filter-input dcc-level-4-checkbox" value="<?php echo esc_attr($station->slug); ?>" data-tax="job_listing_train">
                                                                                                <span><?php echo esc_html($station->name); ?></span>
                                                                                            </label>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="padding:20px;">No train data found. Import it first!</div>
                                    <?php endif; ?>
                                </div>
                                <div class="dcc-modal-footer">
                                    <button type="button" class="dcc-modal-add">Add Checked Items</button>
                                    <button type="button" class="dcc-modal-cancel">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ( $filter_key === 'category' && $cat_mode !== 'disabled' ) : ?>
                    <div class="dcc-filter-group open">
                        <h4 class="dcc-accordion-header">Category</h4>
                        <?php if ( $cat_mode === 'dropdown' ) : ?>
                            <div class="dcc-location-row">
                                <div class="dcc-select-wrapper" style="flex-grow:1;">
                                    <select id="dcc-category-select" class="dcc-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ( $cats_flat as $cat ) : ?>
                                            <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" id="dcc-add-category" class="dcc-btn-add">+Add</button>
                            </div>
                            <div id="dcc-category-tags" class="dcc-active-tags"></div>
                        <?php elseif ( $cat_mode === 'modal' ) : ?>
                            <div style="display:flex; align-items:center;">
                                <button type="button" class="dcc-modal-trigger dcc-sidebar-btn" data-target="dcc-modal-search-category">
                                    <span>Select Category</span>
                                </button>   
                                <span class="dcc-clear-trigger" data-target="dcc-modal-search-category">Clear filter</span>
                            </div>
                            <div id="dcc-modal-search-category" class="dcc-modal-overlay dcc-accordion-modal" data-taxonomy="job_listing_category" data-mode="search" style="display:none;">
                                <div class="dcc-modal-box">
                                    <div class="dcc-modal-header">
                                        <h3>Job Category</h3>
                                        <span class="dcc-modal-close">&times;</span>
                                    </div>
                                    <div class="dcc-modal-instruction-bar">Please choose items then click “Add Checked Items”.</div>
                                    <div class="dcc-modal-body no-padding">
                                        <?php if ( ! empty( $cat_parents ) ) : ?>
                                            <?php foreach ( $cat_parents as $parent ) : ?>
                                                <div class="dcc-accordion-heading js-dcc-accordion-trigger">
                                                    <span><?php echo esc_html($parent->name); ?></span>
                                                    <span class="dcc-arrow">▼</span>
                                                </div>
                                                <div class="dcc-accordion-content" style="display:none;">
                                                    <div class="dcc-modal-checklist">
                                                        <div class="dcc-modal-item-all">
                                                            <label class="dcc-modal-item-label">
                                                                <input type="checkbox" class="dcc-filter-input dcc-checkbox-all" value="<?php echo esc_attr($parent->slug); ?>" data-tax="job_listing_category">
                                                                <span><strong>ALL</strong></span>
                                                            </label>
                                                        </div>
                                                        <?php if ( isset( $cat_children[ $parent->term_id ] ) ) : ?>
                                                            <?php foreach ( $cat_children[ $parent->term_id ] as $child ) : ?>
                                                                <div class="dcc-level-2-wrapper">
                                                                    <label class="dcc-modal-item-label">
                                                                        <input type="checkbox" class="dcc-filter-input dcc-level-2-checkbox" value="<?php echo esc_attr($child->slug); ?>" data-tax="job_listing_category">
                                                                        <span><?php echo esc_html($child->name); ?></span>
                                                                    </label>
                                                                    <?php if ( isset( $cat_children[ $child->term_id ] ) ) : ?>
                                                                        <div class="dcc-level-3-container" style="display:none;">
                                                                            <?php foreach ( $cat_children[ $child->term_id ] as $grandchild ) : ?>
                                                                                <label class="dcc-modal-item-label">
                                                                                    <input type="checkbox" class="dcc-filter-input dcc-level-3-checkbox" value="<?php echo esc_attr($grandchild->slug); ?>" data-tax="job_listing_category">
                                                                                    <span><?php echo esc_html($grandchild->name); ?></span>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="padding:20px;">No categories found.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dcc-modal-footer">
                                        <button type="button" class="dcc-modal-add">Add Checked Items</button>
                                        <button type="button" class="dcc-modal-cancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ( $filter_key === 'type' && ! empty( $search_config['type'] ) ) : ?>
                    <div class="dcc-filter-group open">
                        <h4 class="dcc-accordion-header">Job Type</h4>
                        <div class="dcc-grid-2-col">
                            <?php foreach ( $types as $type ) : ?>
                                <label class="dcc-checkbox">
                                    <input type="checkbox" class="dcc-filter-input" value="<?php echo esc_attr($type->slug); ?>" data-tax="job_listing_type">
                                    <span class="dcc-checkmark"></span><span class="dcc-label-text"><?php echo esc_html($type->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dcc-col-results">
        <div class="dcc-loading" style="display:none;">Loading...</div>
        <ul id="dcc-job-list" class="dcc-job-list"></ul>
    </div>
</div>