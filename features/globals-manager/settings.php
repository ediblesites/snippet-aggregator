<?php
/**
 * Settings page for Globals Manager feature
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register settings
function globals_manager_register_settings() {
    register_setting(
        'snippet_aggregator_settings',
        'globals_manager_pairs',
        [
            'type' => 'array',
            'default' => [],
            'sanitize_callback' => function($value) {
                return is_array($value) ? $value : [];
            }
        ]
    );
}

// Render settings page
function globals_manager_render_settings() {
    $pairs = get_option('globals_manager_pairs', array());
    ?>
    <form action="options.php" method="post">
        <?php 
        settings_fields('snippet_aggregator_settings');
        
        // Include all feature toggles to prevent them from being deleted
        $features = snippet_aggregator_get_available_features();
        foreach ($features as $feature_id => $feature) {
            $current_toggle = get_option("snippet_aggregator_feature_{$feature_id}") ? '1' : '0';
            echo '<input type="hidden" name="snippet_aggregator_feature_' . esc_attr($feature_id) . '" value="' . $current_toggle . '">';
        }
        ?>
        
        <h2><?php _e('Globals Manager', 'snippet-aggregator'); ?></h2>
        
        <div id="globals-manager-form">
            <h3><?php _e('Add New Global', 'snippet-aggregator'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Name', 'snippet-aggregator'); ?></th>
                    <td><input type="text" id="global-name" class="regular-text" placeholder="e.g., cta" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Value', 'snippet-aggregator'); ?></th>
                    <td>
                        <textarea id="global-value" rows="5" class="large-text" placeholder="Content to output."></textarea>
                        <p class="description"><?php _e('Enter the content that will be output when this global is used.', 'snippet-aggregator'); ?></p>
                    </td>
                </tr>
            </table>
            <button type="button" id="add-global" class="button button-primary"><?php _e('Add Global', 'snippet-aggregator'); ?></button>
        </div>

        <h3><?php _e('Existing Globals', 'snippet-aggregator'); ?></h3>
        <div id="globals-list">
            <?php if (empty($pairs)): ?>
                <p><?php _e('No globals created yet.', 'snippet-aggregator'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'snippet-aggregator'); ?></th>
                            <th><?php _e('Value', 'snippet-aggregator'); ?></th>
                            <th><?php _e('Usage', 'snippet-aggregator'); ?></th>
                            <th><?php _e('Actions', 'snippet-aggregator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pairs as $name => $value): ?>
                            <tr data-name="<?php echo esc_attr($name); ?>">
                                <td><strong><?php echo esc_html($name); ?></strong></td>
                                <td><?php echo esc_html(wp_trim_words($value, 10)); ?></td>
                                <td><code>[<?php echo esc_html($name); ?>]</code></td>
                                <td>
                                    <button type="button" class="button edit-global" data-name="<?php echo esc_attr($name); ?>" data-value="<?php echo esc_attr($value); ?>"><?php _e('Edit', 'snippet-aggregator'); ?></button>
                                    <button type="button" class="button delete-global" data-name="<?php echo esc_attr($name); ?>"><?php _e('Delete', 'snippet-aggregator'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </form>

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