<?php
// Cabeçalho comum: abre o HTML, carrega os estilos e mostra o menu.
$paginaAtual = basename($_SERVER['SCRIPT_NAME']);

// [arquivo, nome no menu, ícone, páginas que deixam o item destacado]
$menu = [
        ['index.php',         'Início',          'bi-house-door',   ['index.php']],
        ['produto.php',       'Produtos',        'bi-box-seam',     ['produto.php']],
        ['endereco.php',      'Endereços',       'bi-geo-alt',      ['endereco.php']],
        ['transportador.php', 'Transportadores', 'bi-person-badge', ['transportador.php']],
        ['fretes.php',        'Fretes',          'bi-truck',        ['fretes.php', 'frete.php', 'frete-ver.php']],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo); ?> | Gerenciador Logístico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-escura">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-truck-front-fill"></i> Gerenciador Logístico</a>
        <ul class="navbar-nav ms-auto flex-row flex-wrap gap-3">
            <?php foreach ($menu as [$arquivo, $rotulo, $icone, $paginas]): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($paginaAtual, $paginas) ? 'active' : ''; ?>"
                       href="<?php echo $arquivo; ?>">
                        <i class="bi <?php echo $icone; ?>"></i> <?php echo $rotulo; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<main class="container py-4">