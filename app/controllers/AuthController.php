<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController extends Controller {
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function loginPage(): void {
        if (isset($_SESSION['user_id'])) $this->redirect('/dashboard');
        $this->view('auth/login');
    }

    public function login(): void {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Username and password are required.']);
        }

        try {
            $user = $this->userModel->findByUsername($username);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Database error. Please run all SQL migrations first.']);
        }

        if (!$user || !password_verify($password, $user['password'])) {
            $this->json(['success' => false, 'message' => 'Invalid credentials.']);
        }

        if ($user['status'] !== 'active') {
            $this->json(['success' => false, 'message' => 'Account is inactive. Contact administrator.']);
        }

        $this->userModel->updateLastLogin($user['id']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'role_id'   => $user['role_id'],
        ];

        $this->json(['success' => true, 'redirect' => BASE_URL . '/dashboard']);
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('/login');
    }
}

