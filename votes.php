<?php
/**
 * Voting System API Handler - Fixed
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';

// Verify session for protected actions
if (in_array($action, ['vote', 'user_vote'])) {
    $user = verifySession($sessionId);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

switch ($action) {
    case 'vote':
        handleVote($user);
        break;
    case 'get':
        getVotes();
        break;
    case 'user_vote':
        getUserVote($user);
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

function handleVote($user) {
    $postId = $_POST['post_id'] ?? '';
    $voteValue = intval($_POST['vote'] ?? 0);
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    if ($voteValue !== 1 && $voteValue !== -1) {
        http_response_code(400);
        echo json_encode(['error' => 'Vote must be 1 or -1']);
        return;
    }
    
    $db = getDB();
    
    try {
        // Check if user already voted
        $stmt = $db->prepare("SELECT vote FROM post_votes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user['id']]);
        $existingVote = $stmt->fetch();
        
        if ($existingVote) {
            // If same vote, remove it (toggle off)
            if ($existingVote['vote'] == $voteValue) {
                $stmt = $db->prepare("DELETE FROM post_votes WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$postId, $user['id']]);
            } else {
                // Update to new vote
                $stmt = $db->prepare("UPDATE post_votes SET vote = ? WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$voteValue, $postId, $user['id']]);
            }
        } else {
            // Insert new vote
            $stmt = $db->prepare("INSERT INTO post_votes (post_id, user_id, vote) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $user['id'], $voteValue]);
            
            // Award points
            $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $stmt->execute([POINTS_VOTE, $user['id']]);
            
            // Log activity
            $stmt = $db->prepare("INSERT INTO activity_log (user_id, activity_type, points_earned) VALUES (?, 'vote', ?)");
            $stmt->execute([$user['id'], POINTS_VOTE]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Vote error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to record vote']);
    }
}

function getVotes() {
    $postId = $_GET['post_id'] ?? '';
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    $db = getDB();
    
    // Get upvote count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_votes WHERE post_id = ? AND vote = 1");
    $stmt->execute([$postId]);
    $upvotes = $stmt->fetch()['count'];
    
    // Get downvote count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_votes WHERE post_id = ? AND vote = -1");
    $stmt->execute([$postId]);
    $downvotes = $stmt->fetch()['count'];
    
    // Calculate score
    $score = $upvotes - $downvotes;
    
    echo json_encode([
        'upvotes' => intval($upvotes),
        'downvotes' => intval($downvotes),
        'score' => $score
    ]);
}

function getUserVote($user) {
    $postId = $_GET['post_id'] ?? '';
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT vote FROM post_votes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $user['id']]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'vote' => $result ? intval($result['vote']) : 0
    ]);
}
?>