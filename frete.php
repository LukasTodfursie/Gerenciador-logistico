<?php
require_once 'includes/conexao.php';
$titulo = 'Frete';
$scripts = ['assets/js.js']; // o footer carrega este arquivo (ajuste o nome se o seu for outro)

// Dados que alimentam as listas de escolha (selects)
$transportadores = $pdo->query('SELECT id, nome, placa FROM transportadores ORDER BY nome')->fetchAll();
$enderecos = $pdo->query('SELECT id, apelido, logradouro, numero, cidade, uf FROM enderecos ORDER BY apelido')->fetchAll();
$produtos = $pdo->query('SELECT id, nome, peso FROM produtos ORDER BY nome')->fetchAll();

// Gera uma linha "produto + quantidade" (usada na tela e no modelo do JavaScript)
function linhaItem($produtos, $selecionado = '', $quantidade = 1)
{
    $html  = '<div class="row g-2 item-frete align-items-end">';
    $html .= '<div class="col-8"><label class="form-label small text-muted">Produto</label>';
    $html .= '<select class="form-select" name="produto[]">';
    $html .= '<option value="">Selecione um produto...</option>';
    foreach ($produtos as $p) {
        $marcado = ((string) $p['id'] === (string) $selecionado) ? ' selected' : '';
        $html .= '<option value="' . $p['id'] . '"' . $marcado . '>' . htmlspecialchars($p['nome']) . '</option>';
    }
    $html .= '</select></div>';
    $html .= '<div class="col-3"><label class="form-label small text-muted">Quantidade</label>';
    $html .= '<input type="number" min="1" class="form-control" name="quantidade[]" value="' . (int) $quantidade . '"></div>';
    $html .= '<div class="col-1 d-grid"><button type="button" class="btn btn-outline-danger btn-remover" title="Remover">X</button></div>';
    $html .= '</div>';
    return $html;
}

$erros = [];
$editando = null; // id do frete em edição (null = frete novo)
$frete = [
        'transportador_id' => '', 'origem_id' => '', 'destino_id' => '',
        'data_frete' => date('Y-m-d'), 'valor_frete' => '', 'observacoes' => '',
];
$itensDigitados = [['', 1]]; // começa com uma linha vazia

// Modo edição: a página foi aberta com ?editar=7
if (isset($_GET['editar'])) {
    $consulta = $pdo->prepare('SELECT * FROM fretes WHERE id = ?');
    $consulta->execute([(int) $_GET['editar']]);
    $atual = $consulta->fetch();

    if ($atual) {
        $editando = (int) $atual['id'];
        $frete = $atual;

        $consulta = $pdo->prepare('SELECT produto_id, quantidade FROM frete_produtos WHERE frete_id = ? ORDER BY id');
        $consulta->execute([$editando]);
        $itensDigitados = [];
        foreach ($consulta->fetchAll() as $linha) {
            $itensDigitados[] = [$linha['produto_id'], $linha['quantidade']];
        }
        if (!$itensDigitados) {
            $itensDigitados = [['', 1]];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id'])) {
        $editando = (int) $_POST['id'];
    }

    $frete = [
            'transportador_id' => (int) ($_POST['transportador_id'] ?? 0),
            'origem_id'        => (int) ($_POST['origem_id'] ?? 0),
            'destino_id'       => (int) ($_POST['destino_id'] ?? 0),
            'data_frete'       => trim($_POST['data_frete'] ?? ''),
            'valor_frete'      => str_replace(',', '.', trim($_POST['valor_frete'] ?? '')),
            'observacoes'      => trim($_POST['observacoes'] ?? ''),
    ];

    // Lê as linhas de produto enviadas pelos campos dinâmicos
    $escolhidos = $_POST['produto'] ?? [];
    $quantidades = $_POST['quantidade'] ?? [];
    $idsValidos = array_map('intval', array_column($produtos, 'id'));

    $itens = [];            // [id do produto => quantidade total]
    $itensDigitados = [];   // para reexibir no formulário se houver erro
    foreach ($escolhidos as $posicao => $produtoId) {
        $qtd = (int) ($quantidades[$posicao] ?? 1);
        $itensDigitados[] = [$produtoId, $qtd];

        if ($produtoId === '') {
            continue; // linha sem produto escolhido
        }
        if ($qtd < 1) {
            $erros['qtd'] = 'A quantidade de cada produto deve ser no mínimo 1.';
        } elseif (in_array((int) $produtoId, $idsValidos, true)) {
            // Se o mesmo produto aparecer duas vezes, soma as quantidades
            $itens[(int) $produtoId] = ($itens[(int) $produtoId] ?? 0) + $qtd;
        }
    }
    if (!$itensDigitados) {
        $itensDigitados = [['', 1]];
    }

    // Validações
    if (!$frete['transportador_id']) {
        $erros[] = 'Selecione o transportador.';
    }
    if (!$frete['origem_id']) {
        $erros[] = 'Selecione o endereço de origem.';
    }
    if (!$frete['destino_id']) {
        $erros[] = 'Selecione o endereço de destino.';
    }
    if ($frete['origem_id'] && $frete['origem_id'] === $frete['destino_id']) {
        $erros[] = 'A origem e o destino devem ser endereços diferentes.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $frete['data_frete'])) {
        $erros[] = 'Informe a data do frete.';
    }
    if (!is_numeric($frete['valor_frete']) || $frete['valor_frete'] < 0) {
        $erros[] = 'Informe um valor de frete válido.';
    }
    if (!$itens) {
        $erros[] = 'Escolha pelo menos um produto.';
    }

    if (!$erros) {
        try {
            // Transação: ou grava tudo, ou não grava nada
            $pdo->beginTransaction();

            if ($editando) {
                $comando = $pdo->prepare(
                        'UPDATE fretes SET transportador_id = ?, origem_id = ?, destino_id = ?,
                            data_frete = ?, valor_frete = ?, observacoes = ? WHERE id = ?'
                );
                $comando->execute([$frete['transportador_id'], $frete['origem_id'], $frete['destino_id'],
                        $frete['data_frete'], $frete['valor_frete'], $frete['observacoes'], $editando]);

                // Apaga os produtos antigos; os novos serão inseridos logo abaixo
                $pdo->prepare('DELETE FROM frete_produtos WHERE frete_id = ?')->execute([$editando]);
                $freteId = $editando;
            } else {
                $comando = $pdo->prepare(
                        'INSERT INTO fretes (transportador_id, origem_id, destino_id, data_frete, valor_frete, observacoes)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $comando->execute([$frete['transportador_id'], $frete['origem_id'], $frete['destino_id'],
                        $frete['data_frete'], $frete['valor_frete'], $frete['observacoes']]);
                $freteId = (int) $pdo->lastInsertId(); // id que o banco acabou de gerar
            }

            // Uma linha em frete_produtos para cada produto do frete
            $insere = $pdo->prepare('INSERT INTO frete_produtos (frete_id, produto_id, quantidade) VALUES (?, ?, ?)');
            foreach ($itens as $produtoId => $qtd) {
                $insere->execute([$freteId, $produtoId, $qtd]);
            }

            $pdo->commit();
            header('Location: frete-ver.php?id=' . $freteId);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erros[] = 'Erro ao salvar o frete. Tente novamente.';
        }
    }
}

