<?php
/**
 * Adds responsive and modern styling to Contact Form 7 forms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue responsive Contact Form 7 styles
function cf7_responsive_styles() {
    if (!function_exists('wpcf7_enqueue_scripts')) {
        return;
    }
    
    $css = '
    /* Make form container responsive */
    .wpcf7 {
        max-width: 100%;
        margin: 0 auto;
    }

    /* Responsive form fields */
    .wpcf7 input[type="text"],
    .wpcf7 input[type="email"],
    .wpcf7 input[type="tel"],
    .wpcf7 input[type="url"],
    .wpcf7 textarea,
    .wpcf7 select {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        font-size: inherit;
        line-height: inherit;
    }

    /* Stack layout for form rows */
    .wpcf7 .form-row {
        margin-bottom: 15px;
    }

    /* Submit button styling */
    .wpcf7 input[type="submit"] {
        background: var(--theme-primary-color, #007cba);
        color: var(--theme-button-text-color, white);
        border: var(--theme-button-border, none);
        padding: 12px 24px;
        font-family: inherit;
        font-size: inherit;
        font-weight: var(--theme-button-font-weight, 600);
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .wpcf7 input[type="submit"]:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .wpcf7 input[type="submit"] {
            width: 100%;
        }
    }

    /* Simple stacked layout */
    .wpcf7-form {
        display: block;
    }

    /* Error and validation styling */
    .wpcf7-not-valid {
        border-color: #dc3232 !important;
        box-shadow: 0 0 0 1px #dc3232;
    }

    .wpcf7-validation-errors {
        background: #ffeaea;
        border: 1px solid #dc3232;
        padding: 10px;
        border-radius: 4px;
        margin: 15px 0;
        color: #dc3232;
    }

    .wpcf7-mail-sent-ok {
        background: #eafaea;
        border: 1px solid #46b450;
        padding: 10px;
        border-radius: 4px;
        margin: 15px 0;
        color: #46b450;
    }

    /* Loading spinner */
    .wpcf7-form.submitting .wpcf7-submit {
        opacity: 0.7;
        cursor: not-allowed;
    }
    ';
    
    wp_add_inline_style('wp-block-library', $css);
}
add_action('wp_enqueue_scripts', 'cf7_responsive_styles', 20); 