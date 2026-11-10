<?php
require_once 'includes/conexao.php';

$id = (int) ($_GET['id'] ?? 0);

// Busca o frete juntando (JOIN) os dados das outras tabelas
$consulta = $pdo->prepare(
    'SELECT f.*,
            t.nome AS t_nome, t.cnpj AS t_cnpj, t.telefone AS t_telefone, t.placa AS t_placa,
            o.apelido AS o_apelido, o.logradouro AS o_logradouro, o.numero AS o_numero,
            o.bairro AS o_bairro, o.cidade AS o_cidade, o.uf AS o_uf, o.cep AS o_cep,
            d.apelido AS d_apelido, d.logradouro AS d_logradouro, d.numero AS d_numero,
            d.bairro AS d_bairro, d.cidade AS d_cidade, d.uf AS d_uf, d.cep AS d_cep
       FROM fretes f
       JOIN transportadores t ON t.id = f.transportador_id
       JOIN enderecos o ON o.id = f.origem_id
       JOIN enderecos d ON d.id = f.destino_id
      WHERE f.id = ?'
);
$consulta->execute([$id]);
$f = $consulta->fetch();

if (!$f) {
    header('Location: frete.php');
    exit;
}

// Busca os produtos deste frete
$consulta = $pdo->prepare(
    'SELECT p.nome, p.peso, p.valor, fp.quantidade
       FROM frete_produtos fp
       JOIN produtos p ON p.id = fp.produto_id
      WHERE fp.frete_id = ?
      ORDER BY fp.id'
);
$consulta->execute([$id]);
$itens = $consulta->fetchAll();

$pesoTotal = 0;
$valorCarga = 0;
foreach ($itens as $item) {
    $pesoTotal += $item['peso'] * $item['quantidade'];
    $valorCarga += $item['valor'] * $item['quantidade'];
}

$titulo = 'Frete #' . $f['id'];
include 'includes/header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 titulo-pagina">Frete #<?php echo $f['id']; ?></h1>
        <div class="d-flex gap-2">
            <a href="frete.php?editar=<?php echo $f['id']; ?>" class="btn btn-outline-primary">Editar</a>
            <a href="frete.php" class="btn btn-primary">Novo frete</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                    <h2 class="h6 text-muted">Transportador</h2>
                    <p class="mb-1 fw-semibold"><?php echo htmlspecialchars($f['t_nome']); ?></p>
                    <p class="mb-0 small text-muted">
                        CNPJ: <?php echo htmlspecialchars($f['t_cnpj']); ?><br>
                        Tel.: <?php echo htmlspecialchars($f['t_telefone']); ?><br>
                        Placa: <?php echo htmlspecialchars($f['t_placa']); ?>
                    </p>
                </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                    <h2 class="h6 text-muted">Origem</h2>
                    <p class="mb-1 fw-semibold"><?php echo htmlspecialchars($f['o_apelido']); ?></p>
                    <p class="mb-0 small text-muted">
                        <?php echo htmlspecialchars($f['o_logradouro']); ?>, <?php echo htmlspecialchars($f['o_numero']); ?><br>
                        <?php echo htmlspecialchars($f['o_bairro']); ?> - <?php echo htmlspecialchars($f['o_cidade']); ?>/<?php echo htmlspecialchars($f['o_uf']); ?><br>
                        CEP <?php echo htmlspecialchars($f['o_cep']); ?>
                    </p>
                </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                    <h2 class="h6 text-muted">Destino</h2>
                    <p class="mb-1 fw-semibold"><?php echo htmlspecialchars($f['d_apelido']); ?></p>
                    <p class="mb-0 small text-muted">
                        <?php echo htmlspecialchars($f['d_logradouro']); ?>, <?php echo htmlspecialchars($f['d_numero']); ?><br>
                        <?php echo htmlspecialchars($f['d_bairro']); ?> - <?php echo htmlspecialchars($f['d_cidade']); ?>/<?php echo htmlspecialchars($f['d_uf']); ?><br>
                        CEP <?php echo htmlspecialchars($f['d_cep']); ?>
                    </p>
                </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white">
            <strong>Produtos</strong> &middot; Data: <?php echo date('d/m/Y', strtotime($f['data_frete'])); ?>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th class="text-end">Qtd.</th>
                    <th class="text-end">Peso unit. (kg)</th>
                    <th class="text-end">Valor unit.</th>
                    <th class="text-end">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td class="text-end"><?php echo (int) $item['quantidade']; ?></td>
                        <td class="text-end"><?php echo number_format($item['peso'], 3, ',', '.'); ?></td>
                        <td class="text-end">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                        <td class="text-end">R$ <?php echo number_format($item['valor'] * $item['quantidade'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="2">Totais</th>
                    <th class="text-end"><?php echo number_format($pesoTotal, 3, ',', '.'); ?> kg</th>
                    <th></th>
                    <th class="text-end">R$ <?php echo number_format($valorCarga, 2, ',', '.'); ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="mb-1"><strong>Valor do frete:</strong> R$ <?php echo number_format($f['valor_frete'], 2, ',', '.'); ?></p>
            <p class="mb-0"><strong>Observações:</strong>
                <?php echo $f['observacoes'] ? htmlspecialchars($f['observacoes']) : '<span class="text-muted">-</span>'; ?>
            </p>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>