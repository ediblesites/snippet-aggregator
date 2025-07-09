<?php
/**
 * GitHub webhook handler
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add webhook endpoint
add_action('wp_ajax_nopriv_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');
add_action('wp_ajax_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');

function snippet_aggregator_handle_github_webhook() {
    // Verify webhook signature
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!snippet_aggregator_verify_github_signature($payload, $signature)) {
        wp_die('Unauthorized', 401);
    }
    
    // Parse webhook data
    $data = json_decode($payload, true);
    if (!is_array($data)) {
        wp_die('Invalid payload', 400);
    }
    
    // Get the event type
    $event_type = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    
    switch ($event_type) {
        case 'ping':
            wp_send_json_success(['message' => 'Webhook configured successfully']);
            break;
            
        case 'push':
            // Get the default branch from the repository data
            $default_branch = $data['repository']['default_branch'] ?? 'master';
            
            // Extract the ref from the push event
            $ref = $data['ref'] ?? '';
            $branch = str_replace('refs/heads/', '', $ref);
            
            // Only process pushes to the default branch
            if ($branch === $default_branch) {
                $result = snippet_aggregator_check_for_updates();
                wp_send_json_success([
                    'message' => $result ?? 'Push received but update check failed'
                ]);
            }
            
            wp_send_json_success([
                'message' => 'Push received but ignored - not default branch'
            ]);
            break;
            
        default:
            wp_send_json_success([
                'message' => 'Event received but ignored - unsupported type'
            ]);
    }
}

function snippet_aggregator_verify_github_signature($payload, $signature) {
    $webhook_secret = get_option('snippet_aggregator_webhook_secret');
    if (empty($webhook_secret)) {
        return false;
    }
    
    if (empty($signature)) {
        return false;
    }
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    return hash_equals($expected_signature, $signature);
} 