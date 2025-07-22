<?php
/**
 * Settings page for Glossary feature
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register settings
function glossary_handler_register_settings() {
    register_setting(
        'snippet_aggregator_glossary_settings',
        'glossary_post_types',
        [
            'type' => 'array',
            'default' => ['post', 'page', 'use-case'],
            'sanitize_callback' => function($value) {
                return is_array($value) ? array_map('sanitize_text_field', $value) : [];
            }
        ]
    );
    
    register_setting(
        'snippet_aggregator_glossary_settings',
        'glossary_exclusions',
        [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ]
    );
    
    register_setting(
        'snippet_aggregator_glossary_settings',
        'glossary_inclusions',
        [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ]
    );
}

// Render settings page
function glossary_handler_render_settings() {
    // Clear cache if requested
    if (isset($_POST['flush_glossary_cache'])) {
        delete_transient('glossary_terms_data');
        add_settings_error(
            'snippet_aggregator_messages',
            'glossary_cache_cleared',
            __('Glossary cache cleared!', 'snippet-aggregator'),
            'success'
        );
    }
    
    // Get current values
    $current_post_types = get_option('glossary_post_types', ['post', 'page', 'use-case']);
    $current_exclusions = get_option('glossary_exclusions', '');
    $current_inclusions = get_option('glossary_inclusions', '');
    $all_post_types = get_post_types(['public' => true], 'objects');
    ?>
    <form action="options.php" method="post">
        <?php 
        settings_fields('snippet_aggregator_settings');
        
        // Include all feature toggles to prevent them from being deleted
        $features = snippet_aggregator_get_available_features();
        foreach ($features as $feature_id => $feature) {
            $current_toggle = get_option("snippet_aggregator_feature_{$feature_id}") ? '1' : '0';
            echo '<input type="hidden" name="snippet_aggregator_feature_' . esc_attr($feature_id) . '" value="' . $current_toggle . '">';
        }
        ?>
        
        <h2><?php _e('Glossary Settings', 'snippet-aggregator'); ?></h2>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php _e('Post Types', 'snippet-aggregator'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Select post types to process', 'snippet-aggregator'); ?></legend>
                        <?php foreach ($all_post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" 
                                       name="glossary_post_types[]" 
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(in_array($post_type->name, $current_post_types)); ?> />
                                <?php echo esc_html($post_type->label); ?>
                            </label><br>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Select which post types should have glossary terms automatically linked.', 'snippet-aggregator'); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Included URLs', 'snippet-aggregator'); ?></th>
                <td>
                    <textarea name="glossary_inclusions" 
                              rows="10" 
                              cols="50" 
                              class="large-text"
                              placeholder="/category/tutorials/&#10;/tag/glossary-terms/&#10;/products/*"><?php echo esc_textarea($current_inclusions); ?></textarea>
                    <p class="description">
                        <?php _e('Enter URLs to include for glossary processing, one per line. These override post type restrictions.', 'snippet-aggregator'); ?><br>
                        <?php _e('Use * for wildcards (e.g., /category/* includes all category pages).', 'snippet-aggregator'); ?><br>
                        <?php _e('Use / for the homepage.', 'snippet-aggregator'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Excluded URLs', 'snippet-aggregator'); ?></th>
                <td>
                    <textarea name="glossary_exclusions" 
                              rows="10" 
                              cols="50" 
                              class="large-text"
                              placeholder="/contact&#10;/checkout/*"><?php echo esc_textarea($current_exclusions); ?></textarea>
                    <p class="description">
                        <?php _e('Enter URLs to exclude from glossary processing, one per line.', 'snippet-aggregator'); ?><br>
                        <?php _e('Use * for wildcards (e.g., /checkout/* excludes all checkout pages).', 'snippet-aggregator'); ?><br>
                        <?php _e('Use / for the homepage.', 'snippet-aggregator'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('Cache Management', 'snippet-aggregator'); ?></h2>
    <p><?php _e('If you\'ve updated glossary items and changes aren\'t appearing, flush the cache to force a refresh.', 'snippet-aggregator'); ?></p>
    <form method="post" style="display: inline;">
        <input type="submit" 
               name="flush_glossary_cache" 
               class="button button-secondary" 
               value="<?php esc_attr_e('Flush Glossary Cache', 'snippet-aggregator'); ?>" />
    </form>
    <?php
} 