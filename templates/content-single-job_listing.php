<?php
/**
 * Single Job Listing Template (SEO Smart Version)
 * We let the Theme handle the H1 Title.
 */
global $post;
$id = get_the_ID();

// 1. DATA
$dept = function_exists('get_field') ? get_field( 'dcc_department', $id ) : '';
$internal_id = function_exists('get_field') ? get_field( 'dcc_internal_id', $id ) : '';

$region_list = get_the_term_list( $id, 'job_listing_region', '', ', ', '' );
$region = ($region_list && !is_wp_error($region_list)) ? strip_tags($region_list) : '';

$type_list = get_the_term_list( $id, 'job_listing_type', '', ', ', '' );
$type = ($type_list && !is_wp_error($type_list)) ? strip_tags($type_list) : '';

// 2. ICONS
$icon_pin = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:text-bottom; margin-right:4px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
$icon_case = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:text-bottom; margin-right:4px;"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>';
?>

<div class="dcc-single-wrapper">
    
    <div class="dcc-single-header">
        
        <?php if($dept): ?>
            <div class="dcc-single-dept"><?php echo esc_html($dept); ?></div>
        <?php endif; ?>
        
        <div class="dcc-single-meta">
            <?php if($region): ?>
                <span class="dcc-meta-pill">
                    <?php echo $icon_pin . esc_html($region); ?>
                </span>
            <?php endif; ?>
            
            <?php if($type): ?>
                <span class="dcc-meta-pill">
                    <?php echo $icon_case . esc_html($type); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="dcc-single-grid">
        
        <div class="dcc-single-content">
            <div class="dcc-description-body">
                <?php the_content(); ?>
            </div>
        </div>

        <div class="dcc-single-sidebar">
            <div class="dcc-apply-card">
                <h3 style="margin-top:0;"><?php _e('Job Details', 'donmai-career-core'); ?></h3>
                
                <ul class="dcc-details-list">
                    <?php if($internal_id): ?>
                        <li>
                            <small>Job ID</small>
                            <span><?php echo esc_html($internal_id); ?></span>
                        </li>
                    <?php endif; ?>
                    
                    <li>
                        <small>Date Posted</small>
                        <span><?php the_time('M j, Y'); ?></span>
                    </li>

                     <?php if( function_exists('the_job_salary') && get_the_job_salary() ): ?>
                         <li>
                            <small>Salary</small>
                            <span><?php the_job_salary(); ?></span>
                         </li>
                    <?php endif; ?>
                </ul>

                <div class="dcc-apply-btn-wrapper">
                    <?php 
                    if ( function_exists('candidates_can_apply') && candidates_can_apply() ) {
                        get_job_manager_template( 'job-application.php' ); 
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>