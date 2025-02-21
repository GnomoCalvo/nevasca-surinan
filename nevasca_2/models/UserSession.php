<?php
class UserSession {
    private $conn;
    private $table = 'user_sessions';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * @param int $userId
     * @return string|false
     */
    public function createSession($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            
            // Log para debug
            error_log("Criando sessão para usuário ID: " . $userId);
            error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'não definido'));
            error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'não definido'));
            
            // Limpar sessões antigas do usuário
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['user_id' => $userId]);

            // Criar nova sessão
            $query = "INSERT INTO " . $this->table . " 
                     (user_id, token, user_agent, ip_address) 
                     VALUES (:user_id, :token, :user_agent, :ip_address)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                'user_id' => $userId,
                'token' => $token,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            if ($result) {
                error_log("Sessão criada com sucesso. Token: " . substr($token, 0, 10) . "...");
                return $token;
            } else {
                error_log("Falha ao criar sessão no banco");
                return false;
            }
        } catch (Exception $e) {
            error_log("Erro ao criar sessão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $token
     * @return array|false
     */
    public function validateSession($token) {
        try {
            error_log("Validando sessão com token: " . substr($token, 0, 10) . "...");
            
            $query = "SELECT s.*, u.username 
                     FROM " . $this->table . " s
                     JOIN usuarios u ON u.id = s.user_id
                     WHERE s.token = :token 
                     AND s.last_activity > DATE_SUB(NOW(), INTERVAL 8 HOUR)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['token' => $token]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Resultado da validação: " . ($result ? "sessão válida" : "sessão inválida"));
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao validar sessão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $token
     * @return bool
     */
    public function updateActivity($token) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET last_activity = CURRENT_TIMESTAMP 
                     WHERE token = :token";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute(['token' => $token]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar atividade: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $token
     * @return bool
     */
    public function deleteSession($token) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute(['token' => $token]);
        } catch (Exception $e) {
            error_log("Erro ao deletar sessão: " . $e->getMessage());
            return false;
        }
    }

    public function cleanExpiredSessions() {
        $query = "DELETE FROM " . $this->table . "
                 WHERE last_activity < DATE_SUB(NOW(), INTERVAL 8 HOUR)";
        $this->conn->exec($query);
    }
}
?>