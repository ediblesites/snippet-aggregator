<?php
/**
 * Admin interface functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add menu items
add_action('admin_menu', 'snippet_aggregator_add_menu_items');
function snippet_aggregator_add_menu_items() {
    // Main menu page (shows features by default)
    add_menu_page(
        __('Snippet Aggregator', 'snippet-aggregator'),
        __('Snippet Aggregator', 'snippet-aggregator'),
        'manage_options',
        'snippet-aggregator-features',
        'snippet_aggregator_render_features_page',
        'dashicons-admin-plugins'
    );

    // Settings page under main menu
    add_submenu_page(
        'snippet-aggregator-features',
        __('Settings', 'snippet-aggregator'),
        __('Settings', 'snippet-aggregator'),
        'manage_options',
        'snippet-aggregator-settings',
        'snippet_aggregator_render_settings_page'
    );
}

// Register settings
add_action('admin_init', 'snippet_aggregator_register_settings');
function snippet_aggregator_register_settings() {
    // Debug mode
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_debug_mode',
        [
            'type' => 'boolean',
            'default' => false,
        ]
    );

    // Webhook secret
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_webhook_secret',
        [
            'type' => 'string',
            'sanitize_callback' => 'snippet_aggregator_sanitize_webhook_secret',
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

function snippet_aggregator_sanitize_webhook_secret($value) {
    if (empty($value)) {
        $value = wp_generate_password(32, false);
    }
    return sanitize_text_field($value);
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

// Render features page
function snippet_aggregator_render_features_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get features
    $features = snippet_aggregator_get_available_features();
    
    // Show features page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>

        <form action="options.php" method="post">
            <?php
            settings_fields('snippet_aggregator_settings');
            ?>
            
            <h2><?php _e('Available Features', 'snippet-aggregator'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ($features as $feature_id => $feature): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($feature['name']); ?></th>
                            <td>
                                <label for="feature_<?php echo esc_attr($feature_id); ?>">
                                    <input type="checkbox"
                                           name="snippet_aggregator_feature_<?php echo esc_attr($feature_id); ?>"
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

// Render settings page
function snippet_aggregator_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>

        <form action="options.php" method="post">
            <?php settings_fields('snippet_aggregator_settings'); ?>
            
            <h2><?php _e('Debug Settings', 'snippet-aggregator'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Debug Mode', 'snippet-aggregator'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="snippet_aggregator_debug_mode" 
                                   value="1"
                                   <?php checked(get_option('snippet_aggregator_debug_mode', false)); ?>>
                            <?php _e('Enable detailed logging', 'snippet-aggregator'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, detailed debug information will be written to the WordPress error log. Works in conjunction with WP_DEBUG.', 'snippet-aggregator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2><?php _e('Webhook Configuration', 'snippet-aggregator'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Webhook URL', 'snippet-aggregator'); ?></th>
                    <td>
                        <?php 
                        $webhook_url = add_query_arg('action', 'snippet_aggregator_github_webhook', admin_url('admin-ajax.php'));
                        ?>
                        <div class="webhook-url-container">
                            <input type="text"
                                   class="large-text code"
                                   value="<?php echo esc_url($webhook_url); ?>"
                                   readonly
                                   onclick="this.select()">
                            <button type="button" class="button copy-button" data-clipboard-text="<?php echo esc_url($webhook_url); ?>">
                                <?php _e('Copy URL', 'snippet-aggregator'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Webhook Secret', 'snippet-aggregator'); ?></th>
                    <td>
                        <?php 
                        $webhook_secret = get_option('snippet_aggregator_webhook_secret');
                        if (empty($webhook_secret)) {
                            $webhook_secret = wp_generate_password(32, false);
                            update_option('snippet_aggregator_webhook_secret', $webhook_secret);
                        }
                        ?>
                        <div class="webhook-secret-container">
                            <input type="text"
                                   class="large-text code"
                                   name="snippet_aggregator_webhook_secret"
                                   value="<?php echo esc_attr($webhook_secret); ?>"
                                   readonly
                                   onclick="this.select()">
                            <button type="button" class="button copy-button" data-clipboard-text="<?php echo esc_attr($webhook_secret); ?>">
                                <?php _e('Copy Secret', 'snippet-aggregator'); ?>
                            </button>
                            <button type="button" class="button regenerate-secret">
                                <?php _e('Regenerate Secret', 'snippet-aggregator'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <style>
    .webhook-url-container,
    .webhook-secret-container {
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .webhook-url-container input,
    .webhook-secret-container input {
        flex: 1;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Copy button functionality
        $('.copy-button').click(function() {
            const textToCopy = $(this).data('clipboard-text');
            navigator.clipboard.writeText(textToCopy).then(() => {
                const $button = $(this);
                const originalText = $button.text();
                $button.text('<?php _e('Copied!', 'snippet-aggregator'); ?>');
                setTimeout(() => {
                    $button.text(originalText);
                }, 2000);
            });
        });

        // Regenerate secret
        $('.regenerate-secret').click(function() {
            if (confirm('<?php _e('Are you sure? You will need to update the webhook in GitHub after regenerating the secret.', 'snippet-aggregator'); ?>')) {
                const newSecret = generateRandomString(32);
                $('input[name="snippet_aggregator_webhook_secret"]')
                    .val(newSecret)
                    .trigger('change')
                    .siblings('.copy-button')
                    .attr('data-clipboard-text', newSecret);
            }
        });

        function generateRandomString(length) {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
    });
    </script>
    <?php
} 