<?php
/**
 * Custom Post Types feature initialization
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register the custom post type
add_action('init', 'snippet_aggregator_register_recipe_post_type');
function snippet_aggregator_register_recipe_post_type() {
    $labels = array(
        'name'               => _x('Recipes', 'post type general name', 'snippet-aggregator'),
        'singular_name'      => _x('Recipe', 'post type singular name', 'snippet-aggregator'),
        'menu_name'         => _x('Recipes', 'admin menu', 'snippet-aggregator'),
        'name_admin_bar'    => _x('Recipe', 'add new on admin bar', 'snippet-aggregator'),
        'add_new'           => _x('Add New', 'recipe', 'snippet-aggregator'),
        'add_new_item'      => __('Add New Recipe', 'snippet-aggregator'),
        'new_item'          => __('New Recipe', 'snippet-aggregator'),
        'edit_item'         => __('Edit Recipe', 'snippet-aggregator'),
        'view_item'         => __('View Recipe', 'snippet-aggregator'),
        'all_items'         => __('All Recipes', 'snippet-aggregator'),
        'search_items'      => __('Search Recipes', 'snippet-aggregator'),
        'parent_item_colon' => __('Parent Recipes:', 'snippet-aggregator'),
        'not_found'         => __('No recipes found.', 'snippet-aggregator'),
        'not_found_in_trash'=> __('No recipes found in Trash.', 'snippet-aggregator')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'recipe'),
        'capability_type'   => 'post',
        'has_archive'       => true,
        'hierarchical'      => false,
        'menu_position'     => null,
        'supports'          => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
        'show_in_rest'      => true, // Enable Gutenberg editor
    );

    register_post_type('recipe', $args);
    
    snippet_aggregator_log('custom-post-types', 'Recipe post type registered', 'info');
}

// Register taxonomy for recipe categories
add_action('init', 'snippet_aggregator_register_recipe_taxonomy');
function snippet_aggregator_register_recipe_taxonomy() {
    $labels = array(
        'name'              => _x('Recipe Categories', 'taxonomy general name', 'snippet-aggregator'),
        'singular_name'     => _x('Recipe Category', 'taxonomy singular name', 'snippet-aggregator'),
        'search_items'      => __('Search Recipe Categories', 'snippet-aggregator'),
        'all_items'         => __('All Recipe Categories', 'snippet-aggregator'),
        'parent_item'       => __('Parent Recipe Category', 'snippet-aggregator'),
        'parent_item_colon' => __('Parent Recipe Category:', 'snippet-aggregator'),
        'edit_item'         => __('Edit Recipe Category', 'snippet-aggregator'),
        'update_item'       => __('Update Recipe Category', 'snippet-aggregator'),
        'add_new_item'      => __('Add New Recipe Category', 'snippet-aggregator'),
        'new_item_name'     => __('New Recipe Category Name', 'snippet-aggregator'),
        'menu_name'         => __('Recipe Categories', 'snippet-aggregator'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'recipe-category'),
        'show_in_rest'      => true, // Enable Gutenberg editor
    );

    register_taxonomy('recipe_category', array('recipe'), $args);
    
    snippet_aggregator_log('custom-post-types', 'Recipe category taxonomy registered', 'info');
}

// Add custom meta box for recipe details
add_action('add_meta_boxes', 'snippet_aggregator_add_recipe_meta_boxes');
function snippet_aggregator_add_recipe_meta_boxes() {
    add_meta_box(
        'recipe_details',
        __('Recipe Details', 'snippet-aggregator'),
        'snippet_aggregator_recipe_details_meta_box',
        'recipe',
        'normal',
        'high'
    );
}

function snippet_aggregator_recipe_details_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('recipe_details_meta_box', 'recipe_details_meta_box_nonce');

    // Get existing values
    $cooking_time = get_post_meta($post->ID, '_recipe_cooking_time', true);
    $servings = get_post_meta($post->ID, '_recipe_servings', true);

    ?>
    <p>
        <label for="cooking_time"><?php _e('Cooking Time (minutes):', 'snippet-aggregator'); ?></label>
        <input type="number" id="cooking_time" name="cooking_time" value="<?php echo esc_attr($cooking_time); ?>" />
    </p>
    <p>
        <label for="servings"><?php _e('Number of Servings:', 'snippet-aggregator'); ?></label>
        <input type="number" id="servings" name="servings" value="<?php echo esc_attr($servings); ?>" />
    </p>
    <?php
}

// Save meta box data
add_action('save_post_recipe', 'snippet_aggregator_save_recipe_meta', 10, 2);
function snippet_aggregator_save_recipe_meta($post_id, $post) {
    // Security checks
    if (!isset($_POST['recipe_details_meta_box_nonce']) ||
        !wp_verify_nonce($_POST['recipe_details_meta_box_nonce'], 'recipe_details_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the meta box data
    if (isset($_POST['cooking_time'])) {
        update_post_meta($post_id, '_recipe_cooking_time', sanitize_text_field($_POST['cooking_time']));
    }

    if (isset($_POST['servings'])) {
        update_post_meta($post_id, '_recipe_servings', sanitize_text_field($_POST['servings']));
    }
    
    snippet_aggregator_log('custom-post-types', sprintf('Recipe meta saved for post ID %d', $post_id), 'info');
}

// Add custom columns to the recipe list
add_filter('manage_recipe_posts_columns', 'snippet_aggregator_recipe_columns');
function snippet_aggregator_recipe_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'date') {
            $new_columns['cooking_time'] = __('Cooking Time', 'snippet-aggregator');
            $new_columns['servings'] = __('Servings', 'snippet-aggregator');
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}

// Fill the custom columns with data
add_action('manage_recipe_posts_custom_column', 'snippet_aggregator_recipe_column_content', 10, 2);
function snippet_aggregator_recipe_column_content($column, $post_id) {
    switch ($column) {
        case 'cooking_time':
            $cooking_time = get_post_meta($post_id, '_recipe_cooking_time', true);
            echo $cooking_time ? esc_html($cooking_time) . ' ' . __('minutes', 'snippet-aggregator') : '-';
            break;
        case 'servings':
            $servings = get_post_meta($post_id, '_recipe_servings', true);
            echo $servings ? esc_html($servings) : '-';
            break;
    }
}

// Make the custom columns sortable
add_filter('manage_edit-recipe_sortable_columns', 'snippet_aggregator_recipe_sortable_columns');
function snippet_aggregator_recipe_sortable_columns($columns) {
    $columns['cooking_time'] = 'cooking_time';
    $columns['servings'] = 'servings';
    return $columns;
}

// Handle the custom column sorting
add_action('pre_get_posts', 'snippet_aggregator_recipe_custom_orderby');
function snippet_aggregator_recipe_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('cooking_time' === $orderby) {
        $query->set('meta_key', '_recipe_cooking_time');
        $query->set('orderby', 'meta_value_num');
    } elseif ('servings' === $orderby) {
        $query->set('meta_key', '_recipe_servings');
        $query->set('orderby', 'meta_value_num');
    }
} 