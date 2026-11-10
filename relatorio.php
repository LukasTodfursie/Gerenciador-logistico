<?php
// Relatório de fretes de um dia, entregue como download CSV.
// Uso: relatorio.php?data=AAAA-MM-DD
require_once 'includes/conexao.php';

$data = $_GET['data'] ?? '';

// Confere se a data tem o formato certo e se existe de verdade (ex.: 2026-02-30 não existe)
$formatoOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data);
if (!$formatoOk || !checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4))) {
    header('Location: fretes.php?aviso=data');
    exit;
}

// Uma linha por produto de cada frete do dia
$consulta = $pdo->prepare(
    'SELECT f.id, f.data_frete, f.valor_frete,
            t.nome AS transportador, t.placa,
            o.apelido AS origem, d.apelido AS destino,
            p.nome AS produto, fp.quantidade, p.peso
       FROM fretes f
       JOIN transportadores t ON t.id = f.transportador_id
       JOIN enderecos o ON o.id = f.origem_id
       JOIN enderecos d ON d.id = f.destino_id
       JOIN frete_produtos fp ON fp.frete_id = f.id
       JOIN produtos p ON p.id = fp.produto_id
      WHERE f.data_frete = ?
      ORDER BY f.id, fp.id'
);
$consulta->execute([$data]);
$linhas = $consulta->fetchAll();

if (!$linhas) {
    header('Location: fretes.php?aviso=vazio&data=' . $data);
    exit;
}

// Cabeçalhos que fazem o navegador BAIXAR o arquivo em vez de mostrá-lo
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="relatorio_fretes_' . $data . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF"); // marca que o arquivo é UTF-8, para o Excel mostrar os acentos certos

// Escreve uma linha no CSV, separando as colunas por ponto e vírgula (padrão do Excel em português)
function linhaCsv($saida, $campos)
{
    fputcsv($saida, $campos, ';', '"', '\\');
}

linhaCsv($saida, ['Frete', 'Data', 'Transportador', 'Placa', 'Origem', 'Destino',
    'Produto', 'Quantidade', 'Peso total (kg)', 'Valor do frete (R$)']);

$pesoGeral = 0;
$valorGeral = 0;
$fretesContados = [];

foreach ($linhas as $l) {
    $pesoLinha = $l['peso'] * $l['quantidade'];
    $pesoGeral += $pesoLinha;

    // O valor do frete aparece só na primeira linha de cada frete, para não somar em duplicidade
    $primeiraLinha = !isset($fretesContados[$l['id']]);
    if ($primeiraLinha) {
        $fretesContados[$l['id']] = true;
        $valorGeral += $l['valor_frete'];
    }

    linhaCsv($saida, [
        $l['id'],
        date('d/m/Y', strtotime($l['data_frete'])),
        $l['transportador'],
        $l['placa'],
        $l['origem'],
        $l['destino'],
        $l['produto'],
        $l['quantidade'],
        number_format($pesoLinha, 3, ',', ''),
        $primeiraLinha ? number_format($l['valor_frete'], 2, ',', '') : '',
    ]);
}

linhaCsv($saida, []); // linha em branco
linhaCsv($saida, ['TOTAL', count($fretesContados) . ' frete(s)', '', '', '', '', '', '',
    number_format($pesoGeral, 3, ',', ''), number_format($valorGeral, 2, ',', '')]);

fclose($saida);
exit;