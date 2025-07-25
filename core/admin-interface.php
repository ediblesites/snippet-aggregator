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
    // Add under Settings menu
    add_options_page(
        __('Snippet Aggregator', 'snippet-aggregator'),
        __('Snippet Aggregator', 'snippet-aggregator'),
        'manage_options',
        'snippet-aggregator',
        'snippet_aggregator_render_admin_page'
    );
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
    
    // Sort features by name
    uasort($features, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $features;
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

// Helper function to get feature settings tabs
function snippet_aggregator_get_feature_settings() {
    $settings_tabs = [];
    $features = snippet_aggregator_get_available_features();
    
    foreach ($features as $feature_id => $feature) {
        // Convert hyphens to underscores for function names
        $function_id = str_replace('-', '_', $feature_id);
        
        // Settings file should define these functions if it was loaded:
        // {feature_id}_register_settings()
        // {feature_id}_render_settings()
        $register_func = $function_id . '_register_settings';
        $render_func = $function_id . '_render_settings';
        
        if (function_exists($register_func) && function_exists($render_func)) {
            $settings_tabs[$feature_id] = [
                'name' => $feature['name'],
                'register_callback' => $register_func,
                'render_callback' => $render_func
            ];
        }
    }
    
    return $settings_tabs;
}

// Register feature-specific settings
add_action('admin_init', 'snippet_aggregator_register_feature_settings');
function snippet_aggregator_register_feature_settings() {
    // Register feature settings
    $settings_tabs = snippet_aggregator_get_feature_settings();
    foreach ($settings_tabs as $feature_id => $tab) {
        if (function_exists($tab['register_callback'])) {
            call_user_func($tab['register_callback']);
        }
    }
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

// Main render function for admin page
function snippet_aggregator_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get available settings tabs
    $settings_tabs = snippet_aggregator_get_feature_settings();
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'features';
    ?>
    <div class="wrap">
        <h1><?php printf(__('Snippet Aggregator v%s', 'snippet-aggregator'), SNIPPET_AGGREGATOR_VERSION); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="?page=snippet-aggregator&tab=features" class="nav-tab <?php echo $current_tab === 'features' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Features', 'snippet-aggregator'); ?>
            </a>
            <a href="?page=snippet-aggregator&tab=core-settings" class="nav-tab <?php echo $current_tab === 'core-settings' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Core Settings', 'snippet-aggregator'); ?>
            </a>
            <?php foreach ($settings_tabs as $feature_id => $tab): ?>
                <a href="?page=snippet-aggregator&tab=<?php echo esc_attr($feature_id); ?>" 
                   class="nav-tab <?php echo $current_tab === $feature_id ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php settings_errors('snippet_aggregator_messages'); ?>

        <div class="tab-content">
            <?php
            if ($current_tab === 'features') {
                snippet_aggregator_render_features_tab();
            } elseif ($current_tab === 'core-settings') {
                snippet_aggregator_render_core_settings_tab();
            } elseif (isset($settings_tabs[$current_tab])) {
                // Render feature settings
                call_user_func($settings_tabs[$current_tab]['render_callback']);
            }
            ?>
        </div>
    </div>
    <?php
}

// Render features tab content
function snippet_aggregator_render_features_tab() {
    $features = snippet_aggregator_get_available_features();
    ?>
    <div id="snippet-aggregator-features" class="wrap">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-toggle"><?php _e('Status', 'snippet-aggregator'); ?></th>
                    <th class="column-context"><?php _e('Context', 'snippet-aggregator'); ?></th>
                    <th class="column-title"><?php _e('Feature', 'snippet-aggregator'); ?></th>
                    <th class="column-description"><?php _e('Description', 'snippet-aggregator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($features as $feature_id => $feature): ?>
                    <tr>
                        <td class="column-toggle">
                            <label class="snippet-aggregator-switch">
                                <input type="checkbox"
                                       class="snippet-aggregator-feature-toggle"
                                       data-feature="<?php echo esc_attr($feature_id); ?>"
                                       <?php checked(get_option("snippet_aggregator_feature_{$feature_id}", false)); ?>>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="column-context">
                            <?php if (isset($feature['context'])): ?>
                                <span class="context-badge <?php echo esc_attr($feature['context']); ?>">
                                    <?php echo esc_html(ucfirst($feature['context'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="context-badge all">
                                    <?php _e('All', 'snippet-aggregator'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-title">
                            <strong><?php echo esc_html($feature['name']); ?></strong>
                        </td>
                        <td class="column-description">
                            <?php echo esc_html($feature['description']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
    .snippet-aggregator-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
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
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
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
        transform: translateX(22px);
    }
    
    .slider.round {
        border-radius: 24px;
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

    .context-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 13px;
        font-weight: 500;
    }

    .context-badge.frontend {
        background: #e7f5ff;
        color: #0066cc;
    }

    .context-badge.admin {
        background: #fff5eb;
        color: #b35c00;
    }

    .context-badge.all {
        background: #e7f7e7;
        color: #006600;
    }

    #snippet-aggregator-features .column-toggle {
        width: 10%;
        text-align: center;
    }

    #snippet-aggregator-features .column-context {
        width: 15%;
    }

    #snippet-aggregator-features .column-title {
        width: 25%;
    }

    #snippet-aggregator-features .column-description {
        width: 50%;
    }

    #snippet-aggregator-features td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    #snippet-aggregator-features th {
        font-size: 14px;
    }

    #snippet-aggregator-features td.column-title strong {
        font-size: 14px;
        display: block;
        margin-bottom: 2px;
    }

    #snippet-aggregator-features td.column-description {
        font-size: 13px;
        line-height: 1.5;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Feature toggle functionality
        $('.snippet-aggregator-feature-toggle').on('change', function() {
            const $toggle = $(this);
            const $switch = $toggle.closest('.snippet-aggregator-switch');
            const feature = $toggle.data('feature');
            const enabled = $toggle.prop('checked');
            
            // Disable switch while processing
            $switch.addClass('disabled');
            $toggle.prop('disabled', true);
            
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
                        // Revert on error and show message
                        $toggle.prop('checked', !enabled);
                        alert('Failed to update feature status. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    // Revert the toggle on error and show message
                    $toggle.prop('checked', !enabled);
                    alert('Error updating feature status: ' + error);
                },
                complete: function() {
                    // Re-enable switch
                    $switch.removeClass('disabled');
                    $toggle.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

// Render core settings tab content
function snippet_aggregator_render_core_settings_tab() {
    ?>
    <div class="shortcode-info" style="margin: 10px 0 20px; padding: 12px 15px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <code style="font-size: 13px; background: #f0f0f1; padding: 3px 5px; border-radius: 3px;">[snippet_aggregator_version format="v-prefix"]</code>
        <span style="color: #646970; margin-left: 8px;">Use this shortcode to display the current plugin version </span>
    </div>

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
                        <?php _e('When enabled, detailed debug information will be written to the WordPress error log.', 'snippet-aggregator'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
} 