<?php
/**
 * Plugin Name: Snippet Aggregator
 * Description: A self-updating WordPress plugin that manages internal functionality through feature toggles.
 * Version: 1.0.85    
 * Author: Adam Marash
 * Author URI: https://github.com/ediblesites/snippet-aggregator
 * GitHub Plugin URI: https://github.com/ediblesites/snippet-aggregator
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: snippet-aggregator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SNIPPET_AGGREGATOR_VERSION', '1.0.85');
define('SNIPPET_AGGREGATOR_FILE', __FILE__);
define('SNIPPET_AGGREGATOR_PATH', plugin_dir_path(__FILE__));
define('SNIPPET_AGGREGATOR_URL', plugin_dir_url(__FILE__));
define('SNIPPET_AGGREGATOR_SETTINGS', 'snippet_aggregator_settings');

// Include plugin functions
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Check for WP Pusher dependency
add_action('admin_init', 'snippet_aggregator_check_dependencies');

function snippet_aggregator_check_dependencies() {
    if (!is_plugin_active('wppusher/wppusher.php')) {
        add_action('admin_notices', 'snippet_aggregator_dependency_notice');
        return;
    }
}

function snippet_aggregator_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Snippet Aggregator requires WP Pusher to be installed and activated. Please install WP Pusher to enable automatic updates.', 'snippet-aggregator'); ?></p>
    </div>
    <?php
}

// Load core functionality
require_once SNIPPET_AGGREGATOR_PATH . 'core/shared/logger.php';
require_once SNIPPET_AGGREGATOR_PATH . 'core/admin-interface.php';
require_once SNIPPET_AGGREGATOR_PATH . 'core/version-shortcode.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'snippet_aggregator_activate');

function snippet_aggregator_activate() {
    // Create necessary database tables and options
    if (!get_option('snippet_aggregator_version')) {
        update_option('snippet_aggregator_version', SNIPPET_AGGREGATOR_VERSION);
    }
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'snippet_aggregator_deactivate');

function snippet_aggregator_deactivate() {
    // Cleanup if needed
}

// Load enabled features
add_action('plugins_loaded', 'snippet_aggregator_load_features');

function snippet_aggregator_load_features() {
    $features = snippet_aggregator_get_available_features();
    $is_admin = is_admin();
    
    foreach ($features as $feature_id => $feature) {
        $is_enabled = get_option("snippet_aggregator_feature_{$feature_id}", false);
        $can_load = true;
        
        // Check context restrictions
        if (!empty($feature['context'])) {
            if ($feature['context'] === 'frontend' && $is_admin) {
                snippet_aggregator_log($feature_id, 'Feature skipped - frontend only', 'info');
                $can_load = false;
            }
            if ($feature['context'] === 'admin' && !$is_admin) {
                snippet_aggregator_log($feature_id, 'Feature skipped - admin only', 'info');
                $can_load = false;
            }
        }

        if ($is_enabled && $can_load) {
            // Load feature
            if (!isset($feature['main_file'])) {
                snippet_aggregator_log($feature_id, 'No main file specified for feature', 'error');
                continue;
            }

            $feature_file = SNIPPET_AGGREGATOR_PATH . "features/{$feature_id}/" . $feature['main_file'];
            $settings_file = SNIPPET_AGGREGATOR_PATH . "features/{$feature_id}/settings.php";

            try {
                // Load main feature file
                if (file_exists($feature_file)) {
                    require_once $feature_file;
                    snippet_aggregator_log($feature_id, sprintf('Feature loaded successfully from %s', $feature_file), 'info');
                } else {
                    snippet_aggregator_log($feature_id, sprintf('Feature file %s not found', $feature_file), 'error');
                }
                
                // Load settings file if in admin and allowed
                if ($is_admin && file_exists($settings_file)) {
                    if (empty($feature['context']) || $feature['context'] !== 'frontend') {
                        require_once $settings_file;
                        snippet_aggregator_log($feature_id, 'Settings loaded successfully', 'info');
                    }
                }
            } catch (Exception $e) {
                snippet_aggregator_log($feature_id, 'Failed to load feature: ' . $e->getMessage(), 'error');
            }
        }
    }
} 