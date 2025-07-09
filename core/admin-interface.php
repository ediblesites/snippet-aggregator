<?php
/**
 * Admin interface functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add menu page
add_action('admin_menu', 'snippet_aggregator_add_admin_menu');
function snippet_aggregator_add_admin_menu() {
    add_menu_page(
        __('Snippet Aggregator', 'snippet-aggregator'),
        __('Snippet Aggregator', 'snippet-aggregator'),
        'manage_options',
        'snippet-aggregator',
        'snippet_aggregator_render_admin_page',
        'dashicons-admin-plugins'
    );
}

// Register settings
add_action('admin_init', 'snippet_aggregator_register_settings');
function snippet_aggregator_register_settings() {
    register_setting('snippet_aggregator_settings', SNIPPET_AGGREGATOR_SETTINGS);

    // Register debug mode setting
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_debug_mode',
        [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Enable detailed debug logging',
        ]
    );

    // Register settings for each feature
    $features = snippet_aggregator_get_available_features();
    foreach ($features as $feature_id => $feature) {
        register_setting(
            'snippet_aggregator_settings',
            "snippet_aggregator_feature_{$feature_id}",
            [
                'type' => 'boolean',
                'default' => false,
            ]
        );
    }
}

// Render admin page
function snippet_aggregator_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['submit'])) {
        check_admin_referer('snippet_aggregator_settings');
        
        // Save debug mode
        $debug_mode = isset($_POST['snippet_aggregator_debug_mode']) ? true : false;
        update_option('snippet_aggregator_debug_mode', $debug_mode);
        
        // Save feature toggles
        $features = snippet_aggregator_get_available_features();
        foreach ($features as $feature_id => $feature) {
            $enabled = isset($_POST["feature_{$feature_id}"]) ? true : false;
            update_option("snippet_aggregator_feature_{$feature_id}", $enabled);
        }
        
        add_settings_error(
            'snippet_aggregator_messages',
            'snippet_aggregator_message',
            __('Settings Saved', 'snippet-aggregator'),
            'updated'
        );
    }

    // Get features
    $features = snippet_aggregator_get_available_features();
    
    // Show settings page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>

        <form action="" method="post">
            <?php
            wp_nonce_field('snippet_aggregator_settings');
            settings_fields('snippet_aggregator_settings');
            ?>
            
            <h2><?php _e('General Settings', 'snippet-aggregator'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'snippet-aggregator'); ?></th>
                        <td>
                            <label for="snippet_aggregator_debug_mode">
                                <input type="checkbox"
                                       name="snippet_aggregator_debug_mode"
                                       id="snippet_aggregator_debug_mode"
                                       value="1"
                                       <?php checked(get_option('snippet_aggregator_debug_mode', false)); ?>>
                                <?php _e('Enable detailed debug logging', 'snippet-aggregator'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, detailed debug information will be written to the WordPress debug log.', 'snippet-aggregator'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Webhook URL', 'snippet-aggregator'); ?></th>
                        <td>
                            <?php 
                            $webhook_url = add_query_arg('action', 'snippet_aggregator_github_webhook', admin_url('admin-ajax.php'));
                            ?>
                            <input type="text"
                                   value="<?php echo esc_url($webhook_url); ?>"
                                   class="large-text code"
                                   readonly
                                   onclick="this.select()">
                            <p class="description">
                                <?php _e('Use this URL when configuring the webhook in GitHub. Click to select.', 'snippet-aggregator'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2><?php _e('Features', 'snippet-aggregator'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ($features as $feature_id => $feature): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($feature['name']); ?></th>
                            <td>
                                <label for="feature_<?php echo esc_attr($feature_id); ?>">
                                    <input type="checkbox"
                                           name="feature_<?php echo esc_attr($feature_id); ?>"
                                           id="feature_<?php echo esc_attr($feature_id); ?>"
                                           value="1"
                                           <?php checked(get_option("snippet_aggregator_feature_{$feature_id}", false)); ?>>
                                    <?php echo esc_html($feature['description']); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Helper function to get available features
function snippet_aggregator_get_available_features() {
    $features = [];
    $features_dir = SNIPPET_AGGREGATOR_PATH . 'features';
    
    if (!is_dir($features_dir)) {
        return $features;
    }
    
    $dirs = scandir($features_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') {
            continue;
        }
        
        $feature_dir = $features_dir . '/' . $dir;
        if (!is_dir($feature_dir)) {
            continue;
        }
        
        // Get feature info
        $info_file = $feature_dir . '/info.php';
        if (!file_exists($info_file)) {
            snippet_aggregator_log($dir, 'No info.php found', 'warning');
            continue;
        }
        
        $info = include $info_file;
        if (!is_array($info) || !isset($info['name'], $info['description'], $info['main_file'])) {
            snippet_aggregator_log($dir, 'Invalid info.php structure', 'warning');
            continue;
        }
        
        $main_file = $feature_dir . '/' . $info['main_file'];
        if (!file_exists($main_file)) {
            snippet_aggregator_log($dir, sprintf('Main file %s not found', $info['main_file']), 'warning');
            continue;
        }
        
        $features[$dir] = $info;
    }
    
    return $features;
} 