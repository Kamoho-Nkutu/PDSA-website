<?php
class User {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function register($name, $email, $phone, $password, $role = 'user') {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        // Check if user exists
        $existing = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            throw new Exception("Email already registered");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);

        // Insert new user
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO users (name, email, phone, password, role, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())",
                [$name, $email, $phone, $hashedPassword, $role]
            );

            $userId = $this->db->lastInsertId();
            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function login($email, $password) {
        $user = $this->db->fetch(
            "SELECT id, name, email, password, role FROM users WHERE email = ?", 
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Store user in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return true;
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    public function isAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
    }

    public function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public function updateProfile($userId, $data) {
        // Validate and update user profile
        $allowedFields = ['name', 'phone', 'address', 'postcode'];
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        return $this->db->query($sql, $params)->rowCount() > 0;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        return $this->db->query(
            "UPDATE users SET password = ? WHERE id = ?", 
            [$hashedPassword, $userId]
        )->rowCount() > 0;
    }
}
?>