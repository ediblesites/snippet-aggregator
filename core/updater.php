<?php
/**
 * GitHub updater integration
 */

namespace SnippetAggregator\Core;

use SnippetAggregator\Core\Updater\DirectUpdater;

if (!defined('ABSPATH')) {
    exit;
}

// Register GitHub Plugin URI header
add_filter('extra_plugin_headers', function($headers) {
    $headers[] = 'GitHub Plugin URI';
    return $headers;
});

function snippet_aggregator_get_updater() {
    static $updater = null;
    
    if ($updater === null) {
        snippet_aggregator_log('updater', 'Initializing updater - existing instance was null', 'debug');
        $updater = new DirectUpdater(SNIPPET_AGGREGATOR_FILE);
        
        // TODO: Future setting in admin
        // $branch = get_option('snippet_aggregator_github_branch', 'master');
        // $updater->set_branch($branch);
    }
    
    return $updater;
}

// Add update check action for webhook
add_action('snippet_aggregator_update_check', 'snippet_aggregator_check_for_updates');

function snippet_aggregator_check_for_updates() {
    $updater = snippet_aggregator_get_updater();
    if ($updater) {
        $update_info = $updater->check_for_updates();
        if ($update_info) {
            return $updater->install_update($update_info);
        }
    }
    return null;
} 