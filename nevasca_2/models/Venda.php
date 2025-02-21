<?php
class Venda
{
    private $conn;
    private $table_name = "vendas";

    public $id;
    public $data_venda;
    public $valor_total;
    public $pagamentos = [];
    public $itens = [];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function criar()
    {
        $this->conn->beginTransaction();

        try {
            // Inserir venda
            $query = "INSERT INTO " . $this->table_name . "
                    (valor_total, data_venda)
                    VALUES
                    (:valor_total, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":valor_total", $this->valor_total);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao criar venda");
            }

            $this->id = $this->conn->lastInsertId();

            // Inserir pagamentos
            foreach ($this->pagamentos as $pagamento) {
                $queryPagamento = "INSERT INTO pagamentos_venda
                                (venda_id, forma_pagamento, valor)
                                VALUES
                                (:venda_id, :forma_pagamento, :valor)";

                $stmtPagamento = $this->conn->prepare($queryPagamento);
                $stmtPagamento->bindParam(":venda_id", $this->id);
                $stmtPagamento->bindParam(":forma_pagamento", $pagamento['forma']);
                $stmtPagamento->bindParam(":valor", $pagamento['valor']);

                if (!$stmtPagamento->execute()) {
                    throw new Exception("Erro ao registrar pagamento");
                }
            }

            // Inserir itens da venda
            foreach ($this->itens as $item) {
                // Primeiro verifica se há estoque suficiente
                $queryEstoque = "SELECT quantidade FROM produtos WHERE id = :produto_id";
                $stmtEstoque = $this->conn->prepare($queryEstoque);
                $stmtEstoque->bindParam(":produto_id", $item['produto_id']);
                $stmtEstoque->execute();

                $estoque = $stmtEstoque->fetch(PDO::FETCH_ASSOC);
                if (!$estoque || $estoque['quantidade'] < $item['quantidade']) {
                    throw new Exception("Estoque insuficiente para o produto ID: " . $item['produto_id']);
                }

                // Insere o item da venda
                $queryItem = "INSERT INTO itens_venda
                        (venda_id, produto_id, quantidade, preco_unitario)
                        VALUES
                        (:venda_id, :produto_id, :quantidade, :preco_unitario)";

                $stmtItem = $this->conn->prepare($queryItem);

                $stmtItem->bindParam(":venda_id", $this->id);
                $stmtItem->bindParam(":produto_id", $item['produto_id']);
                $stmtItem->bindParam(":quantidade", $item['quantidade']);
                $stmtItem->bindParam(":preco_unitario", $item['preco_unitario']);

                if (!$stmtItem->execute()) {
                    throw new Exception("Erro ao criar item da venda");
                }

                // Atualiza o estoque
                $queryUpdateEstoque = "UPDATE produtos 
                                     SET quantidade = quantidade - :quantidade
                                     WHERE id = :produto_id";

                $stmtUpdateEstoque = $this->conn->prepare($queryUpdateEstoque);
                $stmtUpdateEstoque->bindParam(":quantidade", $item['quantidade']);
                $stmtUpdateEstoque->bindParam(":produto_id", $item['produto_id']);

                if (!$stmtUpdateEstoque->execute()) {
                    throw new Exception("Erro ao atualizar estoque");
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function atualizarEstoque($produto_id, $quantidade)
    {
        $query = "UPDATE produtos 
                SET quantidade = quantidade - :quantidade
                WHERE id = :id AND quantidade >= :quantidade";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":quantidade", $quantidade);
        $stmt->bindParam(":id", $produto_id);

        if (!$stmt->execute() || $stmt->rowCount() == 0) {
            throw new Exception("Quantidade insuficiente em estoque");
        }
    }

    public function listar($dataInicio = null, $dataFim = null)
    {
        $query = "SELECT 
                    v.*, 
                    GROUP_CONCAT(DISTINCT CONCAT(p.nome, '|', iv.quantidade, '|', iv.preco_unitario) SEPARATOR ';;') as itens,
                    GROUP_CONCAT(DISTINCT CONCAT(pv.forma_pagamento, '|', pv.valor) SEPARATOR ';;') as pagamentos
                 FROM " . $this->table_name . " v
                 LEFT JOIN itens_venda iv ON v.id = iv.venda_id
                 LEFT JOIN produtos p ON iv.produto_id = p.id
                 LEFT JOIN pagamentos_venda pv ON v.id = pv.venda_id";

        if ($dataInicio && $dataFim) {
            $query .= " WHERE DATE(v.data_venda) BETWEEN :data_inicio AND :data_fim";
        }

        $query .= " GROUP BY v.id ORDER BY v.data_venda DESC";

        $stmt = $this->conn->prepare($query);

        if ($dataInicio && $dataFim) {
            $stmt->bindParam(":data_inicio", $dataInicio);
            $stmt->bindParam(":data_fim", $dataFim);
        }

        $stmt->execute();
        return $stmt;
    }

    public function deletar()
    {
        $this->conn->beginTransaction();

        try {
            // Primeiro, buscar os itens da venda para restaurar o estoque
            $queryItens = "SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = :id";
            $stmtItens = $this->conn->prepare($queryItens);
            $stmtItens->bindParam(":id", $this->id);
            $stmtItens->execute();

            // Restaurar o estoque para cada item
            while ($item = $stmtItens->fetch(PDO::FETCH_ASSOC)) {
                $queryUpdateEstoque = "UPDATE produtos 
                                 SET quantidade = quantidade + :quantidade
                                 WHERE id = :produto_id";

                $stmtUpdateEstoque = $this->conn->prepare($queryUpdateEstoque);
                $stmtUpdateEstoque->bindParam(":quantidade", $item['quantidade']);
                $stmtUpdateEstoque->bindParam(":produto_id", $item['produto_id']);

                if (!$stmtUpdateEstoque->execute()) {
                    throw new Exception("Erro ao restaurar estoque do produto ID: " . $item['produto_id']);
                }
            }

            // Deletar os pagamentos relacionados
            $query = "DELETE FROM pagamentos_venda WHERE venda_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao deletar pagamentos da venda");
            }

            // Deletar os itens relacionados
            $query = "DELETE FROM itens_venda WHERE venda_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao deletar itens da venda");
            }

            // Por fim, deletar a venda
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao deletar venda");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function buscarVendasDiarias()
    {
        try {
            $query = "SELECT 
                    COUNT(DISTINCT v.id) as total_vendas,
                    (SELECT SUM(valor_total) FROM vendas WHERE DATE(data_venda) = CURDATE()) as valor_total,
                    COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'dinheiro' THEN v.id END) as qtd_dinheiro,
                    COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'pix' THEN v.id END) as qtd_pix,
                    COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'debito' THEN v.id END) as qtd_debito,
                    COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'credito' THEN v.id END) as qtd_credito,
                    SUM(CASE WHEN pv.forma_pagamento = 'dinheiro' THEN pv.valor ELSE 0 END) as valor_dinheiro,
                    SUM(CASE WHEN pv.forma_pagamento = 'pix' THEN pv.valor ELSE 0 END) as valor_pix,
                    SUM(CASE WHEN pv.forma_pagamento = 'debito' THEN pv.valor ELSE 0 END) as valor_debito,
                    SUM(CASE WHEN pv.forma_pagamento = 'credito' THEN pv.valor ELSE 0 END) as valor_credito
                 FROM vendas v
                 LEFT JOIN pagamentos_venda pv ON v.id = pv.venda_id
                 WHERE DATE(v.data_venda) = CURDATE()";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar vendas diárias: " . $e->getMessage());
        }
    }
}
?>