<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';

class UserController extends Controller {
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Access denied.'];
            $this->redirect('/dashboard');
        }
        $this->userModel = new UserModel();
    }

    public function index(): void {
        $users = $this->userModel->getAllWithRoles();
        $roles = $this->userModel->getAllRoles();
        $this->view('users/index', compact('users', 'roles'));
    }

    public function save(): void {
        $id       = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        if (!$username || !$email || !$fullName || !$roleId) {
            $this->json(['success' => false, 'message' => 'All fields are required.']);
        }

        if ($id) {
            // Edit existing user
            $existing = $this->userModel->findById($id);
            if (!$existing) $this->json(['success' => false, 'message' => 'User not found.']);

            // Check unique username/email excluding self
            $byUsername = $this->userModel->findByUsername($username);
            if ($byUsername && $byUsername['id'] != $id) {
                $this->json(['success' => false, 'message' => 'Username already taken.']);
            }
            $byEmail = $this->userModel->findByEmail($email);
            if ($byEmail && $byEmail['id'] != $id) {
                $this->json(['success' => false, 'message' => 'Email already in use.']);
            }

            $data = [
                'username'  => $username,
                'email'     => $email,
                'full_name' => $fullName,
                'role_id'   => $roleId,
                'status'    => $status,
            ];
            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $this->userModel->updateUser($id, $data);
            $this->json(['success' => true, 'message' => 'User updated.']);
        } else {
            // Create new user
            if (empty($password)) $this->json(['success' => false, 'message' => 'Password is required for new users.']);

            if ($this->userModel->findByUsername($username)) {
                $this->json(['success' => false, 'message' => 'Username already taken.']);
            }
            if ($this->userModel->findByEmail($email)) {
                $this->json(['success' => false, 'message' => 'Email already in use.']);
            }

            $this->userModel->createUser([
                'username'  => $username,
                'email'     => $email,
                'full_name' => $fullName,
                'role_id'   => $roleId,
                'status'    => $status,
                'password'  => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $this->json(['success' => true, 'message' => 'User created.']);
        }
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $this->json(['success' => false, 'message' => 'Cannot delete your own account.']);
        }
        $this->userModel->deleteUser($id);
        $this->json(['success' => true, 'message' => 'User deleted.']);
    }
}
