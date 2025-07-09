<?php
/**
 * Feature Info: Date Display
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'Date Display',
    'description' => 'Displays the current date in the WordPress admin dashboard.',
    'version' => '1.0.0',
    'main_file' => 'date-display.php',
    'dependencies' => [],
    'requires' => [
        'wordpress' => '5.0.0',
        'php' => '7.2.0'
    ]
]; 