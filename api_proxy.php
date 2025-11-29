<?php
/**
 * Emperor Browser API Proxy
 * Proxies all API requests to avoid CORS issues
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Load config
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die(json_encode(['error' => 'Configuration file not found']));
}

require_once $configFile;

// Get request parameters
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$params = $_GET;
unset($params['endpoint']); // Remove endpoint from params

if (empty($endpoint)) {
    http_response_code(400);
    die(json_encode(['error' => 'No endpoint specified']));
}

// Build URL
$url = API_BASE_URL . $endpoint;

// Add authentication
$params['login'] = API_USERNAME;
$params['api_key'] = API_KEY;

// Build query string
$queryString = http_build_query($params);
$fullUrl = $url . '?' . $queryString;

// Initialize cURL
$ch = curl_init($fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Emperor Browser/1.0');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Check for errors
if ($response === false) {
    http_response_code(500);
    die(json_encode(['error' => 'cURL error: ' . $error]));
}

// Return response with same status code
http_response_code($httpCode);
echo $response;
?>