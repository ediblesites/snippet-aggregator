<?php
/**
 * Puzzle shortcode and front-end display logic
 */

if (!defined('ABSPATH')) {
    exit;
}

// Syntax: [puzzle width="600" height="600" tile_size="140" gap="10"]

// Register the puzzle shortcode
add_shortcode('puzzle', 'puzzle_shortcode');

function puzzle_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'width' => '572',
        'height' => '572',
        'tile_size' => '135',
        'gap' => '8'
    ), $atts, 'puzzle');
    
    // Calculate positions based on tile size and gap
    $pos_values = array();
    for ($row = 0; $row < 4; $row++) {
        for ($col = 0; $col < 4; $col++) {
            $left = $col * ($atts['tile_size'] + $atts['gap']);
            $top = $row * ($atts['tile_size'] + $atts['gap']);
            $pos_values[] = ".pos-{$row}-{$col} { left: {$left}px; top: {$top}px; }";
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
        --puzzle-width: <?php echo $atts['width']; ?>px;
        --puzzle-height: <?php echo $atts['height']; ?>px;
        --puzzle-tile-size: <?php echo $atts['tile_size']; ?>px;
        --puzzle-gap: <?php echo $atts['gap']; ?>px;
    }
    </style>

    <div id="puzzleContainer">
        <div class="puzzle-loading">Loading puzzle...</div>
    </div>
    
    <?php
    return ob_get_clean();
} 