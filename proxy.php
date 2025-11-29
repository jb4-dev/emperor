<?php
/**
 * Emperor Browser Image Proxy
 * Fetches images server-side to avoid CORS issues
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Get the URL parameter
$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    http_response_code(400);
    die('No URL provided');
}

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('Invalid URL');
}

// Parse URL to ensure it's from allowed domains
$parsed = parse_url($url);
// **MODIFIED**: Added servers-na.12nineteen.com and cdn-na.12nineteen.com
$allowedDomains = [
    'server.12nineteen.com',
    'test-server.12nineteen.com',
    'cdn.12nineteen.com',
    'servers-na.12nineteen.com',
    'cdn-na.12nineteen.com',
    'cdn.donmai.us',
    'danbooru.donmai.us'
];

$host = isset($parsed['host']) ? $parsed['host'] : '';
$isAllowed = false;

foreach ($allowedDomains as $domain) {
    if ($host === $domain || str_ends_with($host, '.' . $domain)) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(403);
    die('Domain not allowed: ' . htmlspecialchars($host));
}

// Check cache directory
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// **NEW**: Cache Management for 50GB Limit
// Set the maximum cache size allowed (e.g., 40GB to stay safe under 50GB)
define('MAX_CACHE_SIZE_BYTES', 40 * 1024 * 1024 * 1024); 

function manageCacheSize($cacheDir, $maxSizeBytes) {
    if (!is_dir($cacheDir)) return;

    $totalSize = 0;
    $files = [];

    // 1. Calculate current size and collect file stats
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $files[] = [
                'path' => $file->getPathname(),
                'mtime' => $file->getMTime(), // Modification time
                'size' => $file->getSize()
            ];
        }
    }

    // 2. If size is over limit, sort by age and delete oldest files
    if ($totalSize > $maxSizeBytes) {
        // Sort oldest first (lowest mtime)
        usort($files, function($a, $b) {
            return $a['mtime'] <=> $b['mtime'];
        });

        $bytesToDelete = $totalSize - $maxSizeBytes;
        $bytesDeleted = 0;
        
        // 3. Delete files until under the limit
        foreach ($files as $file) {
            if ($bytesDeleted >= $bytesToDelete) {
                break;
            }
            // Also delete the associated mime file
            $mimeFile = $file['path'] . '.mime';
            if (file_exists($mimeFile)) {
                @unlink($mimeFile);
            }
            
            if (@unlink($file['path'])) {
                $bytesDeleted += $file['size'];
            }
        }
        // error_log("Cache Cleanup: Deleted " . number_format($bytesDeleted / 1024 / 1024, 2) . " MB of old files.");
    }
}

// Run the cache management routine
// NOTE: This should run *before* the check for cache hit, to ensure the cache size is managed
manageCacheSize($cacheDir, MAX_CACHE_SIZE_BYTES);


// Create cache filename
$cacheFile = $cacheDir . '/' . md5($url);

// Check if cached (cache for 7 days - 604800 seconds)
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 604800) {
    $data = file_get_contents($cacheFile);
    $mimeFile = $cacheFile . '.mime';
    $contentType = file_exists($mimeFile) ? file_get_contents($mimeFile) : 'image/jpeg';
    
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=604800'); // <--- Updated: Changed cache time to 7 days
    header('X-Cache: HIT');
    echo $data;
    exit;
}

// Fetch the image
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Emperor Browser/1.0');
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute request
$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Check for errors
if ($data === false || $httpCode !== 200) {
    http_response_code($httpCode ?: 500);
    die('Failed to fetch image: ' . $error);
}

// Validate content type (must be an image)
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
if (!in_array($contentType, $allowedTypes)) {
    // Try to detect from data
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->buffer($data);
    
    if (in_array($detectedType, $allowedTypes)) {
        $contentType = $detectedType;
    } else {
        http_response_code(415);
        die('Unsupported media type');
    }
}

// Cache the result
file_put_contents($cacheFile, $data);
file_put_contents($cacheFile . '.mime', $contentType);

// Output the image
header('Content-Type: ' . $contentType);
// Use the new, longer cache control time for new fetches as well
header('Cache-Control: public, max-age=604800'); 
header('X-Cache: MISS');
echo $data;
?>
