<?php
/**
 * Provides an admin interface for managing global content snippets
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'globals_manager_add_admin_page');
add_action('admin_init', 'globals_manager_settings_init');
add_action('wp_ajax_globals_manager_save', 'globals_manager_save_ajax');
add_action('wp_ajax_globals_manager_delete', 'globals_manager_delete_ajax');

function globals_manager_add_admin_page() {
    add_options_page(
        'Globals',
        'Globals',
        'edit_pages',
        'globals-manager',
        'globals_manager_admin_page'
    );
}

function globals_manager_settings_init() {
    register_setting('globals_manager_settings', 'globals_manager_pairs');
}

function globals_manager_admin_page() {
    $pairs = get_option('globals_manager_pairs', array());
    ?>
    <div class="wrap">
        <h1>Globals</h1>
        
        <div id="globals-manager-form">
            <h2>Add New Global</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Name</th>
                    <td><input type="text" id="global-name" placeholder="e.g., cta" /></td>
                </tr>
                <tr>
                    <th scope="row">Value</th>
                    <td><textarea id="global-value" rows="5" cols="50" placeholder="Content to output."></textarea></td>
                </tr>
            </table>
            <button type="button" id="add-global" class="button button-primary">Add Global</button>
        </div>

        <h2>Existing Globals</h2>
        <div id="globals-list">
            <?php if (empty($pairs)): ?>
                <p>No globals created yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Value</th>
                            <th>Usage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pairs as $name => $value): ?>
                            <tr data-name="<?php echo esc_attr($name); ?>">
                                <td><strong><?php echo esc_html($name); ?></strong></td>
                                <td><?php echo esc_html(wp_trim_words($value, 10)); ?></td>
                                <td><code>[<?php echo esc_html($name); ?>]</code></td>
                                <td>
                                    <button type="button" class="button edit-global" data-name="<?php echo esc_attr($name); ?>" data-value="<?php echo esc_attr($value); ?>">Edit</button>
                                    <button type="button" class="button delete-global" data-name="<?php echo esc_attr($name); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var isEditing = false;
        var editingName = '';

        $('#add-global').click(function() {
            var name = $('#global-name').val().trim();
            var value = $('#global-value').val().trim();

            if (!name || !value) {
                alert('Please enter both name and value');
                return;
            }

            if (!/^[a-zA-Z0-9_-]+$/.test(name)) {
                alert('Name can only contain letters, numbers, underscores, and hyphens');
                return;
            }

            var action = isEditing ? 'update' : 'add';
            var data = {
                action: 'globals_manager_save',
                name: name,
                value: value,
                operation: action,
                original_name: editingName,
                nonce: '<?php echo wp_create_nonce('globals_manager_nonce'); ?>'
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });

        $('.edit-global').click(function() {
            var name = $(this).data('name');
            var value = $(this).data('value');
            
            $('#global-name').val(name);
            $('#global-value').val(value);
            $('#add-global').text('Update Global');
            
            isEditing = true;
            editingName = name;
        });

        $('.delete-global').click(function() {
            if (!confirm('Are you sure you want to delete this global?')) {
                return;
            }

            var name = $(this).data('name');
            var data = {
                action: 'globals_manager_delete',
                name: name,
                nonce: '<?php echo wp_create_nonce('globals_manager_nonce'); ?>'
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });
    });
    </script>
    <?php
}

function globals_manager_save_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'globals_manager_nonce')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('edit_pages')) {
        wp_die('Insufficient permissions');
    }

    $name = sanitize_text_field($_POST['name']);
    $value = $_POST['value'];
    $operation = sanitize_text_field($_POST['operation']);
    $original_name = sanitize_text_field($_POST['original_name']);

    if (empty($name) || empty($value)) {
        wp_send_json_error('Name and value are required');
    }

    $pairs = get_option('globals_manager_pairs', array());

    if ($operation === 'update' && $original_name !== $name) {
        unset($pairs[$original_name]);
    }

    $pairs[$name] = $value;
    update_option('globals_manager_pairs', $pairs);

    wp_send_json_success('Global saved successfully');
}

function globals_manager_delete_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'globals_manager_nonce')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('edit_pages')) {
        wp_die('Insufficient permissions');
    }

    $name = sanitize_text_field($_POST['name']);
    $pairs = get_option('globals_manager_pairs', array());

    if (isset($pairs[$name])) {
        unset($pairs[$name]);
        update_option('globals_manager_pairs', $pairs);
    }

    wp_send_json_success('Global deleted successfully');
}

// Register shortcodes
add_action('init', 'globals_manager_register_shortcodes');

function globals_manager_register_shortcodes() {
    $pairs = get_option('globals_manager_pairs', array());
    foreach ($pairs as $name => $content) {
        add_shortcode($name, function() use ($content) {
            return $content;
        });
    }
} 