<?php
require_once 'core/Model.php';

class User extends Model {
    protected $table = 'users';
    
    public function findById($id) {
        return parent::findById($id);
    }
    
    public function updateUser($id, $data) {
        return parent::update($id, $data);
    }
    
    public function updateLastLogin($userId) {
        return $this->updateUser($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    public function getTechnicians() {
        return $this->findWhere("user_group IN ('Admin', 'Technician') AND is_active = 1");
    }

    public function deleteUser($id) {
        return parent::delete($id);
    }
    
    public function countUsers() {
        return parent::count();
    }
    
    public function findByUsername($username) {
        return $this->findOneWhere('username = ? AND is_active = 1', [$username]);
    }
    
    public function findByEmail($email) {
        return $this->findOneWhere('email = ?', [$email]);
    }
    
    public function findByResetToken($token) {
        // First, find the user with the token regardless of expiration
        $user = $this->findOneWhere('reset_token = ?', [$token]);
        
        if (!$user) {
            return null; // No user found with this token
        }
        
        // Check if the token has expired
        $now = date('Y-m-d H:i:s');
        if ($user['reset_expires'] && $user['reset_expires'] > $now) {
            return $user; // Token is valid and not expired
        }
        
        return null; // Token has expired
    }
    
    public function createUser($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updatePassword($userId, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->updateUser($userId, ['password' => $hashedPassword]);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function recordLogin($userId, $ip) {
        $stmt = $this->db->prepare("
            INSERT INTO user_logins (user_id, ip_address, login_time) 
            VALUES (?, ?, NOW())
        ");
        return $stmt->execute([$userId, $ip]);
    }
    
    public function hasRecentLogin($userId, $ip, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM user_logins 
            WHERE user_id = ? AND ip_address = ? AND login_time > DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$userId, $ip, $days]);
        return $stmt->fetch()['count'] > 0;
    }
    
    public function store2FACode($userId, $code) {
        $codeHash = password_hash((string) $code, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            INSERT INTO two_factor_codes (user_id, code, attempts, last_attempt_at, expires_at)
            VALUES (?, ?, 0, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ON DUPLICATE KEY UPDATE code = ?, attempts = 0, last_attempt_at = NULL,
                expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        ");
        return $stmt->execute([$userId, $codeHash, $codeHash]);
    }
    
    public function verify2FACode($userId, $code) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT code, attempts, expires_at
                FROM two_factor_codes
                WHERE user_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$userId]);
            $record = $stmt->fetch();

            if (!$record || strtotime($record['expires_at']) <= time() || (int) $record['attempts'] >= 5) {
                $this->db->prepare("DELETE FROM two_factor_codes WHERE user_id = ?")->execute([$userId]);
                $this->db->commit();
                return false;
            }

            if (password_verify((string) $code, (string) $record['code'])) {
                $this->db->prepare("DELETE FROM two_factor_codes WHERE user_id = ?")->execute([$userId]);
                $this->db->commit();
                return true;
            }

            $attempts = (int) $record['attempts'] + 1;
            if ($attempts >= 5) {
                $this->db->prepare("DELETE FROM two_factor_codes WHERE user_id = ?")->execute([$userId]);
            } else {
                $this->db->prepare("
                    UPDATE two_factor_codes
                    SET attempts = ?, last_attempt_at = NOW()
                    WHERE user_id = ?
                ")->execute([$attempts, $userId]);
            }
            $this->db->commit();
            return false;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getLoginAttempts($ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM login_attempts 
            WHERE ip_address = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip]);
        return $stmt->fetch()['count'];
    }
    
    public function recordLoginAttempt($ip, $username = null, $success = false) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, username, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$ip, $username, $success ? 1 : 0]);
    }
    
    public function cleanupOldLogins() {
        // Clean up login records older than 30 days
        $this->db->query("DELETE FROM user_logins WHERE login_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        // Clean up old login attempts
        $this->db->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        
        // Clean up expired 2FA codes
        $this->db->query("DELETE FROM two_factor_codes WHERE expires_at < NOW()");
    }
    
    public function isUserActive($userId) {
        $user = $this->findOneWhere('id = ?', [$userId]);
        return $user && $user['is_active'] == 1;
    }
    
    public function getDisplayName($user) {
        if (is_array($user)) {
            return !empty($user['name']) ? $user['name'] : $user['username'];
        }
        return !empty($user->name) ? $user->name : $user->username;
    }
    
    public function getAllUsers($limit = null, $offset = 0) {
        $sql = "SELECT id, username, name, email, user_group, is_active, created_at, last_login FROM users";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
