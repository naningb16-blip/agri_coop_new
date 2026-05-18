<?php
class NotificationHelper {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Send to all users with a specific role
    public function notifyRole(string $role, string $type, string $title, string $message, string $link = ''): void {
        $users = $this->db->fetchAll(
            "SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name=? AND u.status='active'",
            [$role], 's'
        );
        foreach ($users as $user) {
            $this->notifyUser($user['id'], $type, $title, $message, $link);
        }
    }

    // Send to a specific user
    public function notifyUser(int $userId, string $type, string $title, string $message, string $link = ''): void {
        $this->db->insert(
            "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)",
            [$userId, $type, $title, $message, $link], 'issss'
        );
    }

    // Get unread count for a user
    public function unreadCount(int $userId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND is_read=0",
            [$userId], 'i'
        );
        return (int)($row['cnt'] ?? 0);
    }

    // Get notifications for a user
    public function getForUser(int $userId, int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit], 'ii'
        );
    }

    // Mark all read for a user
    public function markAllRead(int $userId): void {
        $this->db->query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$userId], 'i');
    }

    // Mark one read
    public function markRead(int $id, int $userId): void {
        $this->db->query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, $userId], 'ii');
    }
}
