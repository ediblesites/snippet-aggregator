# Snippet Aggregator

A self-updating WordPress plugin that manages internal functionality through feature toggles and automatically stays synchronized with a private GitHub repository.

## Features

- Automatic updates from a private GitHub repository
- Feature toggle system for enabling/disabling functionality
- Error isolation to prevent feature failures from affecting other features
- Built-in logging system with optional database storage
- Clean and familiar WordPress admin interface
- Webhook support for instant updates on GitHub pushes

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for installation)
- GitHub repository with personal access token

## Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/your-username/snippet-aggregator.git
   ```

2. Install dependencies using Composer:
   ```bash
   cd snippet-aggregator
   composer install
   ```

3. Activate the plugin through the WordPress admin interface

## Configuration

1. Go to the Snippet Aggregator settings page in WordPress admin
2. Configure GitHub integration:
   - Enter your GitHub repository (format: username/repository)
   - Add your GitHub personal access token
   - Set the branch to track (default: main)
   - Copy the webhook secret and URL
3. Set up the webhook in your GitHub repository:
   - Go to your repository settings
   - Navigate to Webhooks
   - Add new webhook
   - Paste the webhook URL and secret
   - Select "application/json" as content type
   - Choose "Just the push event"

## Adding New Features

1. Create a new directory under `features/` with your feature name
2. Create an `info.php` file in your feature directory:
   ```php
   <?php
   return [
       'name' => 'Your Feature Name',
       'description' => 'Description of what your feature does',
       'version' => '1.0.0'
   ];
   ```
3. Create an `init.php` file with your feature's functionality
4. The feature will automatically appear in the admin interface for enabling/disabling

## Development

- Follow WordPress coding standards
- Use the built-in logging system for debugging
- Test features in isolation before deployment
- Keep features modular and independent

## License

GPL v2 or later # snippet-aggregator
