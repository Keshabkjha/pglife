-- Notifications table for real-time user notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('message','booking','interest','offer','review') NOT NULL DEFAULT 'message',
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    link VARCHAR(500) NULL,
    is_read TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read, created_at DESC),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
