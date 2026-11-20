<?php
require_once 'includes/conexao.php';
$titulo = 'Início';

// Conta os registros de cada tabela (aparecem como um selinho nos botões)
$totalProdutos = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalEnderecos = (int) $pdo->query('SELECT COUNT(*) FROM enderecos')->fetchColumn();
$totalTransportadores = (int) $pdo->query('SELECT COUNT(*) FROM transportadores')->fetchColumn();
$totalFretes = (int) $pdo->query('SELECT COUNT(*) FROM fretes')->fetchColumn();

// [nome do botão, página, ícone, quantidade (ou null para não mostrar)]
$botoes = [
        ['Produtos',         'produto.php',       'bi-box-seam',                $totalProdutos],
        ['Endereços',        'endereco.php',      'bi-geo-alt',                 $totalEnderecos],
        ['Transportadores',  'transportador.php', 'bi-person-badge',            $totalTransportadores],
        ['Novo frete',       'frete.php',         'bi-plus-circle',             null],
        ['Fretes',           'fretes.php',        'bi-truck',                   $totalFretes],
        ['Relatório do dia', 'fretes.php',        'bi-file-earmark-spreadsheet', null],
];

include 'includes/header.php';
?>

    <section class="painel-inicial">
        <p class="saudacao">Olá, seja bem-vindo(a)! Onde você quer ir?</p>

        <div class="menu-botoes">
            <?php foreach ($botoes as [$rotulo, $link, $icone, $quantidade]): ?>
                <a class="botao-menu" href="<?php echo $link; ?>">
                    <span class="botao-menu__icone"><i class="bi <?php echo $icone; ?>"></i></span>
                    <span class="botao-menu__texto"><?php echo htmlspecialchars($rotulo); ?></span>
                    <?php if ($quantidade !== null): ?>
                        <span class="botao-menu__contador"><?php echo $quantidade; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>