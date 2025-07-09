# Test Scripts

## Webhook Tester

The `test-webhook.php` script simulates a GitHub webhook call to test the plugin's update functionality.

### Prerequisites

1. WordPress installation with the Snippet Aggregator plugin activated
2. Webhook secret configured in the plugin settings
3. PHP with curl extension enabled

### Usage

1. Navigate to the tests directory:
   ```bash
   cd /path/to/plugin/tests
   ```

2. Run the test script:
   ```bash
   php test-webhook.php
   ```

### What the Test Does

1. Loads WordPress environment
2. Retrieves webhook secret from WordPress options
3. Creates a simulated GitHub push event payload
4. Generates proper HMAC SHA256 signature
5. Sends POST request to the webhook endpoint
6. Displays the response

### Expected Output

On success:
```
Sending webhook request to: http://your-site.local/wp-admin/admin-ajax.php?action=snippet_aggregator_github_webhook
Using signature: sha256=...
Payload:
Array(
    [ref] => refs/heads/master
    ...
)

Response (HTTP 200):
OK
```

### Troubleshooting

1. "WordPress core not found":
   - Check that the script is in the correct location relative to WordPress
   - The script expects to be in `/wp-content/plugins/snippet-aggregator/tests/`

2. "Webhook secret not found":
   - Go to Snippet Aggregator settings in WordPress admin
   - Configure the webhook secret

3. HTTP 401 response:
   - Verify the webhook secret in WordPress matches what you're using
   - Check the signature is being generated correctly 