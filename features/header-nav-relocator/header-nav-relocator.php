<?php
/**
 * Moves the logo and navigation section to just below the header element
 */

if (!defined('ABSPATH')) {
    exit;
}

// Moves the logo-and-navs div from inside header to just below the header element

// Start output buffering at the beginning of body content
add_action('wp_body_open', function() {
    ob_start();
}, 0);

// Process the entire body content at the end
add_action('wp_footer', function() {
    $content = ob_get_clean();
    
    if (empty($content)) {
        return;
    }

    // Load content into DOM
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<!DOCTYPE html><html><body>' . $content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Find the logo-and-navs div inside header
    $xpath = new DOMXPath($dom);
    $nav_div = $xpath->query("//header//div[contains(@class, 'logo-and-navs')]")->item(0);
    
    if (!$nav_div) {
        echo $content;
        return;
    }
    
    // Store the nav content
    $nav_content = $dom->saveHTML($nav_div);
    
    // Remove it from inside the header
    $nav_div->parentNode->removeChild($nav_div);
    
    // Find the header element
    $header = $xpath->query("//header")->item(0);
    
    if ($header) {
        // Create a temporary DOM to parse the nav content
        $temp_dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $temp_dom->loadHTML('<!DOCTYPE html><html><body>' . $nav_content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        // Import the nav element into the main DOM
        $temp_body = $temp_dom->getElementsByTagName('body')->item(0);
        $nav_element = $dom->importNode($temp_body->firstChild, true);
        
        // Insert after header
        if ($header->nextSibling) {
            $header->parentNode->insertBefore($nav_element, $header->nextSibling);
        } else {
            $header->parentNode->appendChild($nav_element);
        }
    }
    
    // Output the body content only (strip the html/body wrapper)
    $body = $dom->getElementsByTagName('body')->item(0);
    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }
    
    echo $result;
}, 999); 