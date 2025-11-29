-- Emperor Browser Database Schema

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'banned') DEFAULT 'pending',
    points INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin BOOLEAN DEFAULT FALSE,
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_points (points)
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at),
    INDEX idx_user (user_id)
);

CREATE TABLE IF NOT EXISTS post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS post_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    vote TINYINT NOT NULL CHECK (vote IN (-1, 1)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (post_id, user_id),
    INDEX idx_post (post_id)
);

CREATE TABLE IF NOT EXISTS direct_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NOT NULL,
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id, read_status),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS medals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points_required INT NOT NULL,
    color VARCHAR(20) DEFAULT '#FFD700'
);

CREATE TABLE IF NOT EXISTS user_medals (
    user_id INT NOT NULL,
    medal_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, medal_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medal_id) REFERENCES medals(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('comment', 'message', 'time', 'vote') NOT NULL,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at)
);

-- Insert default medals
INSERT INTO medals (name, description, icon, points_required, color) VALUES
('Newcomer', 'Welcome to Emperor Browser!', '🌟', 0, '#C0C0C0'),
('Regular', 'Active community member', '⭐', 100, '#CD7F32'),
('Contributor', 'Valuable contributions', '🏆', 500, '#C0C0C0'),
('Elite', 'Dedicated user', '💎', 1000, '#FFD700'),
('Legend', 'Emperor Browser Legend', '👑', 5000, '#FF6B6B');

-- Create admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123'
INSERT INTO users (username, email, password_hash, status, admin) VALUES
('admin', 'admin@example.com', '$2y$10$TfwU96ruSNFCC5N5ukVEq.Kb81KNno8MNIgTiS4VSLrG95XjEUH/y', 'active', TRUE);