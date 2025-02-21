<?php
class Despesa {
    private $conn;
    private $table_name = "despesas";

    public $id;
    public $nome;
    public $valor;
    public $data_despesa;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->table_name . "
                (nome, valor)
                VALUES
                (:nome, :valor)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->valor = floatval($this->valor);
        
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":valor", $this->valor);
        
        return $stmt->execute();
    }

    public function listar($dataInicio = null, $dataFim = null) {
        $query = "SELECT * FROM " . $this->table_name;
        
        if ($dataInicio && $dataFim) {
            $query .= " WHERE DATE(data_despesa) BETWEEN :data_inicio AND :data_fim";
        }
        
        $query .= " ORDER BY data_despesa DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($dataInicio && $dataFim) {
            $stmt->bindParam(":data_inicio", $dataInicio);
            $stmt->bindParam(":data_fim", $dataFim);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function deletar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>