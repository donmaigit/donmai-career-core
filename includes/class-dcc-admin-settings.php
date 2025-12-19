<?php

class Donmai_Career_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_save' ) );
        
        // NEW: Load Scripts for Drag & Drop
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_footer', array( $this, 'print_js' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 'CAREER Core', 'CAREER Core', 'manage_options', 'career-core', array( $this, 'render_dashboard' ), 'dashicons-google', 56 );
        add_submenu_page( 'career-core', 'Field Editor', 'Field Editor', 'manage_options', 'dcc-fields', array( $this, 'render_field_manager' ) );
    }

    /**
     * Load jQuery UI Sortable (Built-in to WP)
     */
    public function enqueue_assets( $hook ) {
        // Only load on our Field Editor page
        if ( strpos($hook, 'dcc-fields') === false ) return;
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    public function handle_save() {
        if ( isset( $_POST['dcc_save_fields_nonce'] ) && wp_verify_nonce( $_POST['dcc_save_fields_nonce'], 'dcc_save_fields' ) ) {
            
            // 1. Save Priorities (These are now updated by JS drag & drop)
            if ( isset( $_POST['dcc_priority'] ) ) {
                $priorities = array_map( 'floatval', $_POST['dcc_priority'] );
                update_option( 'dcc_field_priorities', $priorities );
            }

            // 2. Save Visibility
            $all_posted_keys = array_keys( $_POST['dcc_priority'] ); 
            $visible_keys = isset( $_POST['dcc_visible'] ) ? array_keys( $_POST['dcc_visible'] ) : array();
            $hidden_fields = array_diff( $all_posted_keys, $visible_keys );
            update_option( 'dcc_hidden_fields', $hidden_fields );

            add_settings_error( 'dcc_messages', 'dcc_message', __( 'Field settings saved.', 'donmai-career-core' ), 'updated' );
        }
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>CAREER Core Dashboard</h1>
            <div class="card" style="padding:20px; max-width:600px;">
                <h2>Welcome to Donmai CAREER Core</h2>
                <p><strong>Active Mode:</strong> Google Material Design</p>
                <p>To manage form fields, go to the <strong>Field Editor</strong> tab.</p>
            </div>
        </div>
        <?php
    }

    public function render_field_manager() {
        $saved_priorities = get_option( 'dcc_field_priorities', array() );
        $hidden_fields    = get_option( 'dcc_hidden_fields', array() );

        // 1. Define Defaults
        $defaults = array(
            'job' => array(
                'job_title'       => array( 'label' => 'Job Title', 'priority' => 1 ),
                'job_location'    => array( 'label' => 'Location (Text)', 'priority' => 2 ),
                'job_region'      => array( 'label' => 'Location (Region)', 'priority' => 2 ),
                'job_type'        => array( 'label' => 'Job Type', 'priority' => 3 ),
                'job_category'    => array( 'label' => 'Job Category', 'priority' => 4 ),
                'job_description' => array( 'label' => 'Description', 'priority' => 10 ),
                'application'     => array( 'label' => 'Application Email/URL', 'priority' => 11 ),
                'job_salary'      => array( 'label' => 'Salary', 'priority' => 12 ),
                'salary_currency' => array( 'label' => 'Salary Currency', 'priority' => 13 ),
                'salary_unit'     => array( 'label' => 'Salary Unit', 'priority' => 14 ),
            ),
            'company' => array(
                'company_name'    => array( 'label' => 'Company Name', 'priority' => 1 ),
                'company_website' => array( 'label' => 'Website', 'priority' => 2 ),
                'company_tagline' => array( 'label' => 'Tagline', 'priority' => 3 ),
                'company_video'   => array( 'label' => 'Video', 'priority' => 4 ),
                'company_twitter' => array( 'label' => 'Twitter', 'priority' => 5 ),
                'company_logo'    => array( 'label' => 'Logo', 'priority' => 6 ),
            )
        );

        // 2. Run Filters (Capture ACF/Plugins)
        try {
            $final_fields = apply_filters( 'submit_job_form_fields', $defaults );
        } catch ( Exception $e ) {
            $final_fields = $defaults;
        }

        // 3. Flatten
        $all_fields = array_merge( 
            isset($final_fields['job']) ? (array)$final_fields['job'] : array(), 
            isset($final_fields['company']) ? (array)$final_fields['company'] : array() 
        );

        // 4. Apply Saved Priorities
        foreach ( $all_fields as $key => $field ) {
            if ( isset( $saved_priorities[$key] ) ) {
                $all_fields[$key]['priority'] = $saved_priorities[$key];
            }
        }

        // 5. Sort
        uasort( $all_fields, function($a, $b) {
            $p1 = isset($a['priority']) ? $a['priority'] : 99;
            $p2 = isset($b['priority']) ? $b['priority'] : 99;
            return $p1 <=> $p2;
        } );

        settings_errors( 'dcc_messages' );
        ?>
        <div class="wrap">
            <h1>Field Editor</h1>
            <p><strong>Drag and drop</strong> rows to reorder fields. Uncheck "Visible" to hide them.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'dcc_save_fields', 'dcc_save_fields_nonce' ); ?>
                
                <table class="widefat fixed striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th> <th style="width: 50px;">Visible</th>
                            <th>Field Label</th>
                            <th>Key</th>
                            <th>Type</th>
                            <th style="width: 70px;">Order</th>
                        </tr>
                    </thead>
                    <tbody id="dcc-sortable-rows">
                        <?php foreach ( $all_fields as $key => $field ) : 
                            $priority = isset($field['priority']) ? $field['priority'] : 99;
                            $label = isset($field['label']) ? $field['label'] : $key;
                            $type = isset($field['type']) ? $field['type'] : 'text';
                            $is_visible = ! in_array( $key, $hidden_fields );
                        ?>
                            <tr class="dcc-field-row">
                                <td style="cursor: move; color: #999;">
                                    <span class="dashicons dashicons-menu dcc-drag-handle"></span>
                                </td>
                                <td>
                                    <input type="checkbox" name="dcc_visible[<?php echo esc_attr($key); ?>]" value="1" <?php checked($is_visible); ?>>
                                </td>
                                <td><strong><?php echo esc_html( $label ); ?></strong></td>
                                <td><code><?php echo esc_html( $key ); ?></code></td>
                                <td><?php echo esc_html( $type ); ?></td>
                                <td>
                                    <input type="number" step="0.1" class="dcc-priority-input" name="dcc_priority[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($priority); ?>" style="width: 60px;" readonly>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * JS for Drag & Drop
     */
    public function print_js() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'dcc-fields' ) === false ) return;
        ?>
        <script>
        jQuery(document).ready(function($){
            
            // Helper to recalculate numbers
            function updatePriorities() {
                $('#dcc-sortable-rows tr').each(function(index) {
                    // Update the number input based on visual order (Index + 1)
                    $(this).find('.dcc-priority-input').val( index + 1 );
                });
            }

            // Init Sortable
            $('#dcc-sortable-rows').sortable({
                handle: '.dcc-drag-handle',
                placeholder: 'ui-state-highlight',
                axis: 'y',
                update: function(event, ui) {
                    updatePriorities();
                    // Optional: Highlight changed rows
                    ui.item.css('background-color', '#f0f4ff');
                }
            });

            // Initial visual update (just in case)
            updatePriorities();
        });
        </script>
        <style>
            .ui-state-highlight { height: 40px; background: #f9f9f9; border: 1px dashed #ccc; }
            .dcc-drag-handle:hover { color: #333; }
        </style>
        <?php
    }
}