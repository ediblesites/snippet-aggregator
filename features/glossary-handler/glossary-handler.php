<?php
/**
 * Glossary handler - applies definitions and manages settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load components
require_once __DIR__ . '/includes/url-handling.php';     // URL inclusion/exclusion functions
require_once __DIR__ . '/includes/term-processing.php';  // Term processing and content filtering
require_once __DIR__ . '/includes/cache-management.php'; // Cache handling functions
require_once __DIR__ . '/includes/styles.php';          // Frontend styles
require_once __DIR__ . '/includes/archive-anchors.php'; // Term ids on the glossary archive for /glossary/#slug links 