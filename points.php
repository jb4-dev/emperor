<?php
/**
 * Points & Leaderboard System
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';

// Verify session for protected actions
if (in_array($action, ['activity'])) {
    $user = verifySession($sessionId);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

switch ($action) {
    case 'activity':
        trackActivity($user);
        break;
    case 'leaderboard':
        getLeaderboard();
        break;
    case 'user_medals':
        getUserMedals();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function verifySession($sessionId) {
    if (empty($sessionId)) return null;
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.admin, u.points
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute([$sessionId]);
    return $stmt->fetch();
}

function trackActivity($user) {
    $minutes = intval($_POST['minutes'] ?? 0);
    
    if ($minutes <= 0) {
        echo json_encode(['success' => false]);
        return;
    }
    
    $points = floor($minutes * POINTS_PER_MINUTE);
    
    if ($points > 0) {
        $db = getDB();
        
        // Add points
        $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $user['id']]);
        
        // Log activity
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, activity_type, points_earned) VALUES (?, 'time', ?)");
        $stmt->execute([$user['id'], $points]);
        
        // Check for new medals
        checkMedals($user['id']);
        
        echo json_encode([
            'success' => true,
            'points_earned' => $points
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}

function checkMedals($userId) {
    $db = getDB();
    
    // Get user's current points
    $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return;
    
    // Get medals user doesn't have yet that they qualify for
    $stmt = $db->prepare("
        SELECT m.id, m.name
        FROM medals m
        LEFT JOIN user_medals um ON m.id = um.medal_id AND um.user_id = ?
        WHERE um.medal_id IS NULL AND m.points_required <= ?
    ");
    $stmt->execute([$userId, $user['points']]);
    $newMedals = $stmt->fetchAll();
    
    // Award new medals
    foreach ($newMedals as $medal) {
        $stmt = $db->prepare("INSERT INTO user_medals (user_id, medal_id) VALUES (?, ?)");
        $stmt->execute([$userId, $medal['id']]);
    }
}

function getLeaderboard() {
    $limit = intval($_GET['limit'] ?? 50);
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.username, u.points, u.created_at,
               COUNT(DISTINCT pc.id) as comments_count,
               COUNT(DISTINCT pv.id) as votes_count
        FROM users u
        LEFT JOIN post_comments pc ON u.id = pc.user_id
        LEFT JOIN post_votes pv ON u.id = pv.user_id
        WHERE u.status = 'active'
        GROUP BY u.id
        ORDER BY u.points DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $leaderboard = $stmt->fetchAll();
    
    echo json_encode(['leaderboard' => $leaderboard]);
}

function getUserMedals() {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username required']);
        return;
    }
    
    $db = getDB();
    
    // Get user ID
    $stmt = $db->prepare("SELECT id, points FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    // Get user's medals
    $stmt = $db->prepare("
        SELECT m.name, m.description, m.icon, m.color, um.earned_at
        FROM user_medals um
        JOIN medals m ON um.medal_id = m.id
        WHERE um.user_id = ?
        ORDER BY m.points_required DESC
    ");
    $stmt->execute([$user['id']]);
    $medals = $stmt->fetchAll();
    
    echo json_encode([
        'medals' => $medals,
        'points' => $user['points']
    ]);
}
?>