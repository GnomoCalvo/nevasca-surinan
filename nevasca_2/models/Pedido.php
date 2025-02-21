<?php
class Pedido
{
    private $conn;
    private $table_name = "pedidos";

    public $id;
    public $data_pedido;
    public $data_vencimento;
    public $valor_total;
    public $itens = [];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function criar()
    {
        $this->conn->beginTransaction();

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (data_vencimento, valor_total)
                    VALUES
                    (:data_vencimento, :valor_total)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":data_vencimento", $this->data_vencimento);
            $stmt->bindParam(":valor_total", $this->valor_total);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao criar pedido");
            }

            $this->id = $this->conn->lastInsertId();

            foreach ($this->itens as $item) {
                $query = "INSERT INTO itens_pedido
                        (pedido_id, nome, tamanho, quantidade, preco_unitario)
                        VALUES
                        (:pedido_id, :nome, :tamanho, :quantidade, :preco_unitario)";

                $stmt = $this->conn->prepare($query);

                $stmt->bindParam(":pedido_id", $this->id);
                $stmt->bindParam(":nome", $item['nome']);
                $stmt->bindParam(":tamanho", $item['tamanho']);
                $stmt->bindParam(":quantidade", $item['quantidade']);
                $stmt->bindParam(":preco_unitario", $item['preco_unitario']);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao criar item do pedido");
                }

                // Atualizar estoque
                $this->atualizarEstoque($item);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function atualizarEstoque($item)
    {
        $produtoModel = new Produto($this->conn);

        // Determinar tipo e categoria baseado no nome do item
        $tipoCategoria = $this->determinarTipoCategoria($item['nome']);

        // Verificar se produto existe
        $query = "SELECT id, quantidade, preco FROM produtos WHERE ";

        if ($tipoCategoria['tipo'] === 'Sorvete' || $tipoCategoria['tipo'] === 'Açaí') {
            $query .= "tipo = :tipo AND tamanho = :tamanho";
        } elseif ($tipoCategoria['tipo'] === 'Picolé') {
            $query .= "tipo = :tipo AND categoria = :categoria";
        } else {
            $query .= "nome = :nome";
        }

        $stmt = $this->conn->prepare($query);

        if ($tipoCategoria['tipo'] === 'Sorvete' || $tipoCategoria['tipo'] === 'Açaí') {
            $stmt->bindParam(":tipo", $tipoCategoria['tipo']);
            $stmt->bindParam(":tamanho", $item['tamanho']);
        } elseif ($tipoCategoria['tipo'] === 'Picolé') {
            $stmt->bindParam(":tipo", $tipoCategoria['tipo']);
            $stmt->bindParam(":categoria", $tipoCategoria['categoria']);
        } else {
            $stmt->bindParam(":nome", $item['nome']);
        }

        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            // Atualizar produto existente
            $query = "UPDATE produtos 
                    SET quantidade = quantidade + :quantidade,
                        preco = :preco
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":quantidade", $item['quantidade']);
            $stmt->bindParam(":preco", $item['preco_unitario']);
            $stmt->bindParam(":id", $produto['id']);
        } else {
            // Criar novo produto
            $query = "INSERT INTO produtos 
                    (tipo, nome, tamanho, categoria, preco, quantidade)
                    VALUES 
                    (:tipo, :nome, :tamanho, :categoria, :preco, :quantidade)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tipo", $tipoCategoria['tipo']);
            $stmt->bindParam(":nome", $item['nome']);
            $stmt->bindParam(":tamanho", $item['tamanho']);
            $stmt->bindParam(":categoria", $tipoCategoria['categoria']);
            $stmt->bindParam(":preco", $item['preco_unitario']);
            $stmt->bindParam(":quantidade", $item['quantidade']);
        }

        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar estoque");
        }
    }

    private function determinarTipoCategoria($nome)
    {
        $nome = strtolower(trim($nome));

        if (strpos($nome, 'sorvete') !== false) {
            return ['tipo' => 'Sorvete', 'categoria' => 'NA'];
        } elseif (strpos($nome, 'açaí') !== false) {
            return ['tipo' => 'Açaí', 'categoria' => 'NA'];
        } elseif (strpos($nome, 'picolé') !== false) {
            if (strpos($nome, 'premium') !== false) {
                return ['tipo' => 'Picolé', 'categoria' => 'Premium'];
            } elseif (strpos($nome, 'recheado') !== false) {
                return ['tipo' => 'Picolé', 'categoria' => 'Recheado'];
            } elseif (strpos($nome, 'paleta') !== false) {
                return ['tipo' => 'Picolé', 'categoria' => 'Paleta'];
            } else {
                return ['tipo' => 'Picolé', 'categoria' => 'Comum'];
            }
        } else {
            return ['tipo' => 'Adicionais', 'categoria' => 'NA'];
        }
    }

    public function listar($dataInicio = null, $dataFim = null)
    {
        $query = "SELECT p.*, 
              GROUP_CONCAT(
                  CONCAT(ip.nome, '|', ip.tamanho, '|', ip.quantidade, '|', ip.preco_unitario)
                  SEPARATOR ';;'
              ) as itens
              FROM " . $this->table_name . " p
              LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id";

        if ($dataInicio && $dataFim) {
            $query .= " WHERE DATE(p.data_pedido) BETWEEN :data_inicio AND :data_fim";
        }

        $query .= " GROUP BY p.id ORDER BY p.data_pedido DESC";

        $stmt = $this->conn->prepare($query);

        if ($dataInicio && $dataFim) {
            $stmt->bindParam(":data_inicio", $dataInicio);
            $stmt->bindParam(":data_fim", $dataFim);
        }

        $stmt->execute();
        return $stmt;
    }

    public function buscarPedidosVencendo()
    {
        $query = "SELECT * FROM " . $this->table_name . "
            WHERE data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
            AND (status IS NULL OR status = 'pendente')  -- Adicionar filtro de status
            ORDER BY data_vencimento";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function atualizarStatus($id, $status)
    {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET status = :status
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":status", $status);

            return $stmt->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deletar()
    {
        $this->conn->beginTransaction();

        try {
            // Primeiro, buscar os itens do pedido
            $query = "SELECT nome, tamanho, quantidade FROM itens_pedido WHERE pedido_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada item, subtrair a quantidade do estoque
            foreach ($itens as $item) {
                // Determinar tipo e categoria baseado no nome do item
                $tipoCategoria = $this->determinarTipoCategoria($item['nome']);

                // Construir query de atualização
                $query = "UPDATE produtos SET quantidade = quantidade - :quantidade WHERE ";

                if ($tipoCategoria['tipo'] === 'Sorvete' || $tipoCategoria['tipo'] === 'Açaí') {
                    $query .= "tipo = :tipo AND tamanho = :tamanho";
                } elseif ($tipoCategoria['tipo'] === 'Picolé') {
                    $query .= "tipo = :tipo AND categoria = :categoria";
                } else {
                    $query .= "nome = :nome";
                }

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":quantidade", $item['quantidade']);

                if ($tipoCategoria['tipo'] === 'Sorvete' || $tipoCategoria['tipo'] === 'Açaí') {
                    $stmt->bindParam(":tipo", $tipoCategoria['tipo']);
                    $stmt->bindParam(":tamanho", $item['tamanho']);
                } elseif ($tipoCategoria['tipo'] === 'Picolé') {
                    $stmt->bindParam(":tipo", $tipoCategoria['tipo']);
                    $stmt->bindParam(":categoria", $tipoCategoria['categoria']);
                } else {
                    $stmt->bindParam(":nome", $item['nome']);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar estoque para o item " . $item['nome']);
                }
            }

            // Depois deletar os itens do pedido
            $query = "DELETE FROM itens_pedido WHERE pedido_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao deletar itens do pedido");
            }

            // Por fim, deletar o pedido
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao deletar pedido");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>