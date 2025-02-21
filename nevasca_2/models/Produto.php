<?php
class Produto
{
    private $conn;
    private $table_name = "produtos";

    public $id;
    public $tipo;
    public $nome;
    public $tamanho;
    public $categoria;
    public $preco;
    public $quantidade;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function listar($filtroTipo = null, $filtroTamanho = null) {
        $query = "SELECT * FROM " . $this->table_name;
        $conditions = [];
        $params = [];
    
        if (!empty($filtroTipo)) {
            $conditions[] = "tipo = :tipo";
            $params[':tipo'] = $filtroTipo;
        }
    
        if (!empty($filtroTamanho)) {
            $conditions[] = "tamanho = :tamanho";
            $params[':tamanho'] = $filtroTamanho;
        }
    
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $stmt = $this->conn->prepare($query);
    
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
    
        $stmt->execute();
        return $stmt;
    }

    public function criar()
    {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo, nome, tamanho, categoria, preco, quantidade)
                VALUES
                (:tipo, :nome, :tamanho, :categoria, :preco, :quantidade)";

        $stmt = $this->conn->prepare($query);

        $this->sanitizarDados();

        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tamanho", $this->tamanho);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":preco", $this->preco);
        $stmt->bindParam(":quantidade", $this->quantidade);

        return $stmt->execute();
    }

    public function atualizar()
    {
        $query = "UPDATE " . $this->table_name . "
                SET tipo = :tipo,
                    nome = :nome,
                    tamanho = :tamanho,
                    categoria = :categoria,
                    preco = :preco,
                    quantidade = :quantidade
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->sanitizarDados();

        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tamanho", $this->tamanho);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":preco", $this->preco);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function deletar()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function buscarPorId()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $this->id);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function sanitizarDados()
    {
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->nome = $this->nome ? htmlspecialchars(strip_tags($this->nome)) : null;
        $this->tamanho = htmlspecialchars(strip_tags($this->tamanho));
        $this->categoria = $this->categoria ? htmlspecialchars(strip_tags($this->categoria)) : 'NA';
        $this->preco = floatval($this->preco);
        $this->quantidade = intval($this->quantidade);
    }

    public function verificarProdutoExistente()
    {
        $this->nome = mb_strtolower(
            preg_replace(
                '/[áàãâä]/ui',
                'a',
                preg_replace(
                    '/[éèêë]/ui',
                    'e',
                    preg_replace(
                        '/[íìîï]/ui',
                        'i',
                        preg_replace(
                            '/[óòõôö]/ui',
                            'o',
                            preg_replace(
                                '/[úùûü]/ui',
                                'u',
                                $this->nome
                            )
                        )
                    )
                )
            )
        );
        $this->tamanho = mb_strtolower(trim($this->tamanho));
        $this->categoria = mb_strtolower(trim($this->categoria));


        $query = "SELECT id FROM " . $this->table_name . " WHERE ";

        if ($this->tipo == 'Sorvete' || $this->tipo == 'Açaí') {
            $query .= "LOWER(tipo) = LOWER(:tipo) AND LOWER(tamanho) = LOWER(:tamanho)";
        } elseif ($this->tipo == 'Picolé') {
            $query .= "LOWER(tipo) = LOWER(:tipo) AND LOWER(categoria) = LOWER(:categoria)";
        } else {
            $query .= "LOWER(nome) = LOWER(:nome)";
        }

        $stmt = $this->conn->prepare($query);

        if ($this->tipo == 'Sorvete' || $this->tipo == 'Açaí') {
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":tamanho", $this->tamanho);
        } elseif ($this->tipo == 'Picolé') {
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":categoria", $this->categoria);
        } else {
            $stmt->bindParam(":nome", $this->nome);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>