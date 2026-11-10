<?php
require_once 'includes/conexao.php';
$titulo = 'Transportadores';

$campos = ['nome', 'cnpj', 'telefone', 'email', 'placa'];
$dados = array_fill_keys($campos, '');
$erros = [];
$editando = null;

// Modo edição
if (isset($_GET['editar'])) {
    $consulta = $pdo->prepare('SELECT * FROM transportadores WHERE id = ?');
    $consulta->execute([(int) $_GET['editar']]);
    $atual = $consulta->fetch();

    if ($atual) {
        $editando = (int) $atual['id'];
        foreach ($campos as $campo) {
            $dados[$campo] = $atual[$campo];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'adicionar';

    if ($acao === 'excluir') {
        try {
            $comando = $pdo->prepare('DELETE FROM transportadores WHERE id = ?');
            $comando->execute([(int) $_POST['id']]);
            header('Location: transportador.php');
            exit;
        } catch (PDOException $e) {
            $erros[] = 'Não é possível excluir: este transportador já está sendo usado em um frete.';
        }

    } else {
        foreach ($campos as $campo) {
            $dados[$campo] = trim($_POST[$campo] ?? '');
        }
        $dados['placa'] = strtoupper($dados['placa']);

        if ($acao === 'salvar') {
            $editando = (int) $_POST['id'];
        }

        if ($dados['nome'] === '') {
            $erros[] = 'Informe o nome do transportador.';
        }
        if ($dados['cnpj'] === '') {
            $erros[] = 'Informe o CNPJ (a autenticidade não é validada).';
        }
        if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }

        if (!$erros) {
            $valores = array_values($dados);

            if ($acao === 'salvar') {
                $valores[] = $editando;
                $comando = $pdo->prepare(
                    'UPDATE transportadores SET nome = ?, cnpj = ?, telefone = ?, email = ?, placa = ? WHERE id = ?'
                );
            } else {
                $comando = $pdo->prepare(
                    'INSERT INTO transportadores (nome, cnpj, telefone, email, placa) VALUES (?, ?, ?, ?, ?)'
                );
            }
            $comando->execute($valores);
            header('Location: transportador.php');
            exit;
        }
    }
}

$transportadores = $pdo->query('SELECT * FROM transportadores ORDER BY id DESC')->fetchAll();

include 'includes/header.php';
?>

<h1 class="h3 mb-3 titulo-pagina"><?php echo $editando !== null ? 'Editar transportador' : 'Cadastro de transportador'; ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger">
        <?php foreach ($erros as $erro): ?>
            <div><?php echo htmlspecialchars($erro); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="transportador.php" class="card card-body mb-4">
    <?php if ($editando !== null): ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?php echo $editando; ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="nome">Nome / Razão social</label>
            <input type="text" class="form-control" id="nome" name="nome" maxlength="120"
                   value="<?php echo htmlspecialchars($dados['nome']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="cnpj">CNPJ</label>
            <input type="text" class="form-control" id="cnpj" name="cnpj" maxlength="18" placeholder="00.000.000/0000-00"
                   value="<?php echo htmlspecialchars($dados['cnpj']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="telefone">Telefone</label>
            <input type="text" class="form-control" id="telefone" name="telefone" maxlength="20"
                   value="<?php echo htmlspecialchars($dados['telefone']); ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label" for="email">E-mail</label>
            <input type="text" class="form-control" id="email" name="email" maxlength="120"
                   value="<?php echo htmlspecialchars($dados['email']); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="placa">Placa do veículo</label>
            <input type="text" class="form-control text-uppercase" id="placa" name="placa" maxlength="10"
                   value="<?php echo htmlspecialchars($dados['placa']); ?>">
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?php echo $editando !== null ? 'Salvar' : 'Adicionar'; ?></button>
        <?php if ($editando !== null): ?>
            <a href="transportador.php" class="btn btn-outline-secondary">Cancelar edição</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="card-header">
        <strong>Transportadores cadastrados (<?php echo count($transportadores); ?>)</strong>
    </div>

    <?php if (!$transportadores): ?>
        <div class="card-body text-muted">Nenhum transportador cadastrado ainda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nome</th>
                    <th>CNPJ</th>
                    <th>Contato</th>
                    <th>Placa</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transportadores as $t): ?>
                    <tr class="<?php echo $editando === (int) $t['id'] ? 'table-warning' : ''; ?>">
                        <td><?php echo $t['id']; ?></td>
                        <td><?php echo htmlspecialchars($t['nome']); ?></td>
                        <td><?php echo htmlspecialchars($t['cnpj']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($t['telefone']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($t['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($t['placa']); ?></td>
                        <td class="text-end text-nowrap">
                            <a href="transportador.php?editar=<?php echo $t['id']; ?>"
                               class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="post" action="transportador.php" class="d-inline"
                                  onsubmit="return confirm('Excluir este transportador?');">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
