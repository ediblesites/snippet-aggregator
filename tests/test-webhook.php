<?php
/**
 * Test script for GitHub webhook functionality
 * 
 * Usage: 
 * 1. Set up WordPress environment variables in .env:
 *    WP_URL=http://your-site.local
 *    WP_PATH=/path/to/wordpress
 * 
 * 2. Run: php test-webhook.php
 */

// Load WordPress core
if (file_exists(dirname(__DIR__, 4) . '/wp-load.php')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
} else {
    die("WordPress core not found. Please check the path.\n");
}

// Test configuration
$webhook_url = admin_url('admin-ajax.php') . '?action=snippet_aggregator_github_webhook';
$webhook_secret = get_option('snippet_aggregator_webhook_secret');

if (empty($webhook_secret)) {
    die("Webhook secret not found in WordPress options. Please set it up first.\n");
}

// Simulate a GitHub webhook payload
$payload = json_encode([
    'zen' => 'Encourage flow.',
    'hook_id' => 556957323,
    'hook' => [
        'type' => 'Repository',
        'id' => 556957323,
        'name' => 'web',
        'active' => true,
        'events' => ['push'],
        'config' => [
            'content_type' => 'json',
            'insecure_ssl' => '0',
            'secret' => '********',
            'url' => 'https://orchsoldev.wpenginepowered.com/wp-admin/admin-ajax.php?action=snippet_aggregator_github_webhook'
        ]
    ],
    'repository' => [
        'id' => 1016579278,
        'name' => 'snippet-aggregator',
        'full_name' => 'ediblesites/snippet-aggregator',
        'private' => false,
        'default_branch' => 'master'
    ],
    'sender' => [
        'login' => 'adam-marash',
        'id' => 35106583,
        'type' => 'User'
    ]
]);

// Generate signature
$signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);

// Send webhook request
$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Hub-Signature-256: ' . $signature,
        'X-GitHub-Event: push',
        'X-GitHub-Delivery: ' . uuid_v4()
    ]
]);

echo "Sending webhook request to: $webhook_url\n";
echo "Using signature: $signature\n";
echo "Payload:\n" . print_r(json_decode($payload, true), true) . "\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nResponse (HTTP $http_code):\n$response\n";

/**
 * Generate a UUID v4
 * @return string
 */
function uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
} 