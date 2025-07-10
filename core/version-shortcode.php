<?php
/**
 * Version shortcode and REST API endpoint functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('snippet-aggregator/v1', '/version', array(
        'methods' => 'GET',
        'callback' => 'snippet_aggregator_get_version',
        'permission_callback' => '__return_true'
    ));
});

function snippet_aggregator_get_version() {
    return array(
        'version' => SNIPPET_AGGREGATOR_VERSION,
        'version_prefixed' => 'v' . SNIPPET_AGGREGATOR_VERSION
    );
}

// Register version shortcode
add_shortcode('snippet_aggregator_version', 'snippet_aggregator_version_shortcode');

function snippet_aggregator_version_shortcode($atts) {
    static $shortcode_count = 0;
    $shortcode_count++;
    $id = 'snippet-aggregator-version-' . $shortcode_count;

    $atts = shortcode_atts(array(
        'format' => 'raw' // Options: 'raw', 'v-prefix'
    ), $atts, 'snippet_aggregator_version');

    // Add inline script for this instance
    add_action('wp_footer', function() use ($id, $atts) {
        ?>
        <script>
        (function() {
            const element = document.getElementById('<?php echo esc_js($id); ?>');
            if (!element) return;
            
            fetch('/wp-json/snippet-aggregator/v1/version')
                .then(response => response.json())
                .then(data => {
                    element.textContent = '<?php echo $atts['format'] === 'v-prefix' ? "' + data.version_prefixed + '" : "' + data.version + '"; ?>';
                })
                .catch(error => {
                    console.error('Failed to fetch version:', error);
                    element.textContent = '<?php echo esc_js(SNIPPET_AGGREGATOR_VERSION); ?>';
                });
        })();
        </script>
        <?php
    });

    // Return a placeholder that will be populated by JavaScript
    return '<span id="' . esc_attr($id) . '">...</span>';
} 