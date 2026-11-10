<?php
require_once 'includes/conexao.php';
$titulo = 'Endereços';

// Lista dos campos do formulário (na mesma ordem das colunas do banco)
$campos = ['apelido', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'];
$dados = array_fill_keys($campos, ''); // cria todos os campos vazios
$erros = [];
$editando = null; // id do endereço em edição (null = nenhum)

// Modo edição: a página foi aberta com ?editar=3
if (isset($_GET['editar'])) {
    $consulta = $pdo->prepare('SELECT * FROM enderecos WHERE id = ?');
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
            $comando = $pdo->prepare('DELETE FROM enderecos WHERE id = ?');
            $comando->execute([(int) $_POST['id']]);
            header('Location: endereco.php');
            exit;
        } catch (PDOException $e) {
            $erros[] = 'Não é possível excluir: este endereço já está sendo usado em um frete.';
        }

    } else {
        // Lê todos os campos do formulário de uma vez
        foreach ($campos as $campo) {
            $dados[$campo] = trim($_POST[$campo] ?? '');
        }
        $dados['uf'] = strtoupper($dados['uf']);

        if ($acao === 'salvar') {
            $editando = (int) $_POST['id'];
        }

        // Campos obrigatórios
        $obrigatorios = ['apelido' => 'apelido', 'logradouro' => 'logradouro', 'numero' => 'número',
            'bairro' => 'bairro', 'cidade' => 'cidade'];
        foreach ($obrigatorios as $campo => $rotulo) {
            if ($dados[$campo] === '') {
                $erros[] = "Informe o campo $rotulo.";
            }
        }
        if (!preg_match('/^\d{5}-?\d{3}$/', $dados['cep'])) {
            $erros[] = 'Informe o CEP no formato 00000-000.';
        }
        if (!preg_match('/^[A-Z]{2}$/', $dados['uf'])) {
            $erros[] = 'Informe a UF com 2 letras (ex.: PR).';
        }

        if (!$erros) {
            $valores = array_values($dados); // os valores, na ordem dos campos

            if ($acao === 'salvar') {
                $valores[] = $editando; // o id vai por último, para o WHERE
                $comando = $pdo->prepare(
                    'UPDATE enderecos SET apelido = ?, cep = ?, logradouro = ?, numero = ?,
                            complemento = ?, bairro = ?, cidade = ?, uf = ? WHERE id = ?'
                );
            } else {
                $comando = $pdo->prepare(
                    'INSERT INTO enderecos (apelido, cep, logradouro, numero, complemento, bairro, cidade, uf)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
            }
            $comando->execute($valores);
            header('Location: endereco.php');
            exit;
        }
    }
}

$enderecos = $pdo->query('SELECT * FROM enderecos ORDER BY id DESC')->fetchAll();

include 'includes/header.php';
?>

    <h1 class="h3 mb-3 titulo-pagina"><?php echo $editando !== null ? 'Editar endereço' : 'Cadastro de endereço'; ?></h1>

<?php if ($erros): ?>
    <div class="alert alert-danger">
        <?php foreach ($erros as $erro): ?>
            <div><?php echo htmlspecialchars($erro); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <form method="post" action="endereco.php" class="card card-body mb-4">
        <?php if ($editando !== null): ?>
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" value="<?php echo $editando; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="apelido">Apelido <small class="text-muted">(ex.: Depósito Central)</small></label>
                <input type="text" class="form-control" id="apelido" name="apelido" maxlength="80"
                       value="<?php echo htmlspecialchars($dados['apelido']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cep">CEP</label>
                <input type="text" class="form-control" id="cep" name="cep" maxlength="9" placeholder="00000-000"
                       value="<?php echo htmlspecialchars($dados['cep']); ?>">
            </div>
            <div class="col-md-9">
                <label class="form-label" for="logradouro">Logradouro</label>
                <input type="text" class="form-control" id="logradouro" name="logradouro" maxlength="150"
                       value="<?php echo htmlspecialchars($dados['logradouro']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="numero">Número</label>
                <input type="text" class="form-control" id="numero" name="numero" maxlength="10"
                       value="<?php echo htmlspecialchars($dados['numero']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="complemento">Complemento</label>
                <input type="text" class="form-control" id="complemento" name="complemento" maxlength="80"
                       value="<?php echo htmlspecialchars($dados['complemento']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bairro">Bairro</label>
                <input type="text" class="form-control" id="bairro" name="bairro" maxlength="80"
                       value="<?php echo htmlspecialchars($dados['bairro']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cidade">Cidade</label>
                <input type="text" class="form-control" id="cidade" name="cidade" maxlength="80"
                       value="<?php echo htmlspecialchars($dados['cidade']); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label" for="uf">UF</label>
                <input type="text" class="form-control text-uppercase" id="uf" name="uf" maxlength="2"
                       value="<?php echo htmlspecialchars($dados['uf']); ?>">
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?php echo $editando !== null ? 'Salvar' : 'Adicionar'; ?></button>
            <?php if ($editando !== null): ?>
                <a href="endereco.php" class="btn btn-outline-secondary">Cancelar edição</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <strong>Endereços cadastrados (<?php echo count($enderecos); ?>)</strong>
        </div>

        <?php if (!$enderecos): ?>
            <div class="card-body text-muted">Nenhum endereço cadastrado ainda.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Apelido</th>
                        <th>Endereço</th>
                        <th>CEP</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enderecos as $e): ?>
                        <tr class="<?php echo $editando === (int) $e['id'] ? 'table-warning' : ''; ?>">
                            <td><?php echo $e['id']; ?></td>
                            <td><?php echo htmlspecialchars($e['apelido']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($e['logradouro']); ?>, <?php echo htmlspecialchars($e['numero']); ?>
                                <?php echo $e['complemento'] ? ' - ' . htmlspecialchars($e['complemento']) : ''; ?><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($e['bairro']); ?> &middot;
                                    <?php echo htmlspecialchars($e['cidade']); ?>/<?php echo htmlspecialchars($e['uf']); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($e['cep']); ?></td>
                            <td class="text-end text-nowrap">
                                <a href="endereco.php?editar=<?php echo $e['id']; ?>"
                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="post" action="endereco.php" class="d-inline"
                                      onsubmit="return confirm('Excluir este endereço?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
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