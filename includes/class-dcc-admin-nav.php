<?php
class Donmai_Career_Nav_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_footer', array( $this, 'print_js' ) );
    }

    public function add_menu() {
        add_submenu_page(
            'career-core', 'Nav Menu', 'Nav Menu', 'manage_options', 'dcc-nav', array( $this, 'render_page' )
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos($hook, 'dcc-nav') === false ) return;
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    public function save_settings() {
        if ( isset( $_POST['dcc_save_nav_nonce'] ) && wp_verify_nonce( $_POST['dcc_save_nav_nonce'], 'dcc_save_nav' ) ) {
            $raw_items = isset($_POST['dcc_nav_items']) ? $_POST['dcc_nav_items'] : array();
            $clean_items = array();

            if ( ! empty( $raw_items ) ) {
                foreach ( $raw_items as $item ) {
                    if ( empty( $item['label'] ) && empty( $item['url'] ) ) continue;

                    $clean_items[] = array(
                        'label'      => sanitize_text_field( $item['label'] ),
                        'url'        => sanitize_text_field( $item['url'] ),
                        'icon_color' => sanitize_hex_color( $item['icon_color'] ),
                        'text_color' => sanitize_hex_color( $item['text_color'] ),
                        'icon'       => wp_unslash( $item['icon'] ) // Remove slashes for DB
                    );
                    
                    do_action( 'wpml_register_single_string', 'donmai-career-core', 'Nav Label: ' . $item['label'], $item['label'] );
                }
            }

            update_option( 'dcc_nav_menu', $clean_items );
            add_settings_error( 'dcc_messages', 'dcc_message', 'Menu updated successfully.', 'updated' );
        }
    }

    public function render_page() {
        $menu = get_option( 'dcc_nav_menu', array() );
        ?>
        <div class="wrap">
            <h1>Left Rail Menu Manager</h1>
            <p>Drag rows to reorder. Paste raw SVG code.</p>

            <form method="post">
                <?php wp_nonce_field( 'dcc_save_nav', 'dcc_save_nav_nonce' ); ?>
                
                <div id="dcc-nav-list">
                    <?php 
                    if ( ! empty( $menu ) ) {
                        foreach( $menu as $index => $item ) {
                            $this->render_row( $index, $item );
                        }
                    }
                    ?>
                </div>

                <div style="margin-top: 20px; padding: 10px; background: #f0f0f1; border-top: 1px solid #ccc;">
                    <button type="button" class="button" id="dcc-add-row"><strong>+ Add New Item</strong></button>
                    <input type="submit" class="button button-primary" value="Save Menu Changes" style="margin-left: 10px;">
                </div>
            </form>

            <div id="dcc-row-template" style="display:none;">
                <?php $this->render_row( 'TEMPLATE_INDEX', array('label'=>'', 'url'=>'#', 'icon'=>'', 'icon_color'=>'#5f6368', 'text_color'=>'#5f6368') ); ?>
            </div>

            <style>
                .dcc-nav-row { background:#fff; border:1px solid #ccd0d4; padding:15px; margin-bottom:10px; border-left: 4px solid #1a73e8; display:flex; gap:15px; align-items:flex-start; }
                .dcc-drag-handle { cursor:move; font-size:20px; color:#ccc; padding-top:10px; }
                .dcc-col { flex: 1; }
                .dcc-nav-row label { display:block; font-weight:bold; margin-bottom:5px; font-size:12px; }
                .dcc-nav-row input[type=text] { width:100%; }
                .dcc-nav-row textarea { width:100%; height:50px; font-family:monospace; font-size:11px; }
                .dcc-icon-preview { width: 30px; height: 30px; border: 1px dashed #ccc; margin-top: 5px; display: flex; align-items: center; justify-content: center; }
                .dcc-icon-preview svg { width: 100%; height: 100%; fill: #555; }
            </style>
        </div>
        <?php
    }

    private function render_row( $i, $item ) {
        $label = isset($item['label']) ? $item['label'] : '';
        $url   = isset($item['url']) ? $item['url'] : '';
        
        // FIX: Remove slashes so SVG renders correctly in Admin
        $icon  = isset($item['icon']) ? wp_unslash($item['icon']) : '';
        
        $icon_col = isset($item['icon_color']) ? $item['icon_color'] : '#5f6368';
        $text_col = isset($item['text_color']) ? $item['text_color'] : '#5f6368';
        ?>
        <div class="dcc-nav-row">
            <div class="dcc-drag-handle">☰</div>
            
            <div class="dcc-col" style="flex:0 0 150px;">
                <label>Label</label>
                <input type="text" name="dcc_nav_items[<?php echo $i; ?>][label]" value="<?php echo esc_attr($label); ?>">
            </div>

            <div class="dcc-col" style="flex:0 0 150px;">
                <label>URL</label>
                <input type="text" name="dcc_nav_items[<?php echo $i; ?>][url]" value="<?php echo esc_attr($url); ?>">
            </div>
            
            <div class="dcc-col">
                <label>SVG Code</label>
                <textarea name="dcc_nav_items[<?php echo $i; ?>][icon]" class="dcc-icon-input"><?php echo esc_textarea($icon); ?></textarea>
                <div class="dcc-icon-preview"><?php echo $icon; ?></div>
            </div>

            <div class="dcc-col" style="flex:0 0 120px;">
                <label>Icon Color</label>
                <input type="text" class="dcc-color-field" name="dcc_nav_items[<?php echo $i; ?>][icon_color]" value="<?php echo esc_attr($icon_col); ?>" data-default-color="#5f6368">
            </div>

            <div class="dcc-col" style="flex:0 0 120px;">
                <label>Text Color</label>
                <input type="text" class="dcc-color-field" name="dcc_nav_items[<?php echo $i; ?>][text_color]" value="<?php echo esc_attr($text_col); ?>" data-default-color="#5f6368">
            </div>

            <div><button type="button" class="button dcc-remove-row" style="margin-top:20px; color:#a00;">Delete</button></div>
        </div>
        <?php
    }

    public function print_js() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'dcc-nav' ) === false ) return;
        ?>
        <script>
        jQuery(document).ready(function($){
            
            // Function to bind Color Picker to inputs
            function bindColorPickers( scope ) {
                scope.find('.dcc-color-field').wpColorPicker();
            }

            // Init existing
            bindColorPickers( $('#dcc-nav-list') );

            $('#dcc-nav-list').sortable({ handle: '.dcc-drag-handle' });

            $('#dcc-add-row').on('click', function(){
                var index = $('#dcc-nav-list .dcc-nav-row').length + Math.floor(Math.random()*1000);
                var template = $('#dcc-row-template').html();
                template = template.replace(/TEMPLATE_INDEX/g, index);
                
                // Append new row
                var $newRow = $(template);
                $('#dcc-nav-list').append($newRow);
                
                // Bind Color Picker ONLY to the new row
                bindColorPickers( $newRow );
            });

            $(document).on('click', '.dcc-remove-row', function(){
                $(this).closest('.dcc-nav-row').remove();
            });

            $(document).on('input', '.dcc-icon-input', function(){
                $(this).next('.dcc-icon-preview').html( $(this).val() );
            });
        });
        </script>
        <?php
    }
}