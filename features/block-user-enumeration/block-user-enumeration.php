<?php
/**
 * Blocks the common ways to list a site's users (user enumeration) for
 * visitors who are not logged in. Logged-in users keep full access, so the
 * block editor's author picker still works.
 */

if (!defined('ABSPATH')) {
    exit;
}

// REST API: remove /wp/v2/users and /wp/v2/users/<id> for visitors.
add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in()) {
        return $endpoints;
    }
    foreach (array_keys($endpoints) as $route) {
        if (preg_match('#^/wp/v2/users(/|$)#', $route)) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
});

// ?author=N redirects to /author/<login-slug>/ and reveals the slug: send
// visitors to the home page instead.
add_action('template_redirect', function () {
    if (is_user_logged_in() || !isset($_GET['author'])) {
        return;
    }
    wp_safe_redirect(home_url('/'), 301);
    exit;
}, 1);

// oEmbed: do not include the author archive URL (it holds the login slug).
add_filter('oembed_response_data', function ($data) {
    unset($data['author_url']);
    return $data;
});

// Core sitemaps: no users sitemap.
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

// Login: one message for a wrong user name and a wrong password, so the form
// does not confirm which user names exist.
add_filter('login_errors', function ($error) {
    global $errors;
    $codes = is_wp_error($errors) ? $errors->get_error_codes() : [];
    if (array_intersect($codes, ['invalid_username', 'invalid_email', 'incorrect_password'])) {
        return __('<strong>Error:</strong> The user name or password is not correct.', 'snippet-aggregator');
    }
    return $error;
});
