<?php
/**
 * Admin interface for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize admin menu
add_action('admin_menu', 'snippet_aggregator_admin_menu');

/**
 * Add admin menu items
 */
function snippet_aggregator_admin_menu() {
    // Add main menu item
    add_menu_page(
        __('Snippet Aggregator', 'snippet-aggregator'),  // page title
        __('Snippet Aggregator', 'snippet-aggregator'),  // menu title
        'manage_options',                                // capability
        'snippet-aggregator-features',                   // menu slug
        'snippet_aggregator_features_page',              // function
        'dashicons-admin-plugins',                       // icon
        30                                               // position
    );

    // Add Features submenu (this replaces the default submenu item from add_menu_page)
    add_submenu_page(
        'snippet-aggregator-features',                   // parent slug
        __('Features', 'snippet-aggregator'),           // page title
        __('Features', 'snippet-aggregator'),           // menu title
        'manage_options',                               // capability
        'snippet-aggregator-features',                  // menu slug (must match parent)
        'snippet_aggregator_features_page'              // function
    );

    // Add Settings submenu
    add_submenu_page(
        'snippet-aggregator-features',                   // parent slug
        __('Settings', 'snippet-aggregator'),           // page title
        __('Settings', 'snippet-aggregator'),           // menu title
        'manage_options',                               // capability
        'snippet-aggregator-settings',                  // menu slug
        'snippet_aggregator_settings_page'              // function
    );
}

/**
 * Render the features management page
 */
function snippet_aggregator_features_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings if form was submitted
    if (isset($_POST['snippet_aggregator_settings_nonce']) && 
        wp_verify_nonce($_POST['snippet_aggregator_settings_nonce'], 'snippet_aggregator_settings')) {
        
        // Handle feature toggles
        $features = snippet_aggregator_get_available_features();
        foreach ($features as $feature_id => $feature) {
            $enabled = isset($_POST['feature_' . $feature_id]) ? true : false;
            update_option("snippet_aggregator_feature_{$feature_id}", $enabled);
        }
        
        // Show success message
        add_settings_error(
            'snippet_aggregator_messages',
            'snippet_aggregator_message',
            __('Settings Saved', 'snippet-aggregator'),
            'updated'
        );
    }
    
    // Get current settings
    $features = snippet_aggregator_get_available_features();
    
    // Show settings page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>
        
        <form action="" method="post">
            <?php wp_nonce_field('snippet_aggregator_settings', 'snippet_aggregator_settings_nonce'); ?>
            
            <table class="form-table">
                <?php foreach ($features as $feature_id => $feature): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($feature['name']); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="feature_<?php echo esc_attr($feature_id); ?>"
                                       <?php checked(snippet_aggregator_is_feature_enabled($feature_id)); ?>>
                                <?php _e('Enable', 'snippet-aggregator'); ?>
                            </label>
                            <?php if (!empty($feature['description'])): ?>
                                <p class="description"><?php echo esc_html($feature['description']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Render the settings page
 */
function snippet_aggregator_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form was submitted
    if (isset($_POST['snippet_aggregator_settings_nonce']) && 
        wp_verify_nonce($_POST['snippet_aggregator_settings_nonce'], 'snippet_aggregator_settings')) {
        
        // Handle debug mode setting
        $debug_mode = isset($_POST['debug_mode']) ? true : false;
        snippet_aggregator_update_setting('debug_mode', $debug_mode);

        // Handle webhook secret if it was regenerated
        if (isset($_POST['regenerate_webhook_secret'])) {
            $new_secret = wp_generate_password(32, false);
            update_option('snippet_aggregator_webhook_secret', $new_secret);
        }
        
        // Show success message
        add_settings_error(
            'snippet_aggregator_messages',
            'snippet_aggregator_message',
            __('Settings Saved', 'snippet-aggregator'),
            'updated'
        );
    }
    
    // Get current settings
    $debug_mode = snippet_aggregator_get_setting('debug_mode', false);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>
        
        <form action="" method="post">
            <?php wp_nonce_field('snippet_aggregator_settings', 'snippet_aggregator_settings_nonce'); ?>
            
            <h2><?php _e('General Settings', 'snippet-aggregator'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Debug Mode', 'snippet-aggregator'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="debug_mode"
                                   <?php checked($debug_mode); ?>>
                            <?php _e('Enable debug logging', 'snippet-aggregator'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, detailed debug information will be written to the WordPress debug log.', 'snippet-aggregator'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2><?php _e('GitHub Webhook Configuration', 'snippet-aggregator'); ?></h2>
            <?php snippet_aggregator_display_webhook_settings(); ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Render the webhook configuration page
 */
function snippet_aggregator_webhook_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('snippet_aggregator_messages'); ?>
        
        <?php snippet_aggregator_display_webhook_settings(); ?>
    </div>
    <?php
}

/**
 * Display webhook configuration section
 */
function snippet_aggregator_display_webhook_settings() {
    $webhook_url = admin_url('admin-ajax.php?action=github_webhook');
    $webhook_secret = get_option('snippet_aggregator_webhook_secret', '');
    
    if (empty($webhook_secret)) {
        $webhook_secret = wp_generate_password(32, false);
        update_option('snippet_aggregator_webhook_secret', $webhook_secret);
    }
    
    ?>
    <div class="webhook-settings">        
        <div class="webhook-url-section">
            <label><strong><?php _e('Webhook URL:', 'snippet-aggregator'); ?></strong></label>
            <div class="webhook-url-container">
                <input type="text" 
                       value="<?php echo esc_url($webhook_url); ?>" 
                       readonly 
                       class="webhook-url-input" 
                       onclick="this.select()"
                       style="width: 100%; font-family: monospace;">
                <button type="button" 
                        class="copy-webhook-url button" 
                        data-clipboard-text="<?php echo esc_url($webhook_url); ?>">
                    <?php _e('Copy URL', 'snippet-aggregator'); ?>
                </button>
            </div>
        </div>
        
        <div class="webhook-secret-section" style="margin-top: 1em;">
            <label><strong><?php _e('Webhook Secret:', 'snippet-aggregator'); ?></strong></label>
            <div class="webhook-secret-container">
                <input type="password" 
                       value="<?php echo esc_attr($webhook_secret); ?>" 
                       readonly 
                       class="webhook-secret-input" 
                       onclick="this.select()"
                       style="width: 100%; font-family: monospace;">
                <button type="button" 
                        class="copy-webhook-secret button" 
                        data-clipboard-text="<?php echo esc_attr($webhook_secret); ?>">
                    <?php _e('Copy Secret', 'snippet-aggregator'); ?>
                </button>
                <button type="button" 
                        class="regenerate-secret button" 
                        onclick="regenerateWebhookSecret()">
                    <?php _e('Regenerate', 'snippet-aggregator'); ?>
                </button>
            </div>
        </div>
        
        <div class="webhook-instructions" style="margin-top: 2em;">
            <p><strong><?php _e('Setup Instructions:', 'snippet-aggregator'); ?></strong></p>
            <ol>
                <li><?php _e('Go to your GitHub repository settings', 'snippet-aggregator'); ?></li>
                <li><?php _e('Navigate to "Webhooks" section', 'snippet-aggregator'); ?></li>
                <li><?php _e('Click "Add webhook"', 'snippet-aggregator'); ?></li>
                <li><?php _e('Paste the URL above into "Payload URL"', 'snippet-aggregator'); ?></li>
                <li><?php _e('Set "Content type" to "application/json"', 'snippet-aggregator'); ?></li>
                <li><?php _e('Paste the secret above into "Secret" field', 'snippet-aggregator'); ?></li>
                <li><?php _e('Select "Just the push event"', 'snippet-aggregator'); ?></li>
                <li><?php _e('Click "Add webhook"', 'snippet-aggregator'); ?></li>
            </ol>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.copy-webhook-url, .copy-webhook-secret');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.dataset.clipboardText;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.textContent;
                    this.textContent = '<?php _e('Copied!', 'snippet-aggregator'); ?>';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            });
        });
    });
    
    function regenerateWebhookSecret() {
        if (confirm('<?php _e('Are you sure? You will need to update the secret in GitHub.', 'snippet-aggregator'); ?>')) {
            // AJAX call to regenerate secret
            const data = new FormData();
            data.append('action', 'regenerate_webhook_secret');
            data.append('nonce', '<?php echo wp_create_nonce('regenerate_webhook_secret'); ?>');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            }).then(() => location.reload());
        }
    }
    </script>
    <?php
}

