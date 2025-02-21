<?php
class Usuario
{
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $current_password;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login()
    {
        try {
            $query = "SELECT id, username, password 
                      FROM " . $this->table_name . " 
                      WHERE username = :username";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $this->username);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Primeiro, verifica se é SHA256 (temporário)
                $sha256_hash = hash('sha256', $this->password);
                if ($sha256_hash === $row['password']) {
                    // Atualizar para bcrypt em segundo plano
                    $this->updateToSecureHash($row['id'], $this->password);
                    return $row;
                }
                
                // Depois tenta bcrypt
                if (password_verify($this->password, $row['password'])) {
                    return $row;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return false;
        }
    }

    private function updateToSecureHash($userId, $plainPassword)
    {
        try {
            $secure_hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $query = "UPDATE " . $this->table_name . "
                     SET password = :password
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $secure_hash);
            $stmt->bindParam(":id", $userId);
            $stmt->execute();

            error_log("Senha atualizada para hash seguro para usuário ID: " . $userId);
        } catch (Exception $e) {
            error_log("Erro ao atualizar hash: " . $e->getMessage());
        }
    }

    public function updateCredentials()
    {
        try {
            $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($this->current_password, $row['password'])) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . "
                    SET username = :username,
                        password = :password
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro na atualização de credenciais: " . $e->getMessage());
            return false;
        }
    }

    public function validatePassword($password)
    {
        return strlen($password) >= 8 &&
            preg_match('/[A-Za-z]/', $password) &&
            preg_match('/[0-9]/', $password) &&
            preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password);
    }

    public function validateUsername($username)
    {
        return strlen($username) >= 5 &&
            preg_match('/^[A-Za-z0-9_]+$/', $username);
    }
}
?>