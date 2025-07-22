<?php
/**
 * Settings page for Google Search feature
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register settings
function google_search_register_settings() {
    register_setting(
        'snippet_aggregator_google_search_settings',
        'snippet_aggregator_google_search_api_key',
        [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
    
    register_setting(
        'snippet_aggregator_google_search_settings',
        'snippet_aggregator_google_search_cse_id',
        [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
}

// Render settings page
function google_search_render_settings() {
    ?>
    <form action="options.php" method="post">
        <?php 
        settings_fields('snippet_aggregator_settings');
        $current_toggle = get_option('snippet_aggregator_feature_google-search') ? '1' : '0';
        ?>
        
        <input type="hidden" 
               name="snippet_aggregator_feature_google-search" 
               value="<?php echo $current_toggle; ?>">
        
        <h2><?php _e('Google Search API Settings', 'snippet-aggregator'); ?></h2>
        <p class="description">
            <?php _e('Configure your Google Custom Search API credentials. These are required for the template-based search results feature.', 'snippet-aggregator'); ?>
        </p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php _e('API Key', 'snippet-aggregator'); ?></th>
                <td>
                    <input type="password" 
                           name="snippet_aggregator_google_search_api_key" 
                           value="<?php echo esc_attr(get_option('snippet_aggregator_google_search_api_key', '')); ?>"
                           class="regular-text"
                           autocomplete="off">
                    <p class="description">
                        <?php _e('Your Google Custom Search API key. Get one from the <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>.', 'snippet-aggregator'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Search Engine ID', 'snippet-aggregator'); ?></th>
                <td>
                    <input type="text" 
                           name="snippet_aggregator_google_search_cse_id" 
                           value="<?php echo esc_attr(get_option('snippet_aggregator_google_search_cse_id', '')); ?>"
                           class="regular-text">
                    <p class="description">
                        <?php _e('Your Google Custom Search Engine ID. Create one at <a href="https://programmablesearchengine.google.com/" target="_blank">Programmable Search Engine</a>.', 'snippet-aggregator'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
} 