include 'includes/header.php';
?>

    <h1 class="h3 mb-3 titulo-pagina"><?php echo $editando ? 'Editar frete #' . $editando : 'Novo frete'; ?></h1>

<?php if (!$transportadores || count($enderecos) < 2 || !$produtos): ?>
    <div class="alert alert-warning">
        Para gerar um frete, cadastre antes pelo menos <strong>1 produto</strong>,
        <strong>2 endereços</strong> (origem e destino) e <strong>1 transportador</strong>.
    </div>
<?php endif; ?>

<?php if ($erros): ?>
    <div class="alert alert-danger">
        <?php foreach ($erros as $erro): ?>
            <div><?php echo htmlspecialchars($erro); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post" action="frete.php">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo $editando; ?>">
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Dados do frete</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="transportador_id">Transportador</label>
                        <select class="form-select" id="transportador_id" name="transportador_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($transportadores as $t): ?>
                                <option value="<?php echo $t['id']; ?>"
                                        <?php echo (int) $frete['transportador_id'] === (int) $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['nome']); ?>
                                    <?php echo $t['placa'] ? ' - ' . htmlspecialchars($t['placa']) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="data_frete">Data</label>
                        <input type="date" class="form-control" id="data_frete" name="data_frete"
                               value="<?php echo htmlspecialchars($frete['data_frete']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="valor_frete">Valor do frete (R$)</label>
                        <input type="text" class="form-control" id="valor_frete" name="valor_frete"
                               value="<?php echo htmlspecialchars($frete['valor_frete']); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="origem_id">Origem</label>
                        <select class="form-select" id="origem_id" name="origem_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($enderecos as $en): ?>
                                <option value="<?php echo $en['id']; ?>"
                                        <?php echo (int) $frete['origem_id'] === (int) $en['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($en['apelido'] . ' - ' . $en['logradouro'] . ', ' . $en['numero'] . ' - ' . $en['cidade'] . '/' . $en['uf']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destino_id">Destino</label>
                        <select class="form-select" id="destino_id" name="destino_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($enderecos as $en): ?>
                                <option value="<?php echo $en['id']; ?>"
                                        <?php echo (int) $frete['destino_id'] === (int) $en['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($en['apelido'] . ' - ' . $en['logradouro'] . ', ' . $en['numero'] . ' - ' . $en['cidade'] . '/' . $en['uf']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"><?php echo htmlspecialchars($frete['observacoes']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Produtos transportados</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">+ Adicionar produto</button>
            </div>
            <div class="card-body" id="lista-itens">
                <?php foreach ($itensDigitados as [$produtoId, $qtd]): ?>
                    <?php echo linhaItem($produtos, $produtoId, $qtd); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modelo que o JavaScript copia a cada clique em "Adicionar produto" -->
        <template id="modelo-item">
            <?php echo linhaItem($produtos); ?>
        </template>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?php echo $editando ? 'Salvar alterações' : 'Gerar frete'; ?></button>
            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>

<?php include 'includes/footer.php'; ?>