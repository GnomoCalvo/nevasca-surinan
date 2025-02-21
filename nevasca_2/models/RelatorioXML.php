<?php
class RelatorioXML
{
    private $relatorio;

    public function __construct($relatorio)
    {
        $this->relatorio = $relatorio;
    }

    public function gerarXMLFinanceiro($dataInicio, $dataFim)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><relatorio_financeiro></relatorio_financeiro>');
        $xml->addAttribute('data_inicio', $dataInicio);
        $xml->addAttribute('data_fim', $dataFim);

        // Adicionar vendas
        $vendasNode = $xml->addChild('vendas');
        $resultVendas = $this->relatorio->vendasPorPeriodo($dataInicio, $dataFim);

        // Totalizadores de formas de pagamento
        $totais = [
            'dinheiro' => 0,
            'pix' => 0,
            'debito' => 0,
            'credito' => 0
        ];

        while ($venda = $resultVendas->fetch(PDO::FETCH_ASSOC)) {
            $vendaNode = $vendasNode->addChild('venda');
            $vendaNode->addChild('data', $venda['data']);
            $vendaNode->addChild('total_vendas', $venda['total_vendas']);
            $vendaNode->addChild('valor_total', $venda['valor_total']);

            // Adicionar formas de pagamento com valores
            $formasNode = $vendaNode->addChild('formas_pagamento');

            // Dinheiro
            if ($venda['total_dinheiro'] > 0) {
                $formaNode = $formasNode->addChild('forma');
                $formaNode->addChild('tipo', 'dinheiro');
                $formaNode->addChild('valor', number_format($venda['total_dinheiro'], 2, '.', ''));
                $totais['dinheiro'] += $venda['total_dinheiro'];
            }

            // PIX
            if ($venda['total_pix'] > 0) {
                $formaNode = $formasNode->addChild('forma');
                $formaNode->addChild('tipo', 'pix');
                $formaNode->addChild('valor', number_format($venda['total_pix'], 2, '.', ''));
                $totais['pix'] += $venda['total_pix'];
            }

            // Débito
            if ($venda['total_debito'] > 0) {
                $formaNode = $formasNode->addChild('forma');
                $formaNode->addChild('tipo', 'debito');
                $formaNode->addChild('valor', number_format($venda['total_debito'], 2, '.', ''));
                $totais['debito'] += $venda['total_debito'];
            }

            // Crédito
            if ($venda['total_credito'] > 0) {
                $formaNode = $formasNode->addChild('forma');
                $formaNode->addChild('tipo', 'credito');
                $formaNode->addChild('valor', number_format($venda['total_credito'], 2, '.', ''));
                $totais['credito'] += $venda['total_credito'];
            }
        }

        // Adicionar totalizador de formas de pagamento
        $totaisNode = $xml->addChild('totais_formas_pagamento');
        foreach ($totais as $forma => $valor) {
            $totalNode = $totaisNode->addChild('forma');
            $totalNode->addChild('tipo', $forma);
            $totalNode->addChild('valor', number_format($valor, 2, '.', ''));
        }

        // Adicionar despesas
        $despesasNode = $xml->addChild('despesas');
        $resultDespesas = $this->relatorio->despesasPorPeriodo($dataInicio, $dataFim);
        while ($despesa = $resultDespesas->fetch(PDO::FETCH_ASSOC)) {
            $despesaNode = $despesasNode->addChild('despesa');
            $despesaNode->addChild('data', $despesa['data']);
            $despesaNode->addChild('nome', $despesa['nome']);
            $despesaNode->addChild('valor', $despesa['valor']);
        }

        // Adicionar balanço
        $balanco = $this->relatorio->balancoPeriodo($dataInicio, $dataFim);
        $balancoNode = $xml->addChild('balanco');
        $balancoNode->addChild('receitas', $balanco['receitas']);
        $balancoNode->addChild('despesas', $balanco['despesas']);
        $balancoNode->addChild('lucro', $balanco['lucro']);

        return $xml->asXML();
    }
}
?>