<?php

class Donmai_Career_Search_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_footer', array( $this, 'print_js' ) );
    }

    public function add_menu() {
        add_submenu_page(
            'career-core',
            'Search Settings',
            'Search Settings',
            'manage_options',
            'dcc-search',
            array( $this, 'render_page' )
        );
    }

    public function enqueue_assets( $hook ) {
        // Only load on our Search Settings page
        if ( strpos($hook, 'dcc-search') === false ) return;
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    public function save_settings() {
        if ( isset( $_POST['dcc_save_search_nonce'] ) && wp_verify_nonce( $_POST['dcc_save_search_nonce'], 'dcc_save_search' ) ) {
            
            // 1. Save Config (Modes & Active states)
            $settings = isset($_POST['dcc_search']) ? $_POST['dcc_search'] : array();
            update_option( 'dcc_search_config', $settings );

            // 2. Save Order (New)
            if ( isset( $_POST['dcc_search_order'] ) && is_array( $_POST['dcc_search_order'] ) ) {
                $order = array_map( 'sanitize_text_field', $_POST['dcc_search_order'] );
                update_option( 'dcc_search_filter_order', $order );
            }

            add_settings_error( 'dcc_messages', 'dcc_message', __( 'Search settings saved.', 'donmai-career-core' ), 'updated' );
        }
    }

    public function render_page() {
        $config = get_option( 'dcc_search_config', array() );
        $saved_order = get_option( 'dcc_search_filter_order', array('location', 'category', 'type') );
        
        // Defaults
        $loc_mode = isset($config['location_mode']) ? $config['location_mode'] : 'dropdown';
        $cat_mode = isset($config['category_mode']) ? $config['category_mode'] : 'modal';
        
        // Compat
        if ( isset($config['location']) && $config['location'] == 1 && empty($config['location_mode']) ) $loc_mode = 'dropdown';
        if ( isset($config['category']) && $config['category'] == 1 && empty($config['category_mode']) ) $cat_mode = 'modal';

        // Define Filter Rows Data
        $filters = array(
            'location' => array(
                'label' => 'Location (Region)',
                'desc'  => 'Dropdown (Tags) OR Modal (Popup)',
                'html'  => '<select name="dcc_search[location_mode]" style="width:100%;">
                                <option value="disabled" ' . selected( $loc_mode, 'disabled', false ) . '>Disabled</option>
                                <option value="dropdown" ' . selected( $loc_mode, 'dropdown', false ) . '>Dropdown (Tags)</option>
                                <option value="modal" ' . selected( $loc_mode, 'modal', false ) . '>Modal (Popup)</option>
                            </select>'
            ),
            'category' => array(
                'label' => 'Category (Industry)',
                'desc'  => 'Dropdown (Tags) OR Modal (Popup)',
                'html'  => '<select name="dcc_search[category_mode]" style="width:100%;">
                                <option value="disabled" ' . selected( $cat_mode, 'disabled', false ) . '>Disabled</option>
                                <option value="dropdown" ' . selected( $cat_mode, 'dropdown', false ) . '>Dropdown (Tags)</option>
                                <option value="modal" ' . selected( $cat_mode, 'modal', false ) . '>Modal (Popup)</option>
                            </select>'
            ),
            'type' => array(
                'label' => 'Job Type',
                'desc'  => 'Checkboxes',
                'html'  => '<label><input type="checkbox" name="dcc_search[type]" value="1" ' . checked( isset($config['type']), true, false ) . '> Active</label>'
            )
        );
		
		// Define Filter Rows Data
        $filters = array(
            'location' => array(
                'label' => 'Location (Region)',
                'desc'  => 'Dropdown (Tags) OR Modal (Popup)',
                'html'  => '<select name="dcc_search[location_mode]" style="width:100%;">
                                <option value="disabled" ' . selected( $loc_mode, 'disabled', false ) . '>Disabled</option>
                                <option value="dropdown" ' . selected( $loc_mode, 'dropdown', false ) . '>Dropdown (Tags)</option>
                                <option value="modal" ' . selected( $loc_mode, 'modal', false ) . '>Modal (Popup)</option>
                            </select>'
            ),
            // NEW: TRAIN FILTER
            'train' => array(
                'label' => 'Train / Station',
                'desc'  => 'Modal (Dependent on Location)',
                'html'  => '<label><input type="checkbox" name="dcc_search[train]" value="1" ' . checked( isset($config['train']), true, false ) . '> Active</label>'
            ),
            'category' => array(
                'label' => 'Category (Industry)',
                'desc'  => 'Dropdown (Tags) OR Modal (Popup)',
                'html'  => '<select name="dcc_search[category_mode]" style="width:100%;">
                                <option value="disabled" ' . selected( $cat_mode, 'disabled', false ) . '>Disabled</option>
                                <option value="dropdown" ' . selected( $cat_mode, 'dropdown', false ) . '>Dropdown (Tags)</option>
                                <option value="modal" ' . selected( $cat_mode, 'modal', false ) . '>Modal (Popup)</option>
                            </select>'
            ),
            'type' => array(
                'label' => 'Job Type',
                'desc'  => 'Checkboxes',
                'html'  => '<label><input type="checkbox" name="dcc_search[type]" value="1" ' . checked( isset($config['type']), true, false ) . '> Active</label>'
            )
        );

        // Sort items based on saved order (handling new/missing keys safely)
        $sorted_keys = array_unique( array_merge( $saved_order, array_keys($filters) ) );
        ?>
        <div class="wrap">
            <h1>Search Sidebar Settings</h1>
            <p><strong>Drag and drop</strong> rows to reorder filters on the frontend.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'dcc_save_search', 'dcc_save_search_nonce' ); ?>
                
                <table class="widefat fixed striped" style="max-width: 750px;">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th> <th>Filter Name</th>
                            <th>Display Style</th>
                            <th style="width: 200px;">Mode / Active</th>
                        </tr>
                    </thead>
                    <tbody id="dcc-search-sortable">
                        <?php 
                        foreach ( $sorted_keys as $key ) {
                            if ( ! isset( $filters[$key] ) ) continue;
                            $f = $filters[$key];
                        ?>
                            <tr class="dcc-search-row">
                                <td style="cursor: move; color: #999; vertical-align:middle;">
                                    <span class="dashicons dashicons-menu dcc-drag-handle"></span>
                                    <input type="hidden" name="dcc_search_order[]" value="<?php echo esc_attr($key); ?>">
                                </td>
                                <td><strong><?php echo esc_html($f['label']); ?></strong></td>
                                <td><?php echo esc_html($f['desc']); ?></td>
                                <td><?php echo $f['html']; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    public function print_js() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'dcc-search' ) === false ) return;
        ?>
        <script>
        jQuery(document).ready(function($){
            $('#dcc-search-sortable').sortable({
                handle: '.dcc-drag-handle',
                axis: 'y',
                placeholder: 'ui-state-highlight',
                helper: function(e, ui) {
                    ui.children().each(function() { $(this).width($(this).width()); });
                    return ui;
                }
            });
        });
        </script>
        <style>
            .ui-state-highlight { height: 50px; background: #f9f9f9; border: 1px dashed #ccc; }
            .dcc-drag-handle:hover { color: #333; cursor: grab; }
        </style>
        <?php
    }
}