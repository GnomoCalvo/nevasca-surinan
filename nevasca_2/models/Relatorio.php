<?php
class Relatorio
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function vendasPorPeriodo($dataInicio, $dataFim)
    {
        $query = "SELECT 
                DATE(v.data_venda) as data,
                COUNT(DISTINCT v.id) as total_vendas,
                (SELECT SUM(valor_total) FROM vendas 
                 WHERE DATE(data_venda) BETWEEN :data_inicio AND :data_fim) as valor_total,
                GROUP_CONCAT(DISTINCT pv.forma_pagamento) as formas_pagamento,
                COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'dinheiro' THEN v.id END) as qtd_dinheiro,
                COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'pix' THEN v.id END) as qtd_pix,
                COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'debito' THEN v.id END) as qtd_debito,
                COUNT(DISTINCT CASE WHEN pv.forma_pagamento = 'credito' THEN v.id END) as qtd_credito,
                SUM(CASE WHEN pv.forma_pagamento = 'dinheiro' THEN pv.valor ELSE 0 END) as total_dinheiro,
                SUM(CASE WHEN pv.forma_pagamento = 'pix' THEN pv.valor ELSE 0 END) as total_pix,
                SUM(CASE WHEN pv.forma_pagamento = 'debito' THEN pv.valor ELSE 0 END) as total_debito,
                SUM(CASE WHEN pv.forma_pagamento = 'credito' THEN pv.valor ELSE 0 END) as total_credito
            FROM vendas v
            LEFT JOIN pagamentos_venda pv ON v.id = pv.venda_id
            WHERE DATE(v.data_venda) BETWEEN :data_inicio AND :data_fim
            GROUP BY DATE(v.data_venda)
            ORDER BY data_venda DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":data_inicio", $dataInicio);
        $stmt->bindParam(":data_fim", $dataFim);
        $stmt->execute();
        return $stmt;
    }

    public function produtosMaisVendidos($dataInicio, $dataFim, $limite = 10)
    {
        $query = "SELECT 
                    p.tipo,
                    p.nome,
                    p.tamanho,
                    SUM(iv.quantidade) as quantidade_vendida,
                    SUM(iv.quantidade * iv.preco_unitario) as valor_total
                FROM produtos p
                JOIN itens_venda iv ON p.id = iv.produto_id
                JOIN vendas v ON iv.venda_id = v.id
                WHERE DATE(v.data_venda) BETWEEN :data_inicio AND :data_fim
                GROUP BY p.id
                ORDER BY quantidade_vendida DESC
                LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":data_inicio", $dataInicio);
        $stmt->bindParam(":data_fim", $dataFim);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function estoqueAtual()
    {
        $query = "SELECT 
                    tipo,
                    nome,
                    tamanho,
                    categoria,
                    quantidade,
                    preco
                FROM produtos
                WHERE quantidade <= 10
                ORDER BY quantidade ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function despesasPorPeriodo($dataInicio, $dataFim)
    {
        $query = "SELECT 
                    DATE(data_despesa) as data,
                    nome,
                    valor
                FROM despesas
                WHERE DATE(data_despesa) BETWEEN :data_inicio AND :data_fim
                ORDER BY data_despesa DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":data_inicio", $dataInicio);
        $stmt->bindParam(":data_fim", $dataFim);
        $stmt->execute();
        return $stmt;
    }

    public function pedidosVencendo()
    {
        $query = "SELECT 
                    p.*,
                    GROUP_CONCAT(CONCAT(ip.nome, '|', ip.quantidade, '|', ip.preco_unitario) 
                               SEPARATOR ';;') as itens
                FROM pedidos p
                LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
                WHERE data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
                GROUP BY p.id
                ORDER BY data_vencimento";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function balancoPeriodo($dataInicio, $dataFim)
    {
        // Receitas (vendas)
        $queryReceitas = "SELECT COALESCE(SUM(valor_total), 0) as total_receitas
                         FROM vendas
                         WHERE DATE(data_venda) BETWEEN :data_inicio AND :data_fim";

        $stmtReceitas = $this->conn->prepare($queryReceitas);
        $stmtReceitas->bindParam(":data_inicio", $dataInicio);
        $stmtReceitas->bindParam(":data_fim", $dataFim);
        $stmtReceitas->execute();
        $receitas = $stmtReceitas->fetch(PDO::FETCH_ASSOC)['total_receitas'];

        // Despesas (pedidos + despesas gerais)
        $queryDespesas = "SELECT 
                            (SELECT COALESCE(SUM(valor_total), 0)
                             FROM pedidos
                             WHERE DATE(data_pedido) BETWEEN :data_inicio AND :data_fim)
                            +
                            (SELECT COALESCE(SUM(valor), 0)
                             FROM despesas
                             WHERE DATE(data_despesa) BETWEEN :data_inicio AND :data_fim)
                         as total_despesas";

        $stmtDespesas = $this->conn->prepare($queryDespesas);
        $stmtDespesas->bindParam(":data_inicio", $dataInicio);
        $stmtDespesas->bindParam(":data_fim", $dataFim);
        $stmtDespesas->execute();
        $despesas = $stmtDespesas->fetch(PDO::FETCH_ASSOC)['total_despesas'];

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'lucro' => $receitas - $despesas
        ];
    }
}
?>