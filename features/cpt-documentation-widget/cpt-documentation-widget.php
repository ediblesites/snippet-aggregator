<?php
/**
 * Dashboard widget displaying custom post types with descriptions and archive links
 */

 if (!defined('ABSPATH')) {
    exit;
}

function add_cpt_documentation_dashboard_widget() {
    wp_add_dashboard_widget(
        'cpt_documentation_widget',
        'Orchestra Custom Settings',
        'display_cpt_documentation_content'
    );
}
add_action('wp_dashboard_setup', 'add_cpt_documentation_dashboard_widget');

function get_filtered_cpts() {
    static $cached_cpts = null;
    
    if ($cached_cpts !== null) {
        return $cached_cpts;
    }
    
    $excluded_cpts = ['mb-relationship', 'meta-box', 'mb-post-type', 'mb-taxonomy', 'filter-set'];
    $post_types = get_post_types(['_builtin' => false, 'show_ui' => true], 'objects');
    
    $filtered_cpts = array_filter($post_types, function($post_type) use ($excluded_cpts) {
        return !in_array($post_type->name, $excluded_cpts);
    });
    
    // Sort by menu_position to match admin sidebar order
    uasort($filtered_cpts, function($a, $b) {
        $pos_a = isset($a->menu_position) ? (int)$a->menu_position : 25;
        $pos_b = isset($b->menu_position) ? (int)$b->menu_position : 25;
        
        if ($pos_a === $pos_b) {
            return strcmp($a->labels->name, $b->labels->name);
        }
        
        return $pos_a - $pos_b;
    });
    
    $cached_cpts = $filtered_cpts;
    return $cached_cpts;
}

function get_cpt_archive_url($post_type) {
    if (!$post_type->has_archive) {
        return '';
    }
    
    if (is_string($post_type->has_archive)) {
        $archive_url = home_url($post_type->has_archive);
        return rtrim($archive_url, '/') . '/';
    }
    
    return get_post_type_archive_link($post_type->name);
}

function display_cpt_documentation_content() {
    $post_types = get_filtered_cpts();
    
    if (empty($post_types)) {
        echo '<p>No custom post types found.</p>';
        return;
    }
    
    ?>
    <div class="cpt-documentation-box">
        <div class="cpt-section">
            <h4>Custom Post Types Reference</h4>
            <?php 
            $post_types_array = array_values($post_types);
            $last_index = count($post_types_array) - 1;
            
            foreach ($post_types_array as $index => $post_type) : 
                $archive_url = get_cpt_archive_url($post_type);
                $has_description = !empty($post_type->description);
            ?>
                <div class="cpt-item">
                    <p><strong><?php echo esc_html($post_type->labels->name); ?>:</strong> 
                        <?php if ($has_description) : ?>
                            <?php echo esc_html($post_type->description); ?>
                        <?php else : ?>
                            <em>No description provided</em>
                        <?php endif; ?>
                    </p>
                    
                    <p><strong>Archive:</strong> 
                        <?php if ($archive_url) : ?>
                            <a href="<?php echo esc_url($archive_url); ?>" target="_blank"><?php echo esc_html($archive_url); ?></a>
                        <?php else : ?>
                            (n/a)
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($index < $last_index) : ?>
                        <hr>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
        .cpt-documentation-box h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #23282d;
        }
        .cpt-section {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
        }
        .cpt-item strong {
            color: #0073aa;
        }
        .cpt-item p {
            font-size: 13px;
        }
        .cpt-item a {
            color: #0073aa;
            text-decoration: none;
        }
        .cpt-item a:hover {
            text-decoration: underline;
        }
        .cpt-item hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
    </style>
    <?php
} 