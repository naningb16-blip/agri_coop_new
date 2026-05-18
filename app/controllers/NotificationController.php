<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/NotificationHelper.php';

class NotificationController extends Controller {
    private NotificationHelper $notif;

    public function __construct() {
        parent::__construct();
        $this->notif = new NotificationHelper();
    }

    // GET /notifications &#8369; returns unread count + recent list (JSON)
    public function fetch(): void {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $this->json([
            'unread' => $this->notif->unreadCount($userId),
            'items'  => $this->notif->getForUser($userId, 15),
        ]);
    }

    // POST /notifications/read-all
    public function readAll(): void {
        $this->requireAuth();
        $this->notif->markAllRead($_SESSION['user_id']);
        $this->json(['success' => true]);
    }

    // POST /notifications/read
    public function read(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $this->notif->markRead($id, $_SESSION['user_id']);
        $this->json(['success' => true]);
    }
}

