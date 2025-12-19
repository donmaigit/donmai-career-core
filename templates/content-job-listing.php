<?php
// Retrieve Data safely
$id = get_the_ID();

// 1. Get ACF Fields
$dept = function_exists('get_field') ? get_field( 'dcc_department', $id ) : '';

// 2. Get Taxonomies
$region_list = get_the_term_list( $id, 'job_listing_region', '', ', ', '' );
$region_names = ($region_list && !is_wp_error($region_list)) ? strip_tags($region_list) : '';

$type_list = get_the_term_list( $id, 'job_listing_type', '', ', ', '' );
$type_names = ($type_list && !is_wp_error($type_list)) ? strip_tags($type_list) : '';

// 3. SVG Icons
$icon_building = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>';
$icon_pin      = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
$icon_case     = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>';

// 4. LANGUAGE LOGIC (The new part)
$content = strip_tags( get_the_content() ); // Remove HTML first
$locale = get_locale(); // Check current language

if ( strpos( $locale, 'ja' ) === 0 ) {
    // JAPANESE: Use Multi-byte Character Count
    // 80 Japanese characters is about equal to 30 English words visually
    if ( mb_strlen( $content ) > 80 ) {
        $summary = mb_substr( $content, 0, 80 ) . '...';
    } else {
        $summary = $content;
    }
} else {
    // ENGLISH/OTHERS: Use Word Count
    $summary = wp_trim_words( $content, 50, '...' );
}
?>

<li class="dcc-card">
    <a href="<?php the_permalink(); ?>" class="dcc-card-link">
        
        <h3 class="dcc-job-title"><?php the_title(); ?></h3>
        
        <div class="dcc-meta-row">
            <span class="dcc-meta-item">
                <?php echo $icon_building; ?> 
                Donmai Inc.
            </span>
            
            <?php if($dept): ?>
                <span class="dcc-meta-item">
                    <?php echo esc_html($dept); ?>
                </span>
            <?php endif; ?>

            <?php if($region_names): ?>
                <span class="dcc-meta-item">
                    <?php echo $icon_pin; ?> 
                    <?php echo esc_html($region_names); ?>
                </span>
            <?php endif; ?>

            <?php if($type_names): ?>
                <span class="dcc-meta-item">
                    <?php echo $icon_case; ?> 
                    <?php echo esc_html($type_names); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="dcc-qualifications">
            <strong><?php _e('Summary:', 'donmai-career-core'); ?></strong>
            <div class="dcc-qual-content">
                <?php echo $summary; ?>
            </div>
        </div>

        <div class="dcc-footer">
            <span class="dcc-btn"><?php _e('Learn more', 'donmai-career-core'); ?></span>
        </div>
    </a>
</li>