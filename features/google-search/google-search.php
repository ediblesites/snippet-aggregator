<?php
/**
 * Template-based search results using Google Custom Search API with post templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Template-based search results using Google Custom Search API with standard pagination
function template_based_search_results($posts, $query) {
	
    if (!$query->is_search() || !$query->is_main_query()) {
        return $posts;
    }
    
    $search_term = $query->get('s');
    if (empty($search_term)) {
        return $posts;
    }
    
    // Google Custom Search API configuration from wp-config
    $api_key = defined('GOOGLE_SEARCH_API_KEY') ? GOOGLE_SEARCH_API_KEY : '';
    $cse_id = defined('GOOGLE_SEARCH_CSE_ID') ? GOOGLE_SEARCH_CSE_ID : '';
    
    // Check if API credentials are configured
    if (empty($api_key) || empty($cse_id)) {
        global $search_template_results;
        $search_template_results = array(array(
            'title' => 'Google Search Not Configured',
            'excerpt' => 'Add GOOGLE_SEARCH_API_KEY and GOOGLE_SEARCH_CSE_ID to your wp-config.php file to enable Google Custom Search.',
            'url' => '#',
            'date' => '',
            'author' => '',
            'meta' => 'Configuration Required',
            'breadcrumb' => 'Error',
            'image' => ''
        ));
        return array();
    }
    
    // Get current page - WordPress uses 'paged' for pagination
    $page = max(1, get_query_var('paged', 1));
    $cache_key = 'google_search_' . md5($search_term . '_page_' . $page);
    
    // Try to get cached results first
    $cached_data = get_transient($cache_key);
    if ($cached_data !== false) {
        global $search_template_results;
        $search_template_results = $cached_data['template_results'];
        
        // Update query counts from cache for pagination
        if (isset($cached_data['total_results'])) {
            $query->found_posts = $cached_data['total_results'];
            $query->max_num_pages = $cached_data['max_pages'];
        }
        
        return array();
    }
    
    // No cache found, fetch from Google API
    $api_url = 'https://www.googleapis.com/customsearch/v1';
    $params = array(
        'key' => $api_key,
        'cx' => $cse_id,
        'q' => urlencode($search_term),
        'num' => 10,
        'start' => max(1, ($page - 1) * 10 + 1)
    );
    
    $url = $api_url . '?' . http_build_query($params);
    
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json'
        )
    ));
    
    if (is_wp_error($response)) {
        global $search_template_results;
        $search_template_results = array();
        return array();
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['items'])) {
        global $search_template_results;
        $search_template_results = array();
        return array();
    }
    
    // Convert Google results to template format
    $template_results = array();
    foreach ($data['items'] as $item) {
        // Extract date from pagemap if available
        $date = '';
        if (isset($item['pagemap']['metatags'][0]['og:article:published_time'])) {
            $date = date('F j, Y', strtotime($item['pagemap']['metatags'][0]['og:article:published_time']));
        } elseif (isset($item['pagemap']['metatags'][0]['article:published_time'])) {
            $date = date('F j, Y', strtotime($item['pagemap']['metatags'][0]['article:published_time']));
        }
        
        // Extract author from pagemap if available
        $author = '';
        if (isset($item['pagemap']['metatags'][0]['author'])) {
            $author = $item['pagemap']['metatags'][0]['author'];
        }
        
        // Extract image from pagemap if available
        $image = '';
        if (isset($item['pagemap']['cse_image'][0]['src'])) {
            $image = $item['pagemap']['cse_image'][0]['src'];
        } elseif (isset($item['pagemap']['metatags'][0]['og:image'])) {
            $image = $item['pagemap']['metatags'][0]['og:image'];
        }
        
        // Build breadcrumb from URL path
        $url_path = parse_url($item['link'], PHP_URL_PATH);
        $path_parts = array_filter(explode('/', trim($url_path, '/')));
        $breadcrumb = $item['displayLink'];
        if (!empty($path_parts)) {
            $breadcrumb .= ' › ' . implode(' › ', $path_parts);
        }
        
        $template_results[] = array(
            'title' => preg_replace('/\s+[\|\-]\s+.*$/', '', isset($item['htmlTitle']) ? $item['htmlTitle'] : $item['title']),
            'excerpt' => isset($item['htmlSnippet']) ? $item['htmlSnippet'] : $item['snippet'],
            'url' => $item['link'],
            'date' => $date,
            'author' => $author,
            'meta' => $author ? 'By ' . $author : '',
            'breadcrumb' => $breadcrumb,
            'image' => $image
        );
    }
    
    // Store results globally for template rendering
    global $search_template_results;
    $search_template_results = $template_results;
    
    // Cache the results for 1 hour
    $total_results = isset($data['searchInformation']['totalResults']) ? intval($data['searchInformation']['totalResults']) : 0;
    $max_pages = $total_results > 0 ? ceil($total_results / 10) : 1;
    
    $cache_data = array(
        'template_results' => $template_results,
        'total_results' => $total_results,
        'max_pages' => $max_pages
    );
    set_transient($cache_key, $cache_data, HOUR_IN_SECONDS);
    
    // Update query counts for WordPress pagination
    $query->found_posts = $total_results;
    $query->max_num_pages = $max_pages;
    
    return array();
}
add_filter('posts_results', 'template_based_search_results', 10, 2);

// Render template-based search results
function render_template_search_results($template_post_id) {
    global $search_template_results;
    
    if (empty($search_template_results) || !is_search()) {
        return '';
    }
    
    // Get the template post content
    $template_post = get_post($template_post_id);
    if (!$template_post) {
        return '<p>Search template not found (ID: ' . $template_post_id . ')</p>';
    }
    
    $template_content = $template_post->post_content;
    
    // Parse and render Gutenberg blocks
    $template_blocks = parse_blocks($template_content);
    $rendered_template = '';
    foreach ($template_blocks as $block) {
        $rendered_template .= render_block($block);
    }
    
    $output = '';
    
    // Loop through search results and replace fields
    foreach ($search_template_results as $result) {
        $result_html = $rendered_template;
        
        // Replace field placeholders with actual data
        $result_html = str_replace('{{TITLE}}', $result['title'], $result_html);
        $result_html = str_replace('{{EXCERPT}}', $result['excerpt'], $result_html);
        
        // Handle Gutenberg auto-prepending http:// to URL placeholder
        $result_html = str_replace('http://{{URL}}', esc_url($result['url']), $result_html);
        $result_html = str_replace('{{URL}}', esc_url($result['url']), $result_html);
        
        $result_html = str_replace('{{DATE}}', esc_html($result['date']), $result_html);
        $result_html = str_replace('{{AUTHOR}}', esc_html($result['author']), $result_html);
        $result_html = str_replace('{{META}}', esc_html($result['meta']), $result_html);
        $result_html = str_replace('{{BREADCRUMB}}', esc_html($result['breadcrumb']), $result_html);
        $result_html = str_replace('{{IMAGE}}', esc_url($result['image']), $result_html);
        
        $output .= $result_html;
    }
    
    return $output;
}

// Shortcode to display template-based search results with standard WordPress pagination
function template_search_shortcode($atts) {
    $atts = shortcode_atts(array(
        'template_id' => 0
    ), $atts);
    
    if (!$atts['template_id']) {
        return '<p>Please provide template_id parameter</p>';
    }
    
    $results_html = render_template_search_results($atts['template_id']);
    
    // Add pagination using WordPress standard pagination
    if (is_search()) {
        global $wp_query;
        
        if ($wp_query->max_num_pages > 1) {
            $pagination = paginate_links(array(
                'total' => $wp_query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'format' => 'page/%#%/',
				'base' => trailingslashit(home_url()) . '%_%',
                'add_args' => array('s' => get_search_query()),
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
                'type' => 'list',
                'mid_size' => 2,
                'end_size' => 1
            ));
            
            if ($pagination) {
                add_action('wp_head', function() {
                    static $css_added = false;
                    if (!$css_added) {
                        echo '<style>
                        .search-pagination {
                            margin: 40px 0;
                            text-align: center;
                        }
                        
                        .search-pagination ul.page-numbers {
                            display: inline-flex;
                            list-style: none;
                            margin: 0;
                            padding: 0;
                            gap: 8px;
                            justify-content: center;
                        }
                        
                        .search-pagination ul.page-numbers li {
                            margin: 0;
                        }
                        
                        .search-pagination ul.page-numbers li a,
                        .search-pagination ul.page-numbers li span {
                            display: inline-block;
                            padding: 10px 15px;
                            text-decoration: none;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                            background: #fff;
                            color: #333;
                            font-weight: 500;
                            transition: all 0.2s ease;
                            min-width: 40px;
                            text-align: center;
                        }
                        
                        .search-pagination ul.page-numbers li a:hover {
                            background: #f5f5f5;
                            border-color: #999;
                            color: #000;
                        }
                        
                        .search-pagination ul.page-numbers li span.current {
                            background: #007cba;
                            color: #fff;
                            border-color: #007cba;
                        }
                        
                        .search-pagination ul.page-numbers li span.dots {
                            background: transparent;
                            border: none;
                            color: #999;
                            cursor: default;
                        }
                        
                        .search-pagination ul.page-numbers li a.prev,
                        .search-pagination ul.page-numbers li a.next {
                            font-weight: normal;
                        }
                        </style>';
                        $css_added = true;
                    }
                });
                
                $results_html .= '<nav class="search-pagination">' . $pagination . '</nav>';
            }
        }
    }
    
    return $results_html;
}
add_shortcode('template_search_results', 'template_search_shortcode'); 