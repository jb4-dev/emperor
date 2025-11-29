<?php
/**
 * Authentication Handler
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'signup':
        handleSignup();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check_session':
        checkSession();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function handleSignup() {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        return;
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be 3-50 characters']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        return;
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        return;
    }
    
    $db = getDB();
    
    // Check if username or email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username or email already exists']);
        return;
    }
    
    // Create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, 'pending')");
    
    try {
        $stmt->execute([$username, $email, $passwordHash]);
        
        // Send email notification to admin
        $message = "New user signup pending approval:\n\nUsername: $username\nEmail: $email\n\nPlease review in the admin panel.";
        @mail(ADMIN_EMAIL, 'New User Registration - Emperor Browser', $message);
        
        echo json_encode([
            'success' => true,
            'message' => 'Account created! Please wait for admin approval.'
        ]);
    } catch (PDOException $e) {
        error_log("Signup error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create account']);
    }
}

function handleLogin() {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password_hash, status, admin FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        return;
    }
    
    if ($user['status'] === 'pending') {
        http_response_code(403);
        echo json_encode(['error' => 'Account pending admin approval']);
        return;
    }
    
    if ($user['status'] === 'banned') {
        http_response_code(403);
        echo json_encode(['error' => 'Account has been banned']);
        return;
    }
    
    // Create session
    $sessionId = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    $stmt = $db->prepare("INSERT INTO sessions (id, user_id, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$sessionId, $user['id'], $expiresAt]);
    
    // Update last activity
    $stmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'username' => $user['username'],
        'is_admin' => (bool)$user['admin']
    ]);
}

function handleLogout() {
    $sessionId = $_POST['session_id'] ?? '';
    
    if ($sessionId) {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
    }
    
    echo json_encode(['success' => true]);
}

function checkSession() {
    $sessionId = $_GET['session_id'] ?? '';
    
    if (empty($sessionId)) {
        http_response_code(401);
        echo json_encode(['valid' => false]);
        return;
    }
    
    $db = getDB();
    
    // Clean expired sessions
    $db->exec("DELETE FROM sessions WHERE expires_at < NOW()");
    
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.points, u.admin, s.expires_at
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['valid' => false]);
        return;
    }
    
    // Update last activity
    $stmt = $db->prepare("UPDATE sessions SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$sessionId]);
    
    $stmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$session['id']]);
    
    echo json_encode([
        'valid' => true,
        'user' => [
            'id' => $session['id'],
            'username' => $session['username'],
            'points' => $session['points'],
            'is_admin' => (bool)$session['admin']
        ]
    ]);
}
?>