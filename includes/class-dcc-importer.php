<?php

class Donmai_Career_Importer {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'wp_ajax_dcc_import_batch', array( $this, 'ajax_import_batch' ) );
        // NEW: Delete Batch Action
        add_action( 'wp_ajax_dcc_delete_batch', array( $this, 'ajax_delete_batch' ) );
    }

    public function add_menu() {
        add_submenu_page(
            'career-core', 'Data Import', 'Data Import', 'manage_options', 'dcc-data-import', array( $this, 'render_page' )
        );
    }

    // --- BATCH IMPORT (Unchanged) ---
    public function ajax_import_batch() {
        check_ajax_referer( 'dcc_import_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.' );

        $type  = isset( $_POST['import_type'] ) ? sanitize_text_field( $_POST['import_type'] ) : '';
        $index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : 0;

        $filename = ($type === 'train') ? 'japan-trains.json' : 'japan-regions.json';
        $json_file = DCC_PLUGIN_DIR . 'assets/data/' . $filename;

        if ( ! file_exists( $json_file ) ) wp_send_json_error( "File not found." );
        $data = json_decode( file_get_contents( $json_file ), true );

        if ( $index >= count( $data ) ) wp_send_json_success( array( 'done' => true ) );

        $item = $data[ $index ];
        set_time_limit( 30 );

        if ( $type === 'train' ) {
            $this->import_single_train_pref( $item );
        } else {
            $this->import_single_region_pref( $item );
        }

        wp_send_json_success( array( 'done' => false, 'index' => $index + 1, 'total' => count( $data ), 'message' => "Imported: " . $item['name'] ));
    }

    // --- NEW: BATCH DELETE ---
    public function ajax_delete_batch() {
        check_ajax_referer( 'dcc_import_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied.' );

        $type = isset( $_POST['import_type'] ) ? sanitize_text_field( $_POST['import_type'] ) : '';
        $taxonomy = ($type === 'train') ? 'job_listing_train' : 'job_listing_region';

        // Fetch 50 terms at a time to delete
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 50, // Delete 50 per batch
            'fields'     => 'ids'
        ) );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            wp_send_json_success( array( 'done' => true, 'message' => 'All deleted!' ) );
        }

        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }

        wp_send_json_success( array(
            'done'    => false,
            'message' => "Deleted batch of " . count($terms)
        ));
    }

    // --- IMPORT LOGIC ---
    private function import_single_train_pref( $pref ) {
        $taxonomy = 'job_listing_train';
        $pref_id = $this->create_term( $pref['name'], $pref['slug'], 0, $taxonomy );
        
        if ( ! empty( $pref['operators'] ) ) {
            foreach ( $pref['operators'] as $op ) {
                $op_id = $this->create_term( $op['name'], $op['slug'], $pref_id, $taxonomy );
                if ( ! empty( $op['lines'] ) ) {
                    foreach ( $op['lines'] as $line ) {
                        $line_id = $this->create_term( $line['name'], $line['slug'], $op_id, $taxonomy );
                        if ( ! empty( $line['stations'] ) ) {
                            foreach ( $line['stations'] as $i => $station ) {
                                if ( is_array( $station ) ) {
                                    $s_name = $station['name'];
                                    $s_slug = $station['slug'];
                                } else {
                                    $s_name = $station;
                                    $s_slug = sanitize_title( $s_name . '-' . $line['slug'] );
                                }
                                $st_id = $this->create_term( $s_name, $s_slug, $line_id, $taxonomy );
                                if ( $st_id ) update_term_meta( $st_id, 'dcc_sort_order', $i );
                            }
                        }
                    }
                }
            }
        }
    }

    private function import_single_region_pref( $l1 ) {
        $taxonomy = 'job_listing_region';
        $l1_id = $this->create_term( $l1['name'], $l1['slug'], 0, $taxonomy );
        if ( ! empty( $l1['children'] ) ) {
            foreach ( $l1['children'] as $l2 ) {
                $l2_id = $this->create_term( $l2['name'], $l2['slug'], $l1_id, $taxonomy );
                if ( ! empty( $l2['children'] ) ) {
                    foreach ( $l2['children'] as $l3 ) {
                        $this->create_term( $l3['name'], $l3['slug'], $l2_id, $taxonomy );
                    }
                }
            }
        }
    }

    private function create_term( $name, $slug, $parent_id, $taxonomy ) {
        $existing = term_exists( $slug, $taxonomy, $parent_id );
        if ( $existing ) return is_array( $existing ) ? $existing['term_id'] : $existing;
        $result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'parent' => $parent_id ) );
        return is_wp_error( $result ) ? 0 : $result['term_id'];
    }

    // --- RENDER PAGE ---
    public function render_page() {
        // Count existing terms for status
        $train_count = wp_count_terms( 'job_listing_train', array('hide_empty'=>false) );
        $region_count = wp_count_terms( 'job_listing_region', array('hide_empty'=>false) );
        ?>
        <div class="wrap">
            <h1>Batch Data Manager</h1>
            
            <div class="card" style="padding:20px; max-width:600px; margin-top:20px;">
                <h3>Step 1: Train Lines</h3>
                <p>Current Terms: <strong><?php echo $train_count; ?></strong></p>
                <div id="dcc-progress-train" style="display:none; margin-bottom:10px;">
                    <div style="background:#ddd; height:20px; border-radius:10px; overflow:hidden;"><div class="dcc-bar" style="background:#0a1f68; width:0%; height:100%; transition:width 0.2s;"></div></div>
                    <small class="dcc-status">Waiting...</small>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="button button-primary dcc-action-btn" data-action="import" data-type="train">Run Train Import</button>
                    <button type="button" class="button dcc-action-btn" style="color:#a00; border-color:#a00;" data-action="delete" data-type="train" onclick="return confirm('Are you sure you want to DELETE ALL train data?');">Delete All Trains</button>
                </div>
            </div>

            <div class="card" style="padding:20px; max-width:600px; margin-top:20px;">
                <h3>Step 2: Regions</h3>
                <p>Current Terms: <strong><?php echo $region_count; ?></strong></p>
                <div id="dcc-progress-region" style="display:none; margin-bottom:10px;">
                    <div style="background:#ddd; height:20px; border-radius:10px; overflow:hidden;"><div class="dcc-bar" style="background:#00a32a; width:0%; height:100%; transition:width 0.2s;"></div></div>
                    <small class="dcc-status">Waiting...</small>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="button button-primary dcc-action-btn" data-action="import" data-type="region">Run Region Import</button>
                    <button type="button" class="button dcc-action-btn" style="color:#a00; border-color:#a00;" data-action="delete" data-type="region" onclick="return confirm('Are you sure you want to DELETE ALL region data?');">Delete All Regions</button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.dcc-action-btn').on('click', function() {
                var btn = $(this);
                var action = btn.data('action'); // 'import' or 'delete'
                var type = btn.data('type');
                
                // UI Setup
                var $container = $('#dcc-progress-' + type);
                var $bar = $container.find('.dcc-bar');
                var $status = $container.find('.dcc-status');
                
                $container.show();
                $bar.css('width', '0%');
                $bar.css('background', action === 'delete' ? '#d63638' : (type === 'train' ? '#0a1f68' : '#00a32a'));
                $status.text( action === 'delete' ? 'Deleting...' : 'Importing...' );
                
                var ajaxAction = (action === 'delete') ? 'dcc_delete_batch' : 'dcc_import_batch';

                function processBatch( index ) {
                    $.ajax({
                        url: ajaxurl, type: 'POST', dataType: 'json',
                        data: { 
                            action: ajaxAction, 
                            security: '<?php echo wp_create_nonce("dcc_import_nonce"); ?>', 
                            import_type: type, 
                            index: index 
                        },
                        success: function(response) {
                            if( ! response.success ) { $status.html('<span style="color:red">Error: ' + response.data + '</span>'); return; }
                            
                            if ( response.data.done ) { 
                                $bar.css('width', '100%'); 
                                $status.html('<strong style="color:green">Done! Refresh page to update counts.</strong>'); 
                            } else {
                                // For delete, we don't know total count easily, so we just pulse/indeterminate or fake progress
                                // For import, we know total.
                                if( action === 'import' ) {
                                    var percent = Math.round( (response.data.index / response.data.total) * 100 );
                                    $bar.css('width', percent + '%');
                                    $status.text(response.data.message + ' (' + percent + '%)');
                                    processBatch( response.data.index );
                                } else {
                                    // Delete Loop
                                    $bar.css('width', '50%'); // Just show activity
                                    $status.text(response.data.message);
                                    processBatch( 0 ); // Keep calling until done returns true
                                }
                            }
                        }
                    });
                }
                processBatch(0);
            });
        });
        </script>
        <?php
    }
}