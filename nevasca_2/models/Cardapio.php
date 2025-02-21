<?php
class Cardapio {
    private $conn;
    private $table_name = "cardapio";

    public $id;
    public $tipo;
    public $sabor;
    public $tamanho;
    public $preco;
    public $disponivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo, sabor, tamanho, preco, disponivel)
                VALUES
                (:tipo, :sabor, :tamanho, :preco, :disponivel)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":sabor", $this->sabor);
        $stmt->bindParam(":tamanho", $this->tamanho);
        $stmt->bindParam(":preco", $this->preco);
        $stmt->bindParam(":disponivel", $this->disponivel);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function listar($tipo = null) {
        $query = "SELECT * FROM " . $this->table_name;
        
        if($tipo) {
            $query .= " WHERE tipo = :tipo";
        }
        
        $query .= " ORDER BY tipo, sabor, tamanho";

        $stmt = $this->conn->prepare($query);
        
        if($tipo) {
            $stmt->bindParam(":tipo", $tipo);
        }

        $stmt->execute();
        return $stmt;
    }

    public function atualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET 
                    tipo = :tipo,
                    sabor = :sabor,
                    tamanho = :tamanho,
                    preco = :preco,
                    disponivel = :disponivel
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":sabor", $this->sabor);
        $stmt->bindParam(":tamanho", $this->tamanho);
        $stmt->bindParam(":preco", $this->preco);
        $stmt->bindParam(":disponivel", $this->disponivel);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function deletar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verificarDuplicado($tipo, $sabor, $tamanho, $id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . 
                 " WHERE tipo = :tipo AND sabor = :sabor AND tamanho = :tamanho";
        
        $params = [
            ":tipo" => $tipo,
            ":sabor" => $sabor,
            ":tamanho" => $tamanho
        ];
        
        // Se estiver editando, excluir o item atual da verificação
        if ($id) {
            $query .= " AND id != :id";
            $params[":id"] = $id;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] > 0;
    }

    public function getTamanhosPorTipo($tipo) {
        switch($tipo) {
            case 'Sorvete':
            case 'Açaí':
                return ['2L', '5L', '10L'];
            default:
                return ['NA'];
        }
    }
}
?>