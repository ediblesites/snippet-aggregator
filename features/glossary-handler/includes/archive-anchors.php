<?php
/**
 * Glossary archive anchors.
 *
 * Posts link to glossary terms as /glossary/#<term-slug> (e.g. /glossary/#chargeback).
 * Give each term's title on the glossary archive the term's slug as its id, so
 * those links land on the term instead of the top of the page.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('render_block_core/post-title', function ($html, $block, $instance) {
    $post_id = $instance->context['postId'] ?? 0;
    if (!$post_id || get_post_type($post_id) !== 'glossary-item' || is_singular('glossary-item')) {
        return $html;
    }
    $tags = new WP_HTML_Tag_Processor($html);
    if ($tags->next_tag() && null === $tags->get_attribute('id')) {
        $tags->set_attribute('id', get_post_field('post_name', $post_id));
    }
    return $tags->get_updated_html();
}, 10, 3);

// The browser jumps to the anchor before the layout is done, and the sticky
// header covers the term. Jump again after load, and leave room for the header.
add_action('wp_footer', function () {
    if (!is_post_type_archive('glossary-item')) {
        return;
    }
    ?>
    <script>
    window.addEventListener('load', function () {
        var id = decodeURIComponent(location.hash.slice(1));
        var term = id && document.getElementById(id);
        if (!term) return;
        setTimeout(function () {
            var header = document.querySelector('header .is-position-sticky, header.is-position-sticky');
            var offset = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
            var top = term.getBoundingClientRect().top + window.pageYOffset - offset - 16;
            window.scrollTo({ top: top, behavior: 'auto' });
        }, 600);
    });
    </script>
    <?php
});
