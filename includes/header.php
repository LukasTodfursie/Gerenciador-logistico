<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $titulo; ?> | Gerenciador Logístico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Gerenciador Logístico</a>
        <ul class="navbar-nav ms-auto flex-row gap-3">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="produto.php">Produtos</a></li>
            <li class="nav-item"><a class="nav-link" href="frete.php">Fretes</a></li>
            <li class="nav-item"><a class="nav-link" href="endereco.php">Endereços</a></li>
            <li class="nav-item"><a class="nav-link" href="transportador.php">Transportadores</a></li>
        </ul>
    </div>
</nav>

<main class="container py-4">