# GitHub Auto-Updating Plugin PRD

## Overview
A self-updating WordPress plugin that manages internal functionality through feature toggles and automatically stays synchronized with a private GitHub repository using WP Pusher.

## Core Architecture

### Plugin Structure
```
my-plugin/
├── core/
│   ├── admin-interface.php      # Feature management & settings UI
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
2. Check for WP Pusher dependency
3. Check feature toggle settings
4. Conditionally load enabled features with error isolation

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
- Logs feature loading and updates
- Written to WordPress error log

### GitHub Integration via WP Pusher
- Prerequisites
  - WP Pusher must be installed and activated
  - WP Pusher license (free for public repositories, premium for private)
  
- Repository Setup
  1. In WP Pusher settings:
     - Configure GitHub credentials
     - Add repository (ediblesites/snippet-aggregator)
     - Set branch to track (defaults to main)
  2. Enable automatic updates in WP Pusher interface
  3. Configure webhook in GitHub repository settings using WP Pusher's webhook URL

- Update Behavior
  - WP Pusher monitors repository for changes
  - Updates are handled through WP Pusher's interface
  - Preserves all settings and feature states across updates
  - Supports both push-based (webhook) and pull-based (periodic check) updates

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

This separation ensures:
- Settings management is centralized in admin-interface.php
- Updates are handled by WP Pusher
- Each component uses settings but doesn't manage them

