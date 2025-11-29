<?php
/**
 * Emperor Browser Server-side Configuration
 * This file is NOT accessible from the web (protected by .htaccess)
 */

// API Configuration
define('API_BASE_URL', 'https://');
define('API_USERNAME', '');  // Replace with your username
define('API_KEY', '');    // Replace with your API key

// Do not allow direct access to this file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Access denied');
}
?>