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

    // Add Features as first submenu to override default
    add_submenu_page(
        'snippet-aggregator-features',
        __('Features', 'snippet-aggregator'),
        __('Features', 'snippet-aggregator'),
        'manage_options',
        'snippet-aggregator-features',
        'snippet_aggregator_render_features_page'
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

// Add AJAX handlers for feature toggling
add_action('wp_ajax_snippet_aggregator_toggle_feature', 'snippet_aggregator_ajax_toggle_feature');
function snippet_aggregator_ajax_toggle_feature() {
    // Check nonce and capabilities
    if (!check_ajax_referer('snippet_aggregator_toggle_feature', 'nonce', false) || 
        !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $feature_id = sanitize_text_field($_POST['feature_id']);
    $enabled = (bool)$_POST['enabled'];
    $option_name = "snippet_aggregator_feature_{$feature_id}";
    
    // Update the option
    update_option($option_name, $enabled);
    
    // Log the change
    snippet_aggregator_log('features', 
        sprintf('Feature "%s" %s via AJAX', $feature_id, $enabled ? 'enabled' : 'disabled'), 
        'info'
    );
    
    wp_send_json_success([
        'message' => sprintf(
            __('Feature "%s" has been %s', 'snippet-aggregator'),
            $feature_id,
            $enabled ? __('enabled', 'snippet-aggregator') : __('disabled', 'snippet-aggregator')
        )
    ]);
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
        <h1><?php _e('Features', 'snippet-aggregator'); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>
        
        <div id="snippet-aggregator-features">
            <h2><?php _e('Available Features', 'snippet-aggregator'); ?></h2>
            <div class="snippet-aggregator-features-grid">
                <?php foreach ($features as $feature_id => $feature): ?>
                    <div class="feature-column">
                        <div class="feature-info">
                            <h3><?php echo esc_html($feature['name']); ?></h3>
                            <p class="description"><?php echo esc_html($feature['description']); ?></p>
                        </div>
                        <div class="feature-toggle">
                            <label class="snippet-aggregator-switch">
                                <input type="checkbox"
                                       class="snippet-aggregator-feature-toggle"
                                       data-feature="<?php echo esc_attr($feature_id); ?>"
                                       <?php checked(get_option("snippet_aggregator_feature_{$feature_id}", false)); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .snippet-aggregator-features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .feature-column {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }

        .feature-info {
            flex: 1;
            padding-right: 20px;
        }

        .feature-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.1em;
        }

        .feature-info .description {
            margin: 0;
            color: #646970;
        }

        .feature-toggle {
            flex-shrink: 0;
            padding-top: 4px;
        }

        .snippet-aggregator-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .snippet-aggregator-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #2271b1;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #2271b1;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }

        .snippet-aggregator-switch.disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .snippet-aggregator-switch.disabled .slider {
            cursor: not-allowed;
        }

        @media screen and (max-width: 782px) {
            .feature-column {
                flex-direction: column;
            }
            
            .feature-info {
                padding-right: 0;
                padding-bottom: 15px;
            }
            
            .feature-toggle {
                align-self: flex-start;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Reusable copy button functionality
            function initCopyButton($button) {
                $button.click(function() {
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
            }

            // Initialize all copy buttons
            $('.copy-button').each(function() {
                initCopyButton($(this));
            });

            // Feature toggle functionality
            $('.snippet-aggregator-feature-toggle').on('change', function() {
                const $switch = $(this).closest('.snippet-aggregator-switch');
                const feature = $(this).data('feature');
                const enabled = $(this).prop('checked');
                
                // Disable switch while processing
                $switch.addClass('disabled');
                $(this).prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'snippet_aggregator_toggle_feature',
                        feature_id: feature,
                        enabled: enabled ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('snippet_aggregator_toggle_feature'); ?>'
                    },
                    success: function(response) {
                        if (!response.success) {
                            // Only revert on error
                            $(this).prop('checked', !enabled);
                        }
                    },
                    error: function() {
                        // Revert the toggle on error
                        $(this).prop('checked', !enabled);
                    },
                    complete: function() {
                        // Re-enable switch
                        $switch.removeClass('disabled');
                        $(this).prop('disabled', false);
                    }
                });
            });
        });
        </script>
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