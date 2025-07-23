<?php
/**
 * Provides filter buttons and query handling for integration type taxonomy
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode [integration_filter_buttons] renders taxonomy filter buttons for integration archive and taxonomy pages
add_shortcode('integration_filter_buttons', function() {
    $is_integration_archive = is_post_type_archive('integration');
    $is_integ_type_tax = is_tax('integ-type');
    
    if (!$is_integration_archive && !$is_integ_type_tax) return '';
    
    $terms = get_terms([
        'taxonomy' => 'integ-type',
        'hide_empty' => true
    ]);
    
    if (is_wp_error($terms) || empty($terms)) return '';
    
    // Determine current selection
    $current = '';
    if ($is_integration_archive && isset($_GET['integ-type'])) {
        $current = sanitize_text_field($_GET['integ-type']);
    } elseif ($is_integ_type_tax) {
        $current = get_queried_object()->slug;
    }
    
    // Base URL for "All" button
    $base_url = $is_integ_type_tax ? get_post_type_archive_link('integration') : remove_query_arg('integ-type');
    
    ob_start();
    echo '<div class="wp-block-buttons alignwide is-content-justification-center is-layout-flex wp-container-core-buttons-is-layout-cc423f81 wp-block-buttons-is-layout-flex" style="gap: 5px;">';
    
    // "All" button
    $all_active = $current === '';
    echo '<div class="wp-block-button ' . ($all_active ? 'is-style-button-brand-alt is-style-button-brand-alt--7' : 'is-style-outline is-style-outline--6') . '">';
    echo '<a href="' . esc_url($base_url) . '" class="wp-block-button__link has-secondary-color has-text-color has-link-color ' . ($all_active ? '' : 'has-border-color has-primary-border-color has-border-color has-border-light-border') . ' wp-element-button"' . ($all_active ? '' : ' style="color:#3b5570b3"') . '>All</a>';
    echo '</div>';
    
    foreach ($terms as $term) {
        $is_active = $current === $term->slug;
        
        // Use taxonomy term URLs for all buttons
        $url = get_term_link($term);
        
        echo '<div class="wp-block-button ' . ($is_active ? 'is-style-button-brand-alt is-style-button-brand-alt--7' : 'is-style-outline is-style-outline--6') . '">';
        echo '<a href="' . esc_url($url) . '" class="wp-block-button__link has-secondary-color has-text-color has-link-color ' . ($is_active ? '' : 'has-border-color has-primary-border-color has-border-color has-border-light-border') . ' wp-element-button"' . ($is_active ? '' : ' style="color:#3b5570b3"') . '>' . esc_html($term->name) . '</a>';
        echo '</div>';
    }
    
    echo '</div>';
    return ob_get_clean();
});

// Filter main query on integration archive based on selected taxonomy
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query() || !is_post_type_archive('integration')) return;
    
    if (!empty($_GET['integ-type'])) {
        $query->set('tax_query', [[
            'taxonomy' => 'integ-type',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['integ-type'])
        ]]);
    }
}); 