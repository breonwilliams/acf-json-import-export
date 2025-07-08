<?php
/**
 * Plugin Name: ACF JSON Import/Export Tool
 * Plugin URI: https://breonwilliams.com/plugins/acf-json-import-export
 * Description: Adds admin tools to export and import ACF JSON field data for any post type.
 * Version: 1.4
 * Author: Breon Williams
 * Author URI: https://breonwilliams.com
 * Text Domain: acf-json-import-export
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace ACFJsonImportExport;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACF_JSON_IE_VERSION', '1.4');
define('ACF_JSON_IE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACF_JSON_IE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_post_export_acf_data', array($this, 'handle_export'));
        add_action('admin_post_import_acf_json', array($this, 'handle_import'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_acf_preview_import', array($this, 'ajax_preview_import'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('tools_page_export-acf-fields', 'tools_page_acf-json-import'))) {
            return;
        }
        
        wp_enqueue_script(
            'acf-json-import-export-admin',
            ACF_JSON_IE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ACF_JSON_IE_VERSION,
            true
        );
        
        wp_localize_script('acf-json-import-export-admin', 'acfJsonImportExport', array(
            'nonce' => wp_create_nonce('acf_import_export_nonce'),
            'loading' => __('Loading preview...', 'acf-json-import-export'),
            'error' => __('An error occurred while loading the preview.', 'acf-json-import-export'),
            'selectItem' => __('Please select an item first.', 'acf-json-import-export'),
            'noInput' => __('Please provide JSON data or select a file.', 'acf-json-import-export'),
        ));
        
        wp_enqueue_style(
            'acf-json-import-export-admin',
            ACF_JSON_IE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ACF_JSON_IE_VERSION
        );
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('acf-json-import-export', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check if ACF is active
     */
    public function check_dependencies() {
        if (!function_exists('get_fields')) {
            add_action('admin_notices', array($this, 'display_acf_required_notice'));
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }
    
    /**
     * Display ACF required notice
     */
    public function display_acf_required_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('ACF JSON Import/Export Tool requires Advanced Custom Fields to be installed and activated.', 'acf-json-import-export'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Ensure we're in admin area
        if (!is_admin()) {
            return;
        }
        
        add_submenu_page(
            'tools.php',
            __('Export ACF Fields', 'acf-json-import-export'),
            __('Export ACF Fields', 'acf-json-import-export'),
            'manage_options',
            'export-acf-fields',
            array($this, 'render_export_page')
        );

        add_submenu_page(
            'tools.php',
            __('Import ACF JSON', 'acf-json-import-export'),
            __('Import ACF JSON', 'acf-json-import-export'),
            'manage_options',
            'acf-json-import',
            array($this, 'render_import_page')
        );
    }
    
    /**
     * Get all public post types
     */
    private function get_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $excluded = array('attachment');
        
        foreach ($excluded as $exclude) {
            unset($post_types[$exclude]);
        }
        
        return $post_types;
    }
    
    /**
     * Get posts by post type
     */
    private function get_posts_by_type($post_type = 'page') {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'any'
        );
        
        return get_posts($args);
    }
    
    /**
     * Render export page
     */
    public function render_export_page() {
        $post_types = $this->get_post_types();
        $selected_post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'page';
        $posts = $this->get_posts_by_type($selected_post_type);
        ?>
        <div class="wrap">
            <h1><?php _e('Export ACF Fields', 'acf-json-import-export'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="export-acf-fields">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="post_type"><?php _e('Post Type:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <select name="post_type" id="post_type" onchange="this.form.submit()">
                                <?php foreach ($post_types as $post_type): ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="export_acf_data">
                <?php wp_nonce_field('acf_export_nonce', 'export_nonce'); ?>
                <input type="hidden" name="post_type" value="<?php echo esc_attr($selected_post_type); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="post_id"><?php _e('Select Item:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <select name="post_id" id="post_id" required>
                                <option value="">-- <?php _e('Select an item', 'acf-json-import-export'); ?> --</option>
                                <?php foreach ($posts as $post): ?>
                                    <option value="<?php echo esc_attr($post->ID); ?>">
                                        <?php echo esc_html($post->post_title ?: '(No Title)'); ?> (ID: <?php echo $post->ID; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php _e('Export ACF JSON', 'acf-json-import-export'); ?>">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        $post_types = $this->get_post_types();
        $selected_post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'page';
        $posts = $this->get_posts_by_type($selected_post_type);
        
        // Display messages
        $this->display_import_messages();
        ?>
        <div class="wrap">
            <h1><?php _e('Import ACF JSON', 'acf-json-import-export'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="acf-json-import">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="post_type"><?php _e('Post Type:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <select name="post_type" id="post_type" onchange="this.form.submit()">
                                <?php foreach ($post_types as $post_type): ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
            
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="import_acf_json">
                <?php wp_nonce_field('acf_import_nonce', 'import_nonce'); ?>
                <input type="hidden" name="post_type" value="<?php echo esc_attr($selected_post_type); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="post_id"><?php _e('Select Item:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <select name="post_id" id="post_id" required>
                                <option value="">-- <?php _e('Select an item', 'acf-json-import-export'); ?> --</option>
                                <?php foreach ($posts as $post): ?>
                                    <option value="<?php echo esc_attr($post->ID); ?>">
                                        <?php echo esc_html($post->post_title ?: '(No Title)'); ?> (ID: <?php echo $post->ID; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="acf_json_paste"><?php _e('Paste JSON Code:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <textarea name="acf_json_paste" id="acf_json_paste" rows="10" cols="80" class="large-text code" placeholder='{"field_name": "value", "another_field": "another value"}'></textarea>
                            <p class="description"><?php _e('Paste your ACF JSON data here', 'acf-json-import-export'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="acf_json_file"><?php _e('OR Upload JSON File:', 'acf-json-import-export'); ?></label></th>
                        <td>
                            <input type="file" name="acf_json_file" id="acf_json_file" accept=".json">
                            <p class="description"><?php _e('Maximum file size: 2MB', 'acf-json-import-export'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Options:', 'acf-json-import-export'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_backup" id="enable-backup" value="1">
                                <?php _e('Create backup before import', 'acf-json-import-export'); ?>
                            </label>
                            <div class="backup-options">
                                <p class="description"><?php _e('A backup of current ACF data will be created before importing new data.', 'acf-json-import-export'); ?></p>
                            </div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="acf-preview-import" class="button"><?php _e('Preview Import', 'acf-json-import-export'); ?></button>
                    <input type="submit" class="button button-primary" value="<?php _e('Import', 'acf-json-import-export'); ?>">
                </p>
                
                <div class="acf-preview-container"></div>
            </form>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for import preview
     */
    public function ajax_preview_import() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_import_export_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'acf-json-import-export')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'acf-json-import-export')));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'acf-json-import-export')));
        }
        
        // Get JSON data
        $json_data = null;
        
        if (!empty($_POST['json_data'])) {
            $json_data = json_decode(stripslashes($_POST['json_data']), true);
        } elseif (!empty($_FILES['json_file'])) {
            $file_content = file_get_contents($_FILES['json_file']['tmp_name']);
            $json_data = json_decode($file_content, true);
        }
        
        if (!$json_data || !is_array($json_data)) {
            wp_send_json_error(array('message' => __('Invalid JSON data', 'acf-json-import-export')));
        }
        
        // Get current fields
        $current_fields = get_fields($post_id) ?: array();
        
        // Generate preview HTML
        $html = $this->generate_preview_html($current_fields, $json_data);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Generate preview HTML
     */
    private function generate_preview_html($current_fields, $new_fields) {
        ob_start();
        ?>
        <div class="notice notice-info">
            <h3><?php _e('Import Preview', 'acf-json-import-export'); ?></h3>
            <p><?php _e('Review the changes that will be made:', 'acf-json-import-export'); ?></p>
        </div>
        
        <table class="acf-preview-table">
            <thead>
                <tr>
                    <th><?php _e('Field Name', 'acf-json-import-export'); ?></th>
                    <th><?php _e('Current Value', 'acf-json-import-export'); ?></th>
                    <th><?php _e('New Value', 'acf-json-import-export'); ?></th>
                    <th><?php _e('Status', 'acf-json-import-export'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $all_keys = array_unique(array_merge(array_keys($current_fields), array_keys($new_fields)));
                sort($all_keys);
                
                foreach ($all_keys as $key):
                    $current_value = isset($current_fields[$key]) ? $current_fields[$key] : null;
                    $new_value = isset($new_fields[$key]) ? $new_fields[$key] : null;
                    
                    $row_class = '';
                    $status = '';
                    
                    if ($current_value === null && $new_value !== null) {
                        $row_class = 'acf-field-new-only';
                        $status = __('New', 'acf-json-import-export');
                    } elseif ($current_value !== null && $new_value === null) {
                        $row_class = 'acf-field-remove';
                        $status = __('Will be removed', 'acf-json-import-export');
                    } elseif ($current_value != $new_value) {
                        $row_class = 'acf-field-changed';
                        $status = __('Changed', 'acf-json-import-export');
                    } else {
                        $status = __('Unchanged', 'acf-json-import-export');
                    }
                    ?>
                    <tr class="<?php echo esc_attr($row_class); ?>">
                        <td><strong><?php echo esc_html($key); ?></strong></td>
                        <td class="acf-field-current"><?php echo esc_html($this->format_value_for_preview($current_value)); ?></td>
                        <td class="acf-field-new"><?php echo esc_html($this->format_value_for_preview($new_value)); ?></td>
                        <td><?php echo esc_html($status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format value for preview display
     */
    private function format_value_for_preview($value) {
        if (is_null($value)) {
            return '(empty)';
        }
        
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (strlen($value) > 100) {
            return substr($value, 0, 100) . '...';
        }
        
        return $value;
    }
    
    /**
     * Display import messages
     */
    private function display_import_messages() {
        if (isset($_GET['imported'])) {
            $backup_created = isset($_GET['backup']) && $_GET['backup'] == '1';
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('ACF fields imported successfully!', 'acf-json-import-export'); ?></p>
                <?php if ($backup_created): ?>
                    <p><?php _e('Backup was created before import.', 'acf-json-import-export'); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }

        if (isset($_GET['error'])) {
            $error_messages = array(
                'invalid_post' => __('Invalid item selected.', 'acf-json-import-export'),
                'no_input' => __('No JSON input provided. Please either paste JSON or upload a file.', 'acf-json-import-export'),
                'invalid_file' => __('Invalid file type. Please upload a JSON file.', 'acf-json-import-export'),
                'invalid_json' => __('Invalid JSON format.', 'acf-json-import-export'),
                'empty_json' => __('The JSON is empty or invalid.', 'acf-json-import-export'),
                'upload_error' => __('File upload failed. Please try again.', 'acf-json-import-export'),
                'update_failed' => __('Failed to update some fields.', 'acf-json-import-export'),
                'file_too_large' => __('File size exceeds the 2MB limit.', 'acf-json-import-export'),
                'backup_failed' => __('Failed to create backup.', 'acf-json-import-export'),
            );
            $error = sanitize_key($_GET['error']);
            $message = isset($error_messages[$error]) ? $error_messages[$error] : __('An error occurred.', 'acf-json-import-export');
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Handle export
     */
    public function handle_export() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'acf-json-import-export'));
        }

        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'acf_export_nonce')) {
            wp_die(__('Security check failed', 'acf-json-import-export'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post_type = sanitize_key($_POST['post_type'] ?? 'page');
        
        if (!$post_id) {
            wp_redirect(admin_url('tools.php?page=export-acf-fields&error=invalid_post&post_type=' . $post_type));
            exit;
        }

        // Verify the post exists
        $post = get_post($post_id);
        if (!$post) {
            wp_redirect(admin_url('tools.php?page=export-acf-fields&error=post_not_found&post_type=' . $post_type));
            exit;
        }

        $fields = get_fields($post_id);
        if (!$fields) {
            wp_redirect(admin_url('tools.php?page=export-acf-fields&error=no_fields&post_type=' . $post_type));
            exit;
        }

        $slug = sanitize_title($post->post_title ?: 'post-' . $post_id);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="acf-export-' . $slug . '.json"');
        echo json_encode($fields, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Handle import
     */
    public function handle_import() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'acf-json-import-export'));
        }

        if (!isset($_POST['import_nonce']) || !wp_verify_nonce($_POST['import_nonce'], 'acf_import_nonce')) {
            wp_die(__('Security check failed', 'acf-json-import-export'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post_type = sanitize_key($_POST['post_type'] ?? 'page');
        $enable_backup = isset($_POST['enable_backup']) && $_POST['enable_backup'] == '1';
        
        if (!$post_id) {
            wp_redirect(admin_url('tools.php?page=acf-json-import&error=invalid_post&post_type=' . $post_type));
            exit;
        }

        // Verify the post exists
        $post = get_post($post_id);
        if (!$post) {
            wp_redirect(admin_url('tools.php?page=acf-json-import&error=invalid_post&post_type=' . $post_type));
            exit;
        }

        // Create backup if requested
        $backup_created = false;
        if ($enable_backup) {
            $backup_result = $this->create_backup($post_id);
            if (is_wp_error($backup_result)) {
                wp_redirect(admin_url('tools.php?page=acf-json-import&error=backup_failed&post_type=' . $post_type));
                exit;
            }
            $backup_created = true;
        }

        // Get JSON data
        $json_data = $this->get_import_json_data($post_type);
        
        if (is_wp_error($json_data)) {
            wp_redirect(admin_url('tools.php?page=acf-json-import&error=' . $json_data->get_error_code() . '&post_type=' . $post_type));
            exit;
        }

        // Update fields
        $results = $this->update_acf_fields($post_id, $json_data);
        
        // Redirect with appropriate message
        if ($results['error_count'] > 0 && $results['update_count'] == 0) {
            wp_redirect(admin_url('tools.php?page=acf-json-import&error=update_failed&post_type=' . $post_type));
        } else {
            $redirect_url = admin_url('tools.php?page=acf-json-import&imported=1&post_type=' . $post_type);
            if ($backup_created) {
                $redirect_url .= '&backup=1';
            }
            wp_redirect($redirect_url);
        }
        exit;
    }
    
    /**
     * Create backup of current ACF data
     */
    private function create_backup($post_id) {
        $current_fields = get_fields($post_id);
        if (!$current_fields) {
            return true; // No fields to backup
        }
        
        // Create backup directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/acf-json-backups';
        
        if (!file_exists($backup_dir)) {
            if (!wp_mkdir_p($backup_dir)) {
                return new \WP_Error('backup_dir_failed', __('Failed to create backup directory', 'acf-json-import-export'));
            }
        }
        
        // Create backup file
        $post = get_post($post_id);
        $timestamp = current_time('Y-m-d-H-i-s');
        $filename = sprintf('backup-%s-%s-%s.json', $post->post_name, $post_id, $timestamp);
        $filepath = $backup_dir . '/' . $filename;
        
        $backup_data = array(
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'backup_date' => current_time('mysql'),
            'fields' => $current_fields
        );
        
        if (file_put_contents($filepath, json_encode($backup_data, JSON_PRETTY_PRINT)) === false) {
            return new \WP_Error('backup_write_failed', __('Failed to write backup file', 'acf-json-import-export'));
        }
        
        return true;
    }
    
    /**
     * Get import JSON data from either paste or file
     */
    private function get_import_json_data($post_type) {
        $json_data = null;

        // Check if pasted JSON is provided
        if (!empty($_POST['acf_json_paste'])) {
            $json_data = json_decode(stripslashes($_POST['acf_json_paste']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new \WP_Error('invalid_json', __('Invalid JSON format.', 'acf-json-import-export'));
            }
        }
        // Check if file is uploaded
        elseif (isset($_FILES['acf_json_file']) && $_FILES['acf_json_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = $_FILES['acf_json_file'];
            
            // Validate file type
            $file_type = wp_check_filetype($uploaded_file['name']);
            $allowed_types = array('json' => 'application/json');
            
            if (!in_array($file_type['type'], $allowed_types) && $file_type['ext'] !== 'json') {
                return new \WP_Error('invalid_file', __('Invalid file type.', 'acf-json-import-export'));
            }

            // Check file size (2MB max)
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($uploaded_file['size'] > $max_size) {
                return new \WP_Error('file_too_large', __('File size exceeds limit.', 'acf-json-import-export'));
            }

            // Read and parse JSON
            $json_content = file_get_contents($uploaded_file['tmp_name']);
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new \WP_Error('invalid_json', __('Invalid JSON format.', 'acf-json-import-export'));
            }
        }
        else {
            return new \WP_Error('no_input', __('No JSON input provided.', 'acf-json-import-export'));
        }

        // Validate JSON data
        if (!is_array($json_data) || empty($json_data)) {
            return new \WP_Error('empty_json', __('Empty or invalid JSON.', 'acf-json-import-export'));
        }

        return $json_data;
    }
    
    /**
     * Update ACF fields
     */
    private function update_acf_fields($post_id, $json_data) {
        $update_count = 0;
        $error_count = 0;
        
        foreach ($json_data as $key => $value) {
            // Sanitize field key
            $field_key = sanitize_text_field($key);
            
            // Update field
            if (update_field($field_key, $value, $post_id)) {
                $update_count++;
            } else {
                $error_count++;
            }
        }
        
        return array(
            'update_count' => $update_count,
            'error_count' => $error_count
        );
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'export-acf-fields') {
            return;
        }

        if (isset($_GET['error'])) {
            $error_messages = array(
                'invalid_post' => __('Please select a valid item.', 'acf-json-import-export'),
                'post_not_found' => __('The selected item was not found.', 'acf-json-import-export'),
                'no_fields' => __('No ACF fields found for the selected item.', 'acf-json-import-export'),
            );
            $error = sanitize_key($_GET['error']);
            $message = isset($error_messages[$error]) ? $error_messages[$error] : __('An error occurred.', 'acf-json-import-export');
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::get_instance();
});

// Activation hook to flush rewrite rules
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

