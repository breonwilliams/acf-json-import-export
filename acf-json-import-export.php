<?php
/**
 * Plugin Name: ACF JSON Import/Export Tool
 * Description: Adds admin tools to export and import ACF JSON field data for any post type.
 * Version: 1.5
 * Author: Breon Williams
 * Author URI: https://breonwilliams.com
 * Text Domain: acf-json-import-export
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if ACF is active
add_action('admin_init', function() {
    if (!function_exists('get_fields')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('ACF JSON Import/Export Tool requires Advanced Custom Fields to be installed and activated.', 'acf-json-import-export'); ?></p>
            </div>
            <?php
        });
    }
});

// Add admin menus
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Export ACF Fields',
        'Export ACF Fields',
        'manage_options',
        'export-acf-fields',
        'acf_export_admin_page'
    );

    add_submenu_page(
        'tools.php',
        'Import ACF JSON',
        'Import ACF JSON',
        'manage_options',
        'acf-json-import',
        'acf_json_import_page'
    );
});

// Helper function to get all post types
function acf_json_get_post_types() {
    $post_types = get_post_types(array('public' => true), 'objects');
    unset($post_types['attachment']);
    return $post_types;
}

// Helper function to get posts by type
function acf_json_get_posts_by_type($post_type = 'page') {
    return get_posts(array(
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'any'
    ));
}

// Export page
function acf_export_admin_page() {
    $post_types = acf_json_get_post_types();
    $selected_post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'page';
    $posts = acf_json_get_posts_by_type($selected_post_type);
    ?>
    <div class="wrap">
        <h1>Export ACF Fields</h1>
        
        <form method="get" action="">
            <input type="hidden" name="page" value="export-acf-fields">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="post_type">Post Type:</label></th>
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
                    <th scope="row"><label for="post_id">Select Item:</label></th>
                    <td>
                        <select name="post_id" id="post_id" required>
                            <option value="">-- Select an item --</option>
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
                <input type="submit" class="button button-primary" value="Export ACF JSON">
            </p>
        </form>
    </div>
    <?php
}

// Export handler
add_action('admin_post_export_acf_data', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'acf_export_nonce')) {
        wp_die('Security check failed');
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id) {
        wp_die("Invalid post ID");
    }

    $fields = get_fields($post_id);
    if (!$fields) {
        wp_die("No ACF fields found for the selected item.");
    }

    $post = get_post($post_id);
    $slug = sanitize_title($post->post_title ?: 'post-' . $post_id);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="acf-export-' . $slug . '.json"');
    echo json_encode($fields, JSON_PRETTY_PRINT);
    exit;
});

// Import page
function acf_json_import_page() {
    $post_types = acf_json_get_post_types();
    $selected_post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'page';
    $posts = acf_json_get_posts_by_type($selected_post_type);
    ?>
    <div class="wrap">
        <h1>Import ACF JSON</h1>
        
        <?php if (isset($_GET['imported'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>ACF fields imported successfully!</p>
            </div>
        <?php endif; ?>
        
        <form method="get" action="">
            <input type="hidden" name="page" value="acf-json-import">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="post_type">Post Type:</label></th>
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
                    <th scope="row"><label for="post_id">Select Item:</label></th>
                    <td>
                        <select name="post_id" id="post_id" required>
                            <option value="">-- Select an item --</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?php echo esc_attr($post->ID); ?>">
                                    <?php echo esc_html($post->post_title ?: '(No Title)'); ?> (ID: <?php echo $post->ID; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf_json_paste">Paste JSON Code:</label></th>
                    <td>
                        <textarea name="acf_json_paste" id="acf_json_paste" rows="10" cols="80" class="large-text code" placeholder='{"field_name": "value", "another_field": "another value"}'></textarea>
                        <p class="description">Paste your ACF JSON data here</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf_json_file">OR Upload JSON File:</label></th>
                    <td>
                        <input type="file" name="acf_json_file" id="acf_json_file" accept=".json">
                        <p class="description">Maximum file size: 2MB</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button button-primary" value="Import">
            </p>
        </form>
    </div>
    <?php
}

// Import handler
add_action('admin_post_import_acf_json', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    if (!isset($_POST['import_nonce']) || !wp_verify_nonce($_POST['import_nonce'], 'acf_import_nonce')) {
        wp_die('Security check failed');
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $post_type = sanitize_key($_POST['post_type'] ?? 'page');
    
    if (!$post_id) {
        wp_die("Invalid post ID");
    }

    $json_data = null;

    // Check if pasted JSON is provided
    if (!empty($_POST['acf_json_paste'])) {
        $json_data = json_decode(stripslashes($_POST['acf_json_paste']), true);
        if (!$json_data) {
            wp_die("Invalid pasted JSON");
        }
    }
    // Check if file is uploaded
    elseif (!empty($_FILES['acf_json_file']['tmp_name'])) {
        $file = $_FILES['acf_json_file']['tmp_name'];
        $json_data = json_decode(file_get_contents($file), true);
        if (!$json_data) {
            wp_die("Invalid uploaded JSON file");
        }
    } else {
        wp_die("No JSON input provided");
    }

    // Save each field
    foreach ($json_data as $key => $value) {
        update_field($key, $value, $post_id);
    }

    wp_redirect(admin_url('tools.php?page=acf-json-import&imported=1&post_type=' . $post_type));
    exit;
});