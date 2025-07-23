<?php
/**
 * Enhances menu dropdown items with full-row click areas and hover effects
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.gw-mm-item__dropdown-content .linkme').forEach(row => {
        const link = row.querySelector('.linkhere a[href]');
        if (!link) return;

        const linkHref = link.getAttribute('href');
        if (!linkHref) return;

        row.style.cursor = 'pointer';

        row.addEventListener('click', e => {
          if (!e.target.closest('a')) {
            window.location.href = linkHref;
          }
        });

        row.addEventListener('mouseenter', () => {
          row.style.backgroundColor = '#f0f0f0';
        });

        row.addEventListener('mouseleave', () => {
          row.style.backgroundColor = '';
        });
      });
    });
    </script>
    <?php
}); 