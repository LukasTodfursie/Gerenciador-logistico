<?php
require_once 'includes/conexao.php';
$titulo = 'Produtos';

$erros = [];
$nome = '';
$peso = '';
$valor = '';
$editando = null; // id do produto em edição (null = nenhum)

// Modo edição: a página foi aberta com ?editar=3
if (isset($_GET['editar'])) {
    $consulta = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $consulta->execute([(int) $_GET['editar']]);
    $produtoAtual = $consulta->fetch();

    if ($produtoAtual) {
        $editando = (int) $produtoAtual['id'];
        $nome = $produtoAtual['nome'];
        $peso = $produtoAtual['peso'];
        $valor = $produtoAtual['valor'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'adicionar';

    if ($acao === 'excluir') {
        try {
            $comando = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
            $comando->execute([(int) $_POST['id']]);
            header('Location: produto.php');
            exit;
        } catch (PDOException $e) {
            // O banco impede apagar um produto que já está em algum frete
            $erros[] = 'Não é possível excluir: este produto já está sendo usado em um frete.';
        }

    } else {
        // Vale para "adicionar" e para "salvar" (edição)
        $nome = trim($_POST['nome']);
        $peso = str_replace(',', '.', trim($_POST['peso']));
        $valor = str_replace(',', '.', trim($_POST['valor']));

        if ($acao === 'salvar') {
            $editando = (int) $_POST['id'];
        }

        if ($nome === '') {
            $erros[] = 'Informe o nome do produto.';
        }
        if (!is_numeric($peso) || $peso <= 0) {
            $erros[] = 'Informe um peso válido, maior que zero.';
        }
        if (!is_numeric($valor) || $valor < 0) {
            $erros[] = 'Informe um valor válido (pode ser zero).';
        }

        if (!$erros) {
            if ($acao === 'salvar') {
                $comando = $pdo->prepare('UPDATE produtos SET nome = ?, peso = ?, valor = ? WHERE id = ?');
                $comando->execute([$nome, $peso, $valor, $editando]);
            } else {
                $comando = $pdo->prepare('INSERT INTO produtos (nome, peso, valor) VALUES (?, ?, ?)');
                $comando->execute([$nome, $peso, $valor]);
            }
            header('Location: produto.php');
            exit;
        }
    }
}

// Busca todos os produtos no banco (do mais novo para o mais antigo)
$produtos = $pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();

// Calcula os totais
$pesoTotal = 0;
$valorTotal = 0;
foreach ($produtos as $produto) {
    $pesoTotal += $produto['peso'];
    $valorTotal += $produto['valor'];
}

include 'includes/header.php';
?>

    <h1 class="h3 mb-3 titulo-pagina"><?php echo $editando !== null ? 'Editar produto' : 'Cadastro de produto'; ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger">
        <?php foreach ($erros as $erro): ?>
            <div><?php echo htmlspecialchars($erro); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post" action="produto.php" class="card card-body mb-4">
        <?php if ($editando !== null): ?>
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" value="<?php echo $editando; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="nome">Nome do produto</label>
                <input type="text" class="form-control" id="nome" name="nome"
                       value="<?php echo htmlspecialchars($nome); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="peso">Peso (kg)</label>
                <input type="text" class="form-control" id="peso" name="peso"
                       value="<?php echo htmlspecialchars($peso); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="valor">Valor (R$)</label>
                <input type="text" class="form-control" id="valor" name="valor"
                       value="<?php echo htmlspecialchars($valor); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <?php echo $editando !== null ? 'Salvar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>

        <?php if ($editando !== null): ?>
            <div class="mt-3">
                <a href="produto.php" class="btn btn-sm btn-outline-secondary">Cancelar edição</a>
            </div>
        <?php endif; ?>
    </form>

    <div class="card">
        <div class="card-header">
            <strong>Produtos cadastrados (<?php echo count($produtos); ?>)</strong>
        </div>

        <?php if (!$produtos): ?>
            <div class="card-body text-muted">Nenhum produto cadastrado ainda.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Nome</th>
                        <th class="text-end">Peso (kg)</th>
                        <th class="text-end">Valor (R$)</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr class="<?php echo $editando === (int) $produto['id'] ? 'table-warning' : ''; ?>">
                            <td><?php echo $produto['id']; ?></td>
                            <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td class="text-end"><?php echo number_format($produto['peso'], 3, ',', '.'); ?></td>
                            <td class="text-end">R$ <?php echo number_format($produto['valor'], 2, ',', '.'); ?></td>
                            <td class="text-end text-nowrap">
                                <a href="produto.php?editar=<?php echo $produto['id']; ?>"
                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="post" action="produto.php" class="d-inline"
                                      onsubmit="return confirm('Excluir este produto?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                    <tr>
                        <th colspan="2">Totais</th>
                        <th class="text-end"><?php echo number_format($pesoTotal, 3, ',', '.'); ?> kg</th>
                        <th class="text-end">R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>