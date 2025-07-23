# Snippet Aggregator

Internal WordPress plugin for managing modular functionality through feature toggles. Auto-updates from GitHub repository.

## Features

- Feature toggle system for enabling/disabling functionality
- Automatic updates via WP Pusher integration
- Modular architecture for easy feature additions
- Debug logging capabilities
- Admin interface for feature management

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WP Pusher plugin (required for updates)

## Installation

1. Install and activate WP Pusher
2. Configure WP Pusher with your GitHub credentials
3. Install this plugin through WP Pusher using the repository: ediblesites/snippet-aggregator
4. Activate the plugin through WordPress admin interface

## Usage

1. Go to "Snippet Aggregator" in the WordPress admin menu
2. Toggle features on/off as needed
3. Configure plugin settings under Settings > Snippet Aggregator

## Development

Features are organized in a modular structure:

```
features/
├── feature-name/
│   ├── feature-name.php    # Main feature code
│   ├── settings.php       # Optional settings (if needed)
│   └── info.php          # Feature metadata
```

### Adding a New Feature

1. Each feature must be in its own directory under `features/`
2. Directory name must be kebab-case (e.g., `my-feature`)
3. Feature must include at least these two files:
   - Main PHP file with the feature code
   - `info.php` with feature metadata
4. Optionally, a feature can include a `settings.php` file to add a settings tab

### info.php Format

The `info.php` file must return an array with these keys:
```php
<?php
return [
    'name' => 'Feature Name',        // Human-readable name
    'description' => 'One line description of what the feature does',
    'main_file' => 'feature-name.php', // Must match your main file name
    'context' => 'admin'  // Optional: 'admin', 'frontend', or omit for both
];
```

### settings.php Format

If your feature requires settings, create a `settings.php` file in your feature directory. This file must define two functions using underscores (not hyphens) in the names:

```php
<?php
// For a feature directory named 'my-feature':
function my_feature_register_settings() {  // Use underscores, not hyphens
    register_setting(
        'snippet_aggregator_settings',
        'setting_name',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
}

function my_feature_render_settings() {  // Use underscores, not hyphens
    ?>
    <form action="options.php" method="post">
        <?php 
        settings_fields('snippet_aggregator_settings');
        // Security is automatically handled - nonce verification and capability checks
        ?>
        
        <h2>Feature Settings</h2>
        <!-- Your settings form fields here -->
        
        <?php submit_button(); ?>
    </form>
    <?php
}
```

### Settings Behavior

The presence of a `settings.php` file will:
1. Register your settings when WordPress initializes
2. Add a settings tab for your feature in the Snippet Aggregator settings page
3. Render your settings page when your tab is active

Settings tabs are only visible and loaded if:
- The feature is enabled in the Features tab
- The feature is allowed to run in the admin context (either has no context specified or has context = 'admin' in info.php)
- Both required functions exist and follow the naming convention

### Accessing Feature Settings

Settings are stored using WordPress options API. Access them in your feature code using:
```php
$setting_value = get_option('your_setting_name', 'default_value');
```

### Security

The settings system includes several security measures:
- Automatic nonce verification for all settings forms
- Capability checks ('manage_options' required)
- Settings are only loaded for enabled features
- Frontend-only features cannot load settings in admin
- Input sanitization through WordPress Settings API

### Debug Mode

When debug mode is enabled in Core Settings:
- Settings loading events are logged
- Settings registration is tracked
- Failed settings loads are logged with error details
- Settings access is logged when debug logging is enabled

This helps troubleshoot issues with feature settings by providing detailed logs about:
- When settings files are loaded
- Whether required functions are found
- Any errors during settings registration
- Settings access patterns

Example debug log entries:
```
[info] my-feature: Settings loaded successfully
[error] another-feature: No settings functions found
[info] third-feature: Settings registered with 3 options
```