/**
 * Handle webhook secret regeneration
 */
add_action('wp_ajax_regenerate_webhook_secret', 'snippet_aggregator_regenerate_webhook_secret');

function snippet_aggregator_regenerate_webhook_secret() {
    check_ajax_referer('regenerate_webhook_secret');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', 401);
    }
    
    $new_secret = wp_generate_password(32, false);
    update_option('snippet_aggregator_webhook_secret', $new_secret);
    
    wp_send_json_success();
}

/**
 * Get list of available features
 *
 * @return array
 */
function snippet_aggregator_get_available_features() {
    $features_dir = SNIPPET_AGGREGATOR_PATH . 'features';
    $features = array();
    
    if (is_dir($features_dir)) {
        $dirs = scandir($features_dir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $feature_dir = $features_dir . '/' . $dir;
            if (is_dir($feature_dir)) {
                // Get feature info
                $info_file = $feature_dir . '/info.php';
                if (file_exists($info_file)) {
                    $info = include $info_file;
                    if (is_array($info) && isset($info['main_file'])) {
                        // Check if main file exists
                        if (!file_exists($feature_dir . '/' . $info['main_file'])) {
                            Snippet_Aggregator_Logger::error($dir, sprintf('Main file %s not found', $info['main_file']));
                            continue;
                        }
                        $features[$dir] = $info;
                    }
                }
            }
        }
    }
    
    return $features;
} 

/**
 * Enqueue admin styles
 */
add_action('admin_enqueue_scripts', 'snippet_aggregator_admin_styles');

function snippet_aggregator_admin_styles($hook) {
    if (!in_array($hook, [
        'toplevel_page_snippet-aggregator-features',
        'snippet-aggregator_page_snippet-aggregator-settings'
    ])) {
        return;
    }

    wp_enqueue_style(
        'snippet-aggregator-admin',
        SNIPPET_AGGREGATOR_URL . 'core/css/admin.css',
        [],
        SNIPPET_AGGREGATOR_VERSION
    );
} 