<?php
/**
 * Last Modified Column Feature
 * 
 * Adds a sortable Last Modified column to all post type lists in admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add Last Modified column to all post types with UI
add_action('admin_init', function() {
    $post_types = get_post_types(['show_ui' => true], 'names');
    foreach ($post_types as $post_type) {
        add_filter("manage_edit-{$post_type}_columns", 'add_last_modified_column', 20);
        add_action("manage_{$post_type}_posts_custom_column", 'render_last_modified_column', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'make_last_modified_sortable');
    }
});

/**
 * Add Last Modified column after the Date column
 */
function add_last_modified_column($columns) {
    $new = [];
    foreach ($columns as $key => $value) {
        if ($key === 'date') {
            $new['last_modified'] = __('Last Modified', 'snippet-aggregator');
        }
        $new[$key] = $value;
    }
    return $new;
}

/**
 * Render the Last Modified column content
 */
function render_last_modified_column($column_name, $post_id) {
    if ($column_name === 'last_modified') {
        $modified = get_post_field('post_modified', $post_id);
        $timestamp = strtotime($modified);
        echo sprintf(
            __('Last Modified<br>%1$s at %2$s', 'snippet-aggregator'),
            date_i18n(get_option('date_format'), $timestamp),
            date_i18n(get_option('time_format'), $timestamp)
        );
    }
}

/**
 * Make the Last Modified column sortable
 */
function make_last_modified_sortable($columns) {
    $columns['last_modified'] = 'modified';
    return $columns;
}

/**
 * Handle sorting by Last Modified
 */
add_action('pre_get_posts', function($query) {
    if (is_admin() && $query->is_main_query()) {
        if ($query->get('orderby') === 'modified') {
            $query->set('orderby', 'modified');
        }
    }
}); 