# GitHub Auto-Updating Plugin PRD

## Overview
A self-updating WordPress plugin that manages internal functionality through feature toggles and automatically stays synchronized with a private GitHub repository.

## Core Architecture

### Plugin Structure
```
my-plugin/
├── core/
│   ├── updater.php              # GitHub integration & auto-updates
│   ├── admin-interface.php      # Settings dashboard
│   └── shared/                  # Common utilities (DRY principle)
│       ├── database.php
│       ├── templating.php
│       ├── notifications.php
│       └── helpers.php
├── features/                    # Modular functionality
│   ├── custom-post-types/
│   ├── email-notifications/
│   └── seo-tweaks/
└── main-plugin.php             # Bootstrap & feature loading
```

### Loading Sequence
1. Load shared utilities first
2. Check feature toggle settings
3. Conditionally load enabled features with error isolation
4. Initialize GitHub updater integration

## Feature Management

### User Interface
- Clean admin dashboard with toggle switches for each feature
- Each toggle controls whether that feature's code gets loaded
- Standard WordPress plugin behavior (no custom snippet management)

### Feature Organization
- Each feature in its own directory under `/features/`
- Must contain `info.php` that defines:
  - Feature name
  - Description
  - Main file path
- Main file specified in info.php is the entry point
- Can include multiple related files, templates, assets
- Access to shared utilities from `/core/shared/`

### Error Isolation
- Individual feature loading wrapped in try/catch blocks
- Failed features don't crash other functionality
- Logging of feature-specific errors to WordPress error log
- Option to auto-disable problematic features

## Auto-Update System

### GitHub Integration
- Monitors private GitHub repository for changes
- Uses GitHub API with authentication token
- Integrates with WordPress's native update system
- Appears as standard plugin update in admin interface

### Version Control
- Can use either GitHub releases (semantic versioning) or commit hashes
- Preserves user settings and feature toggle states across updates
- Maintains activation status of individual features

## Technical Benefits

### Simplicity
- Standard WordPress plugin architecture
- No complex snippet management system
- Leverages existing WordPress update mechanisms
- Familiar user experience

### Maintainability
- Clean separation of concerns
- DRY principle with shared utilities
- Modular feature development
- Version control of entire codebase as single unit

### Reliability
- Error isolation prevents cascading failures
- Automatic updates keep functionality current
- Settings preservation across updates
- Fallback mechanisms for failed features

## Implementation Priority

1. **Core Infrastructure**: Basic plugin structure with GitHub updater
2. **Feature Loading**: Toggle system with error isolation
3. **Admin Interface**: Settings dashboard for feature management
4. **Shared Utilities**: Common functionality library
5. **Individual Features**: Migrate existing functionality into modular structure

## Technical Considerations

### Third-Party GitHub Updater Integration
Using existing GitHub updater library (e.g., YahnisElsts/plugin-update-checker) for initial implementation:

```php
// In main plugin file
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/username/my-plugin/',
    __FILE__,
    'my-plugin-slug'
);

// For private repos
$myUpdateChecker->setAuthentication('your-token-here');
```

