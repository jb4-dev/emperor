<?php
/**
 * Admin Panel API
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';

// Verify admin session
$user = verifyAdminSession($sessionId);
if (!$user) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

switch ($action) {
    case 'get_pending_users':
        getPendingUsers();
        break;
    case 'approve_user':
        approveUser();
        break;
    case 'reject_user':
        rejectUser();
        break;
    case 'ban_user':
        banUser();
        break;
    case 'unban_user':
        unbanUser();
        break;
    case 'get_stats':
        getStats();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function verifyAdminSession($sessionId) {
    if (empty($sessionId)) return null;
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.admin
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active' AND u.admin = 1
    ");
    $stmt->execute([$sessionId]);
    return $stmt->fetch();
}

function getPendingUsers() {
    $db = getDB();
    $stmt = $db->query("
        SELECT id, username, email, created_at
        FROM users
        WHERE status = 'pending'
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll();
    
    echo json_encode(['users' => $users]);
}

function approveUser() {
    $userId = $_POST['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Get user email for notification
    $stmt = $db->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $message = "Hello {$user['username']},\n\nYour Emperor Browser account has been approved! You can now log in.\n\nEnjoy!";
        @mail($user['email'], 'Account Approved - Emperor Browser', $message);
    }
    
    echo json_encode(['success' => true]);
}

function rejectUser() {
    $userId = $_POST['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true]);
}

function banUser() {
    $userId = $_POST['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Delete all sessions
    $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true]);
}

function unbanUser() {
    $userId = $_POST['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true]);
}

function getStats() {
    $db = getDB();
    
    $stats = [];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Pending users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'");
    $stats['pending_users'] = $stmt->fetch()['count'];
    
    // Total comments
    $stmt = $db->query("SELECT COUNT(*) as count FROM post_comments");
    $stats['total_comments'] = $stmt->fetch()['count'];
    
    // Total messages
    $stmt = $db->query("SELECT COUNT(*) as count FROM direct_messages");
    $stats['total_messages'] = $stmt->fetch()['count'];
    
    echo json_encode(['stats' => $stats]);
}
?>