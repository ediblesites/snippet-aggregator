<?php
/**
 * Puzzle shortcode and front-end display logic
 */

if (!defined('ABSPATH')) {
    exit;
}

// Syntax: [puzzle width="100%" max_width="600"]

// Register the puzzle shortcode
add_shortcode('puzzle', 'puzzle_shortcode');

function puzzle_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'width' => '100%',
        'max_width' => '600px'
    ), $atts, 'puzzle');
    
    // Calculate positions based on tile size and gap
    $pos_values = array();
    for ($row = 0; $row < 4; $row++) {
        for ($col = 0; $col < 4; $col++) {
            $left = $col === 0 ? '0' : 'calc(' . $col . ' * (var(--puzzle-tile-size) + var(--puzzle-gap)))';
            $top = $row === 0 ? '0' : 'calc(' . $row . ' * (var(--puzzle-tile-size) + var(--puzzle-gap)))';
            $pos_values[] = ".pos-{$row}-{$col} { left: {$left}; top: {$top}; }";
        }
    }
    
    // Enqueue styles and scripts
    wp_enqueue_style(
        'puzzle-style',
        plugins_url('puzzle.css', __FILE__),
        array(),
        SNIPPET_AGGREGATOR_VERSION
    );
    
    wp_enqueue_script(
        'puzzle-script',
        plugins_url('puzzle.js', __FILE__),
        array(),
        SNIPPET_AGGREGATOR_VERSION,
        true
    );
    
    ob_start();
    ?>
    
    <style>
    /* Dynamic position classes */
    <?php echo implode("\n", $pos_values); ?>
    
    /* Dynamic size variables */
    #puzzleContainer {
        --puzzle-width: <?php echo esc_attr($atts['width']); ?>;
        --puzzle-max-width: <?php echo esc_attr($atts['max_width']); ?>;
    }
    </style>

    <div id="puzzleContainer">
        <div class="puzzle-loading">Loading puzzle...</div>
    </div>
    
    <?php
    return ob_get_clean();
} 