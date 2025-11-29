<?php
/**
 * Direct Messages API Handler - Fixed
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
    case 'send':
        sendMessage($user);
        break;
    case 'get_conversations':
        getConversations($user);
        break;
    case 'get_messages':
        getMessages($user);
        break;
    case 'mark_read':
        markAsRead($user);
        break;
    case 'search_users':
        searchUsers();
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

function sendMessage($user) {
    $recipientUsername = trim($_POST['recipient'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($recipientUsername) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Recipient and message required']);
        return;
    }
    
    if (strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long (max 1000 characters)']);
        return;
    }
    
    $db = getDB();
    
    // Get recipient ID
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$recipientUsername]);
    $recipient = $stmt->fetch();
    
    if (!$recipient) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    if ($recipient['id'] == $user['id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot send message to yourself']);
        return;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Insert message
        $stmt = $db->prepare("INSERT INTO direct_messages (sender_id, recipient_id, message, read_status, created_at) VALUES (?, ?, ?, 0, NOW())");
        $result = $stmt->execute([$user['id'], $recipient['id'], $message]);
        
        if (!$result) {
            throw new Exception("Failed to insert message");
        }
        
        $messageId = $db->lastInsertId();
        
        // Award points
        $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([POINTS_MESSAGE, $user['id']]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, activity_type, points_earned) VALUES (?, 'message', ?)");
        $stmt->execute([$user['id'], POINTS_MESSAGE]);
        
        // Commit transaction
        $db->commit();
        
        // Return the new message data
        $stmt = $db->prepare("SELECT id, message, created_at, sender_id FROM direct_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message_sent' => 'Message sent',
            'points_earned' => POINTS_MESSAGE,
            'new_message' => $newMessage
        ]);
    } catch (Exception $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Message send error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
}

function getConversations($user) {
    $db = getDB();
    
    try {
        // Get unique conversation partners
        $stmt = $db->prepare("
            SELECT DISTINCT
                u.id as other_user_id,
                u.username as other_username
            FROM direct_messages dm
            JOIN users u ON (
                (dm.sender_id = ? AND u.id = dm.recipient_id) OR
                (dm.recipient_id = ? AND u.id = dm.sender_id)
            )
            WHERE u.status = 'active'
            ORDER BY u.username
        ");
        $stmt->execute([$user['id'], $user['id']]);
        $partners = $stmt->fetchAll();
        
        $conversations = [];
        foreach ($partners as $partner) {
            // Get unread count
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM direct_messages 
                WHERE sender_id = ? AND recipient_id = ? AND read_status = 0
            ");
            $stmt->execute([$partner['other_user_id'], $user['id']]);
            $unread = $stmt->fetch()['count'];
            
            // Get last message
            $stmt = $db->prepare("
                SELECT message, created_at
                FROM direct_messages
                WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([
                $user['id'], $partner['other_user_id'],
                $partner['other_user_id'], $user['id']
            ]);
            $lastMsg = $stmt->fetch();
            
            $conversations[] = [
                'other_user_id' => $partner['other_user_id'],
                'other_username' => $partner['other_username'],
                'unread_count' => intval($unread),
                'last_message' => $lastMsg ? $lastMsg['message'] : null,
                'last_message_time' => $lastMsg ? $lastMsg['created_at'] : null
            ];
        }
        
        // Sort by last message time
        usort($conversations, function($a, $b) {
            if (!$a['last_message_time']) return 1;
            if (!$b['last_message_time']) return -1;
            return strcmp($b['last_message_time'], $a['last_message_time']);
        });
        
        echo json_encode(['conversations' => $conversations]);
    } catch (Exception $e) {
        error_log("Get conversations error: " . $e->getMessage());
        echo json_encode(['conversations' => []]);
    }
}

function getMessages($user) {
    $otherUsername = $_GET['user'] ?? '';
    
    if (empty($otherUsername)) {
        http_response_code(400);
        echo json_encode(['error' => 'User required', 'messages' => []]);
        return;
    }
    
    $db = getDB();
    
    try {
        // Get other user ID
        $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$otherUsername]);
        $otherUser = $stmt->fetch();
        
        if (!$otherUser) {
            error_log("User not found: " . $otherUsername);
            http_response_code(404);
            echo json_encode(['error' => 'User not found', 'messages' => []]);
            return;
        }
        
        error_log("Fetching messages between user " . $user['id'] . " and " . $otherUser['id']);
        
        // Get messages - simplified query
        $stmt = $db->prepare("
            SELECT 
                dm.id, 
                dm.message, 
                dm.created_at, 
                dm.read_status,
                dm.sender_id,
                dm.recipient_id,
                u.username as sender_username
            FROM direct_messages dm
            JOIN users u ON dm.sender_id = u.id
            WHERE 
                (dm.sender_id = ? AND dm.recipient_id = ?) OR 
                (dm.sender_id = ? AND dm.recipient_id = ?)
            ORDER BY dm.created_at ASC
        ");
        
        $stmt->execute([
            $user['id'], $otherUser['id'],
            $otherUser['id'], $user['id']
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($messages) . " messages");
        
        // Add is_mine flag
        foreach ($messages as &$msg) {
            $msg['is_mine'] = ($msg['sender_id'] == $user['id']);
        }
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'count' => count($messages),
            'debug' => [
                'current_user_id' => $user['id'],
                'other_user_id' => $otherUser['id'],
                'other_username' => $otherUser['username']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get messages error: " . $e->getMessage());
        echo json_encode([
            'error' => $e->getMessage(),
            'messages' => []
        ]);
    }
}

function markAsRead($user) {
    $otherUsername = $_POST['user'] ?? '';
    
    if (empty($otherUsername)) {
        http_response_code(400);
        echo json_encode(['error' => 'User required']);
        return;
    }
    
    $db = getDB();
    
    try {
        // Get other user ID
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$otherUsername]);
        $otherUser = $stmt->fetch();
        
        if (!$otherUser) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Mark messages as read
        $stmt = $db->prepare("
            UPDATE direct_messages 
            SET read_status = 1 
            WHERE sender_id = ? AND recipient_id = ? AND read_status = 0
        ");
        $stmt->execute([$otherUser['id'], $user['id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Mark read error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function searchUsers() {
    $query = trim($_GET['query'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode(['users' => []]);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT username, points
        FROM users
        WHERE username LIKE ? AND status = 'active'
        LIMIT 10
    ");
    $stmt->execute(['%' . $query . '%']);
    $users = $stmt->fetchAll();
    
    echo json_encode(['users' => $users]);
}
?>