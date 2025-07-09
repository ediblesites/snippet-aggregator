<?php
/**
 * Creates a custom post type for managing glossary items
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register the Glossary Item custom post type
add_action('init', function() {
    $labels = [
        'name'                  => 'Glossary Items',
        'singular_name'         => 'Glossary Item',
        'menu_name'            => 'Glossary Items',
        'name_admin_bar'       => 'Glossary Item',
        'add_new'              => 'Add New',
        'add_new_item'         => 'Add New Glossary Item',
        'new_item'             => 'New Glossary Item',
        'edit_item'            => 'Edit Glossary Item',
        'view_item'            => 'View Glossary Item',
        'all_items'            => 'All Glossary Items',
        'search_items'         => 'Search Glossary Items',
        'not_found'            => 'No glossary items found.',
        'not_found_in_trash'   => 'No glossary items found in Trash.',
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => ['slug' => 'glossary'],
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-book-alt',
        'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'show_in_rest'        => true, // Enable Gutenberg editor
    ];

    register_post_type('glossary-item', $args);
});

// Add custom columns to the admin list view
add_filter('manage_glossary-item_posts_columns', function($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['excerpt'] = 'Description';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
});

// Populate custom column content
add_action('manage_glossary-item_posts_custom_column', function($column_name, $post_id) {
    if ($column_name === 'excerpt') {
        $excerpt = get_the_excerpt($post_id);
        echo wp_trim_words($excerpt, 20, '...');
    }
}, 10, 2); 