<?php
require_once 'includes/conexao.php';
$titulo = 'Fretes';

// Excluir um frete (os produtos ligados a ele saem juntos, por causa do ON DELETE CASCADE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $comando = $pdo->prepare('DELETE FROM fretes WHERE id = ?');
    $comando->execute([(int) $_POST['id']]);

    // Volta para a lista, mantendo o filtro de data se havia um
    $volta = 'fretes.php';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data'] ?? '')) {
        $volta .= '?data=' . $_POST['data'];
    }
    header('Location: ' . $volta);
    exit;
}

// Filtro por data (opcional)
$data = $_GET['data'] ?? '';
$filtrando = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $data);

// Monta a consulta; só acrescenta o WHERE se houver filtro
$sql = 'SELECT f.id, f.data_frete, f.valor_frete,
               t.nome AS transportador, o.apelido AS origem, d.apelido AS destino,
               (SELECT COALESCE(SUM(fp.quantidade), 0)
                  FROM frete_produtos fp WHERE fp.frete_id = f.id) AS total_itens
          FROM fretes f
          JOIN transportadores t ON t.id = f.transportador_id
          JOIN enderecos o ON o.id = f.origem_id
          JOIN enderecos d ON d.id = f.destino_id';
$parametros = [];
if ($filtrando) {
    $sql .= ' WHERE f.data_frete = ?';
    $parametros[] = $data;
}
$sql .= ' ORDER BY f.data_frete DESC, f.id DESC';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$fretes = $consulta->fetchAll();

$valorTotal = 0;
foreach ($fretes as $f) {
    $valorTotal += $f['valor_frete'];
}

// Avisos que o relatório pode mandar de volta
$aviso = $_GET['aviso'] ?? '';

include 'includes/header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 titulo-pagina">Fretes</h1>
        <a href="frete.php" class="btn btn-primary">+ Novo frete</a>
    </div>

<?php if ($aviso === 'data'): ?>
    <div class="alert alert-warning">Escolha uma data válida para gerar o relatório.</div>
<?php elseif ($aviso === 'vazio'): ?>
    <div class="alert alert-warning">Nenhum frete encontrado na data escolhida.</div>
<?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="fretes.php" class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label" for="data">Data do frete</label>
                    <input type="date" class="form-control" id="data" name="data"
                           value="<?php echo $filtrando ? htmlspecialchars($data) : ''; ?>">
                </div>
                <div class="col-auto d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                    <a href="fretes.php" class="btn btn-outline-secondary">Limpar</a>
                    <button type="submit" formaction="relatorio.php" class="btn btn-success">
                        Baixar relatório do dia
                    </button>
                </div>
            </form>
            <div class="form-text mt-2">
                Escolha uma data e clique em "Baixar relatório do dia" para gerar um arquivo CSV (abre no Excel).
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Fretes (<?php echo count($fretes); ?>)</strong>
            <?php if ($filtrando): ?>
                <span class="text-muted">&middot; em <?php echo date('d/m/Y', strtotime($data)); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$fretes): ?>
            <div class="card-body text-muted">Nenhum frete encontrado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Data</th>
                        <th>Transportador</th>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th class="text-end">Itens</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fretes as $f): ?>
                        <tr>
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($f['data_frete'])); ?></td>
                            <td><?php echo htmlspecialchars($f['transportador']); ?></td>
                            <td><?php echo htmlspecialchars($f['origem']); ?></td>
                            <td><?php echo htmlspecialchars($f['destino']); ?></td>
                            <td class="text-end"><?php echo (int) $f['total_itens']; ?></td>
                            <td class="text-end">R$ <?php echo number_format($f['valor_frete'], 2, ',', '.'); ?></td>
                            <td class="text-end text-nowrap">
                                <a href="frete-ver.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
                                <a href="frete.php?editar=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="post" action="fretes.php" class="d-inline"
                                      onsubmit="return confirm('Excluir este frete?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                    <input type="hidden" name="data" value="<?php echo $filtrando ? htmlspecialchars($data) : ''; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                    <tr>
                        <th colspan="6">Total dos fretes</th>
                        <th class="text-end">R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>