<?php
/**
 * Admin settings page for glossary
 */

if (!defined('ABSPATH')) {
    exit;
}

// Admin settings page
function glossary_settings_menu() {
    add_options_page(
        'Glossary Settings',
        'Glossary',
        'manage_options',
        'glossary-settings',
        'glossary_settings_page'
    );
}
add_action('admin_menu', 'glossary_settings_menu');

// Settings page content
function glossary_settings_page() {
    $message = '';
    
    if (isset($_POST['submit'])) {
        // Save post types
        $post_types = isset($_POST['glossary_post_types']) ? $_POST['glossary_post_types'] : [];
        update_option('glossary_post_types', $post_types);
        
        // Save exclusions
        $exclusions = isset($_POST['glossary_exclusions']) ? sanitize_textarea_field($_POST['glossary_exclusions']) : '';
        update_option('glossary_exclusions', $exclusions);
        
        // Save inclusions
        $inclusions = isset($_POST['glossary_inclusions']) ? sanitize_textarea_field($_POST['glossary_inclusions']) : '';
        update_option('glossary_inclusions', $inclusions);
        
        // Clear cache when settings change
        delete_transient('glossary_terms_data');
        
        $message = '<div class="notice notice-success"><p>Settings saved and cache cleared!</p></div>';
    }
    
    if (isset($_POST['flush_cache'])) {
        delete_transient('glossary_terms_data');
        $message = '<div class="notice notice-success"><p>Glossary cache cleared!</p></div>';
    }
    
    echo $message;
    
    $current_post_types = get_option('glossary_post_types', ['post', 'page', 'use-case']);
    $current_exclusions = get_option('glossary_exclusions', '');
    $current_inclusions = get_option('glossary_inclusions', '');
    $all_post_types = get_post_types(['public' => true], 'objects');
    ?>
    <div class="wrap">
        <h1>Glossary Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Post Types</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">Select post types to process</legend>
                            <?php foreach ($all_post_types as $post_type): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="glossary_post_types[]" 
                                           value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php checked(in_array($post_type->name, $current_post_types)); ?> />
                                    <?php echo esc_html($post_type->label); ?>
                                </label><br>
                            <?php endforeach; ?>
                            <p class="description">Select which post types should have glossary terms automatically linked.</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Included URLs</th>
                    <td>
                        <textarea name="glossary_inclusions" 
                                  rows="10" 
                                  cols="50" 
                                  class="large-text"
                                  placeholder="/category/tutorials/&#10;/tag/glossary-terms/&#10;/products/*"><?php echo esc_textarea($current_inclusions); ?></textarea>
                        <p class="description">
                            Enter URLs to include for glossary processing, one per line. These override post type restrictions.<br>
                            Use * for wildcards (e.g., /category/* includes all category pages).<br>
                            Use / for the homepage.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Excluded URLs</th>
                    <td>
                        <textarea name="glossary_exclusions" 
                                  rows="10" 
                                  cols="50" 
                                  class="large-text"
                                  placeholder="/contact&#10;/checkout/*"><?php echo esc_textarea($current_exclusions); ?></textarea>
                        <p class="description">
                            Enter URLs to exclude from glossary processing, one per line.<br>
                            Use * for wildcards (e.g., /checkout/* excludes all checkout pages).<br>
                            Use / for the homepage.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        
        <hr>
        
        <h2>Cache Management</h2>
        <p>If you've updated glossary items and changes aren't appearing, flush the cache to force a refresh.</p>
        <form method="post" action="" style="display: inline;">
            <input type="submit" name="flush_cache" class="button button-secondary" value="Flush Glossary Cache" />
        </form>
    </div>
    <?php
} 