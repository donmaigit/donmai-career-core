<?php
class Donmai_Career_Loader {
    public function run() {
        // 1. Dependency Checks & Utils
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-dependencies.php';
        if ( ! Donmai_Career_Dependencies::check() ) return;
        
        // 2. Data Structures & Fields
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-acf.php';
        new Donmai_Career_ACF();

        // 3. Modal Field (Frontend Submission)
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-modal-field.php';
        new Donmai_Career_Modal_Field();

        // 4. Submission Logic
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-submission.php';
        new Donmai_Career_Submission();

        // 5. GLOBAL AJAX LOGIC (Drilldown & Trains)
        // Moved outside is_admin() so AJAX works on Frontend Submission too
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin-drilldown.php';
        new Donmai_Career_Admin_Drilldown();

        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin-train-picker.php';
        new Donmai_Career_Admin_Train_Picker();

        // 6. Admin Area Only
        if ( is_admin() ) {
            require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin.php';
            new Donmai_Career_Admin();
            
            require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin-settings.php';
            new Donmai_Career_Settings();
            
            require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin-nav.php';
            new Donmai_Career_Nav_Settings();
            
            require_once DCC_PLUGIN_DIR . 'includes/class-dcc-admin-search.php';
            new Donmai_Career_Search_Settings();

            // DATA IMPORTER (Keep disabled for security unless using)
            // require_once DCC_PLUGIN_DIR . 'includes/class-dcc-importer.php';
            // new Donmai_Career_Importer();
        }

        // 7. Frontend Rendering
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-renderer.php';
        new Donmai_Career_Renderer();

        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-dashboard.php';
        new Donmai_Career_Dashboard();

        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-templates.php';
        new Donmai_Career_Templates();
		
		// Importer
		//require_once DCC_PLUGIN_DIR . 'includes/class-dcc-importer.php';
		//new Donmai_Career_Importer();

        $this->define_public_hooks();
    }

    private function define_public_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'dcc-frontend', DCC_PLUGIN_URL . 'assets/css/dcc-frontend.css', array(), DCC_VERSION );
    }
}