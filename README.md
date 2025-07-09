# Snippet Aggregator

Internal WordPress plugin for managing modular functionality through feature toggles. Auto-updates from GitHub repository.

## Current Setup

Plugin currently uses a hardcoded public GitHub repository for updates.

## Usage

- Features page: Enable/disable individual features
- Settings page: Configure debug options

## Adding Features

1. Create feature directory in `features/`
2. Add `info.php`:
   ```php
   return [
       'name' => 'Feature Name',
       'description' => 'Feature description',
       'main_file' => 'feature.php'
   ];
   ```
3. Implement feature.php in your main file

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Planned Features

- Configurable GitHub repository
- Private repository support
- Webhook integration for instant updates
