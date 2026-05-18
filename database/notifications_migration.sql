-- ============================================================
-- Notifications System
-- ============================================================
USE agri_coop;

CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,           -- who receives it
    type        VARCHAR(50) NOT NULL,   -- 'doc_pending','doc_approved','doc_rejected','doc_fully_approved'
    title       VARCHAR(255) NOT NULL,
    message     TEXT NOT NULL,
    link        VARCHAR(500),           -- URL to the relevant page
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notif_user_read ON notifications(user_id, is_read);
