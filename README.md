# Gerenciador Logístico

Sistema web para cadastro e controle de fretes, desenvolvido como atividade prática de desenvolvimento web.
Permite cadastrar produtos, endereços (origem e destino) e transportadores, e gerar fretes em que
um único transportador leva vários produtos (relação 1 para muitos). Também é possível baixar o
relatório dos fretes de um dia específico.

## Tecnologias

- PHP 8 (PDO)
- MySQL / MariaDB
- HTML, CSS e JavaScript
- Bootstrap 5
- XAMPP, Git e GitHub

## Funcionalidades

- Cadastro, edição e exclusão de produtos, endereços e transportadores
- Geração de fretes com campos dinâmicos em JavaScript (adicionar e remover produtos)
- Tela de detalhes do frete, com totais de peso e valor
- Listagem de fretes com filtro por data
- Download do relatório de fretes do dia em CSV (abre no Excel)

## Como executar

1. Instale o [XAMPP](https://www.apachefriends.org/) e inicie o **Apache** e o **MySQL**.
2. Clone este repositório dentro de `C:\xampp\htdocs`:
   `git clone https://github.com/SEU-USUARIO/gerenciador-logistico.git`
3. Abra o phpMyAdmin (`http://localhost/phpmyadmin`), vá na aba **Importar** e selecione o arquivo
   `database/schema.sql`. Isso cria o banco `gerenciador_logistico` e as tabelas.
4. Se o seu MySQL tiver senha, ajuste o arquivo `includes/conexao.php`.
5. Acesse `http://localhost/gerenciador-logistico/`.

## Estrutura

- `index.php`: página inicial
- `produto.php`, `endereco.php`, `transportador.php`: cadastros
- `frete.php`: formulário de frete (com JavaScript)
- `fretes.php`: lista de fretes e filtro por data
- `frete-ver.php`: detalhes de um frete
- `relatorio.php`: download do relatório do dia
- `includes/`: conexão com o banco, cabeçalho e rodapé
- `assets/`: CSS e JavaScript próprios
- `database/schema.sql`: criação do banco de dados

## Autor

Lucas de Jesus