### Webhook-Triggered Updates
Enable instant updates on GitHub pushes (library doesn't support this natively):

```php
// Add webhook endpoint for GitHub
add_action('wp_ajax_nopriv_github_webhook', 'handle_github_webhook');
add_action('wp_ajax_github_webhook', 'handle_github_webhook');

function handle_github_webhook() {
    // Verify webhook signature for security
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!verify_github_signature($payload, $signature)) {
        wp_die('Unauthorized', 401);
    }
    
    // Parse webhook data
    $data = json_decode($payload, true);
    
    // Check if this is a push to your main branch
    if ($data['ref'] === 'refs/heads/main') {
        global $myUpdateChecker;
        $myUpdateChecker->checkForUpdates();
        
        // Log the webhook trigger
        error_log('Webhook triggered update check');
    }
    
    wp_die('OK', 200);
}

function verify_github_signature($payload, $signature) {
    $webhook_secret = get_option('my_plugin_webhook_secret');
    if (!$webhook_secret) {
        return false;
    }
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    return hash_equals($expected_signature, $signature);
}
```

### Admin Interface Webhook Display
Show webhook URL prominently in admin settings:

```php
// In admin interface
function display_webhook_settings() {
    $webhook_url = admin_url('admin-ajax.php?action=github_webhook');
    $webhook_secret = get_option('my_plugin_webhook_secret', '');
    
    if (empty($webhook_secret)) {
        $webhook_secret = wp_generate_password(32, false);
        update_option('my_plugin_webhook_secret', $webhook_secret);
    }
    
    ?>
    <div class="webhook-settings">
        <h3>GitHub Webhook Configuration</h3>
        
        <div class="webhook-url-section">
            <label><strong>Webhook URL:</strong></label>
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
                    Copy URL
                </button>
            </div>
        </div>
        
        <div class="webhook-secret-section">
            <label><strong>Webhook Secret:</strong></label>
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
                    Copy Secret
                </button>
                <button type="button" 
                        class="regenerate-secret button" 
                        onclick="regenerateWebhookSecret()">
                    Regenerate
                </button>
            </div>
        </div>
        
        <div class="webhook-instructions">
            <p><strong>Setup Instructions:</strong></p>
            <ol>
                <li>Go to your GitHub repository settings</li>
                <li>Navigate to "Webhooks" section</li>
                <li>Click "Add webhook"</li>
                <li>Paste the URL above into "Payload URL"</li>
                <li>Set "Content type" to "application/json"</li>
                <li>Paste the secret above into "Secret" field</li>
                <li>Select "Just the push event"</li>
                <li>Click "Add webhook"</li>
            </ol>
        </div>
    </div>
    
    <script>
    // Copy to clipboard functionality
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.copy-webhook-url, .copy-webhook-secret');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.dataset.clipboardText;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            });
        });
    });
    
    function regenerateWebhookSecret() {
        if (confirm('Are you sure? You will need to update the secret in GitHub.')) {
            // AJAX call to regenerate secret
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=regenerate_webhook_secret&nonce=' + wpNonce
            }).then(() => location.reload());
        }
    }
    </script>
    <?php
}
```

**Setup**: Admin interface displays webhook URL in easily-copyable format with one-click copying and step-by-step GitHub configuration instructions.

### Feature Registry System
Dynamic feature discovery and management:

```php
// In core/admin-interface.php
function get_available_features() {
    $features = [];
    $features_dir = PLUGIN_PATH . 'features';
    
    foreach (scandir($features_dir) as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $info_file = $features_dir . '/' . $dir . '/info.php';
        if (!file_exists($info_file)) {
            log($dir, 'No info.php found', 'warning');
            continue;
        }
        
        $info = include $info_file;
        if (!isset($info['name'], $info['description'], $info['main_file'])) {
            log($dir, 'Invalid info.php structure', 'warning');
            continue;
        }
        
        $main_file = $features_dir . '/' . $dir . '/' . $info['main_file'];
        if (!file_exists($main_file)) {
            log($dir, sprintf('Main file %s not found', $info['main_file']), 'warning');
            continue;
        }
        
        $features[$dir] = $info;
    }
    
    return $features;
}
```

### Settings Management
Centralized configuration with per-feature settings:

```php
// In core/settings.php
class Settings_Manager {
    private $settings_key = 'my_plugin_settings';
    
    public function get_feature_setting($feature_id, $key, $default = null) {
        $all_settings = get_option($this->settings_key, []);
        return $all_settings[$feature_id][$key] ?? $default;
    }
    
    public function update_feature_setting($feature_id, $key, $value) {
        $all_settings = get_option($this->settings_key, []);
        $all_settings[$feature_id][$key] = $value;
        update_option($this->settings_key, $all_settings);
    }
    
    public function is_feature_enabled($feature_id) {
        return (bool) get_option("my_plugin_feature_{$feature_id}", false);
    }
}
```

### Dependency Management
Handle inter-feature dependencies:

```php
// In each feature's init.php
class Custom_Post_Types_Feature {
    public static function get_dependencies() {
        return ['database-tools']; // Other features this depends on
    }
    
    public static function init() {
        if (!self::dependencies_met()) {
            throw new Exception('Required dependencies not available');
        }
        
        // Initialize feature
        add_action('init', [self::class, 'register_post_types']);
    }
    
    private static function dependencies_met() {
        foreach (self::get_dependencies() as $dep) {
            if (!apply_filters('my_plugin_feature_available', false, $dep)) {
                return false;
            }
        }
        return true;
    }
}
```

### Database Schema Management
Handle feature-specific database needs:

```php
// In core/shared/database.php
class Database_Manager {
    public static function create_feature_table($feature_id, $schema) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . "my_plugin_{$feature_id}";
        
        $sql = "CREATE TABLE $table_name ($schema) {$wpdb->get_charset_collate()};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Track created tables for cleanup
        $created_tables = get_option('my_plugin_created_tables', []);
        $created_tables[$feature_id] = $table_name;
        update_option('my_plugin_created_tables', $created_tables);
    }
    
    public static function cleanup_feature_data($feature_id) {
        global $wpdb;
        
        $created_tables = get_option('my_plugin_created_tables', []);
        if (isset($created_tables[$feature_id])) {
            $wpdb->query("DROP TABLE IF EXISTS {$created_tables[$feature_id]}");
            unset($created_tables[$feature_id]);
            update_option('my_plugin_created_tables', $created_tables);
        }
        
        // Clean up options
        delete_option("my_plugin_feature_{$feature_id}");
    }
}
```

### Asset Management
Handle feature-specific CSS/JS:

```php
// In core/shared/assets.php
class Asset_Manager {
    public static function enqueue_feature_assets($feature_id) {
        $feature_dir = plugin_dir_url(__FILE__) . "../features/{$feature_id}/";
        
        // Check for CSS file
        $css_file = plugin_dir_path(__FILE__) . "../features/{$feature_id}/styles.css";
        if (file_exists($css_file)) {
            wp_enqueue_style(
                "my-plugin-{$feature_id}",
                $feature_dir . 'styles.css',
                [],
                filemtime($css_file)
            );
        }
        
        // Check for JS file
        $js_file = plugin_dir_path(__FILE__) . "../features/{$feature_id}/scripts.js";
        if (file_exists($js_file)) {
            wp_enqueue_script(
                "my-plugin-{$feature_id}",
                $feature_dir . 'scripts.js',
                ['jquery'],
                filemtime($js_file),
                true
            );
        }
    }
}
```

### Logging and Debugging
Feature-specific logging system:

```php
// In core/shared/logger.php
class Logger {
    public static function log($feature_id, $message, $level = 'info') {
        if (!WP_DEBUG && !get_option('snippet_aggregator_debug_mode', false)) {
            return;
        }
        
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [{$level}] {$feature_id}: {$message}";
        
        error_log($log_entry);
    }
}
```

**Debug Mode**: A setting in the admin interface that enables debug-level logging to WordPress error log. When enabled along with WP_DEBUG, provides detailed information about feature loading, webhook processing, and other internal operations.

**Setup**: Admin interface includes a simple toggle for debug mode in general settings.

