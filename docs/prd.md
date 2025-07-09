# GitHub Auto-Updating Plugin PRD

## Overview
A self-updating WordPress plugin that manages internal functionality through feature toggles and automatically stays synchronized with a private GitHub repository.

## Core Architecture

### Plugin Structure
```
my-plugin/
├── core/
│   ├── updater.php              # GitHub integration & auto-updates
│   ├── admin-interface.php      # Feature management & settings UI
│   ├── webhook.php             # GitHub webhook handling
│   └── shared/                  # Common utilities
│       ├── database.php
│       ├── templating.php
│       └── notifications.php
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
The plugin provides two admin interfaces:

1. Main Plugin Page (Snippet Aggregator)
   - Accessible via "Snippet Aggregator" in the main admin menu
   - Shows all available features with toggle switches
- Each toggle controls whether that feature's code gets loaded
   - Features are discovered automatically from the `/features/` directory

2. Settings Page (Settings > Snippet Aggregator)
   - Debug Settings
     - Simple checkbox to enable/disable debug mode
     - Clear description of logging behavior
     - Indicates it works with WP_DEBUG
   - Future (currently hardcoded): GitHub Repository Settings
     - Text field for repository (owner/repo format)
     - Password field for access token
     - Text field for branch name with 'master' default
   - Webhook Configuration
     - Pre-filled endpoint URL in readonly text field
     - One-click copy button for URL
     - Auto-generated secret in readonly text field
     - One-click copy button for secret
     - "Regenerate Secret" button with confirmation
   - All fields use standard WordPress settings API
   - Changes saved via standard WordPress form submission

### Feature Organization
- Each feature in its own directory under `/features/`
- Must contain `info.php` that defines:
  - Feature name
  - Description
  - Entry point file name
- The specified entry point file contains the feature's core functionality
- Can include additional assets (CSS, JS, templates)
- Access to shared utilities for common functionality
- Features can be enabled/disabled without plugin restart
- Changes to feature state are immediately reflected

## Core Functionality

### Debug Mode
- Toggle in settings to enable detailed logging
- Works with WP_DEBUG
- Logs feature loading, webhook processing, and updates
- Written to WordPress error log

### GitHub Integration
- Repository Settings
  - Repository URL (owner/repo format)
  - Personal access token
  - Branch to track (defaults to main)
- Webhook Configuration
  - Auto-generated endpoint URL
  - Auto-generated secret
  - Secret regeneration capability
  - Push event monitoring
- Update Behavior
  - Monitors private repository for changes
- Integrates with WordPress's native update system
- Appears as standard plugin update in admin interface
  - Uses semantic versioning via GitHub releases
  - Preserves all settings and feature states across updates

### Error Handling
- Feature loading errors are isolated
- Failed features don't affect others
- Option to auto-disable problematic features

## Technical Implementation

### Core Components

1. `admin-interface.php`: Settings & Feature Management
```php
// Provides two admin pages and manages all plugin settings
add_menu_page('Snippet Aggregator', 'Snippet Aggregator', 'manage_options', 'snippet-aggregator');
add_submenu_page('options-general.php', 'Snippet Aggregator', 'Snippet Aggregator', 'manage_options', 'snippet-aggregator-settings');
```

2. `updater.php`: GitHub Integration
```php
// Handles plugin updates via GitHub
$myUpdateChecker = PucFactory::buildUpdateChecker(
    "https://github.com/{$github_repo}/",
    __FILE__,
    'snippet-aggregator'
);
```

3. `webhook.php`: Webhook Processing
```php
// Processes GitHub webhooks and triggers updates
add_action('wp_ajax_nopriv_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');
```

This separation ensures:
- Settings management is centralized in admin-interface.php
- Update functionality is isolated in updater.php
- Webhook handling is contained in webhook.php
- Each component uses settings but doesn't manage them

