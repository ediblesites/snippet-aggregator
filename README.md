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
│   └── info.php           # Feature metadata
```

### Adding a New Feature

1. Each feature must be in its own directory under `features/`
2. Directory name must be kebab-case (e.g., `my-feature`)
3. Feature must include exactly two files:
   - Main PHP file with the feature code
   - `info.php` with feature metadata

### info.php Format

The `info.php` file must return an array with exactly these keys:
```php
<?php
return [
    'name' => 'Feature Name',        // Human-readable name
    'description' => 'One line description of what the feature does',
    'main_file' => 'feature-name.php' // Must match your main file name
];
```
