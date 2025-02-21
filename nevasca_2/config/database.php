<?php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Obtém a URL do banco de dados a partir das variáveis de ambiente
            $db_url = getenv("DATABASE_URL");
            if (!$db_url) {
                throw new Exception("Erro: variável DATABASE_URL não encontrada.");
            }

            // Faz o parsing da URL do PostgreSQL
            $components = parse_url($db_url);
            $host = $components["host"];
            $port = $components["port"];
            $user = $components["user"];
            $password = $components["pass"];
            $dbname = ltrim($components["path"], "/");

            // Cria a conexão com PostgreSQL
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'utf8'");
        } catch (Exception $e) {
            echo "Erro na conexão: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
