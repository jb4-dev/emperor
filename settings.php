<?php
/**
 * User Settings API Handler
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';

// Verify session
$user = verifySession($sessionId);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

switch ($action) {
    case 'get':
        getSettings($user);
        break;
    case 'save':
        saveSettings($user);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function verifySession($sessionId) {
    if (empty($sessionId)) return null;
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.admin
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute([$sessionId]);
    return $stmt->fetch();
}

function getSettings($user) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT enabled_apis, default_sort, theme, notifications_enabled
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        $settings = $stmt->fetch();
        
        if ($settings) {
            // Parse enabled_apis JSON
            $enabledAPIs = $settings['enabled_apis'] ? json_decode($settings['enabled_apis'], true) : ['pgn'];
            
            echo json_encode([
                'success' => true,
                'settings' => [
                    'enabled_apis' => $enabledAPIs,
                    'default_sort' => $settings['default_sort'] ?? 'date',
                    'theme' => $settings['theme'] ?? 'dark',
                    'notifications_enabled' => (bool)$settings['notifications_enabled']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'settings' => [
                    'enabled_apis' => ['pgn'],
                    'default_sort' => 'date',
                    'theme' => 'dark',
                    'notifications_enabled' => true
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("Get settings error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load settings'
        ]);
    }
}

function saveSettings($user) {
    $enabledAPIs = $_POST['enabled_apis'] ?? '["pgn"]';
    $defaultSort = $_POST['default_sort'] ?? 'date';
    
    // Validate enabled APIs
    $apis = json_decode($enabledAPIs, true);
    if (!is_array($apis) || empty($apis)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid API selection']);
        return;
    }
    
    // Validate sort option
    $validSorts = ['date', 'popularity', 'score'];
    if (!in_array($defaultSort, $validSorts)) {
        $defaultSort = 'date';
    }
    
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            UPDATE users 
            SET enabled_apis = ?, default_sort = ?
            WHERE id = ?
        ");
        $stmt->execute([$enabledAPIs, $defaultSort, $user['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully'
        ]);
    } catch (Exception $e) {
        error_log("Save settings error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save settings'
        ]);
    }
}
?>