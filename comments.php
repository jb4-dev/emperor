<?php
/**
 * Comments API Handler
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
        getComments();
        break;
    case 'post':
        postComment($user);
        break;
    case 'delete':
        deleteComment($user);
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

function getComments() {
    $postId = $_GET['post_id'] ?? '';
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.id, c.comment, c.created_at, c.edited_at,
               u.username, u.points
        FROM post_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();
    
    echo json_encode(['comments' => $comments]);
}

function postComment($user) {
    $postId = $_POST['post_id'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($postId) || empty($comment)) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID and comment required']);
        return;
    }
    
    if (strlen($comment) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment too long (max 2000 characters)']);
        return;
    }
    
    $db = getDB();
    
    try {
        // Insert comment
        $stmt = $db->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $user['id'], $comment]);
        
        // Award points
        $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([POINTS_COMMENT, $user['id']]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, activity_type, points_earned) VALUES (?, 'comment', ?)");
        $stmt->execute([$user['id'], POINTS_COMMENT]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment posted',
            'points_earned' => POINTS_COMMENT
        ]);
    } catch (PDOException $e) {
        error_log("Comment post error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to post comment']);
    }
}

function deleteComment($user) {
    $commentId = $_POST['comment_id'] ?? '';
    
    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment ID required']);
        return;
    }
    
    $db = getDB();
    
    // Check ownership or admin
    $stmt = $db->prepare("SELECT user_id FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found']);
        return;
    }
    
    if ($comment['user_id'] != $user['id'] && !$user['admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to delete this comment']);
        return;
    }
    
    $stmt = $db->prepare("DELETE FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    echo json_encode(['success' => true]);
}
?>