<?php
/**
 * Plugin Name: ACF JSON Import/Export Tool
 * Description: Adds admin tools to export and import ACF JSON field data for any page.
 * Version: 1.0
 * Author: Breon Williams
 * Author URI: https://breonwilliams.com
 */

// Export Menu and Logic
add_action('admin_menu', function () {
    add_menu_page(
        'Export ACF Fields',
        'Export ACF Fields',
        'manage_options',
        'export-acf-fields',
        'acf_export_admin_page'
    );

    add_menu_page(
        'Import ACF JSON',
        'Import ACF JSON',
        'manage_options',
        'acf-json-import',
        'acf_json_import_page'
    );
});

function acf_export_admin_page() {
    $pages = get_pages(); ?>
    <div class="wrap">
        <h1>Export ACF Fields from a Page</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="export_acf_data">
            <label for="page_id">Select Page:</label>
            <select name="page_id" id="page_id">
                <?php foreach ($pages as $page): ?>
                    <option value="<?php echo esc_attr($page->ID); ?>">
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <input type="submit" class="button button-primary" value="Export ACF JSON">
        </form>
    </div>
<?php }

add_action('admin_post_export_acf_data', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    $page_id = intval($_POST['page_id'] ?? 0);
    if (!$page_id) {
        wp_die("Invalid page ID");
    }

    $fields = get_fields($page_id);
    if (!$fields) {
        wp_die("No ACF fields found for the selected page.");
    }

    $page = get_post($page_id);
    $slug = sanitize_title($page->post_title);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="acf-export-' . $slug . '.json"');
    echo json_encode($fields, JSON_PRETTY_PRINT);
    exit;
});

// Import Menu and Logic
function acf_json_import_page() {
    ?>
    <div class="wrap">
        <h1>Import ACF JSON into Page</h1>
        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="import_acf_json">
            <label>Select Page:</label>
            <?php $pages = get_pages(); ?>
            <select name="page_id">
                <?php foreach ($pages as $page): ?>
                    <option value="<?php echo esc_attr($page->ID); ?>">
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Select JSON File:</label>
            <input type="file" name="acf_json_file" accept=".json"><br><br>

            <input type="submit" class="button button-primary" value="Import">
        </form>
    </div>
    <?php
}

add_action('admin_post_import_acf_json', function () {
    if (!current_user_can('manage_options')) {
        wp_die("Access denied");
    }

    $page_id = intval($_POST['page_id']);
    $file = $_FILES['acf_json_file']['tmp_name'];
    if (!$file) {
        wp_die("No file uploaded");
    }

    $json_data = json_decode(file_get_contents($file), true);
    if (!$json_data) {
        wp_die("Invalid JSON");
    }

    foreach ($json_data as $key => $value) {
        update_field($key, $value, $page_id);
    }

    wp_redirect(admin_url('admin.php?page=acf-json-import&imported=1'));
    exit;
});
?>
