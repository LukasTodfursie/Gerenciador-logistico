// Campos dinâmicos do frete:
// adiciona e remove linhas de produto sem recarregar a página.
document.addEventListener('DOMContentLoaded', function () {
    var lista = document.getElementById('lista-itens');
    var modelo = document.getElementById('modelo-item');
    var botaoAdicionar = document.getElementById('btn-add-item');

    // Clique em "Adicionar produto": copia o modelo e coloca no fim da lista
    botaoAdicionar.addEventListener('click', function () {
        lista.appendChild(modelo.content.cloneNode(true));
    });

    // Clique em qualquer botão de remover (inclusive os das linhas novas)
    lista.addEventListener('click', function (evento) {
        var botao = evento.target.closest('.btn-remover');
        if (!botao) {
            return;
        }
        botao.closest('.item-frete').remove();

        // Garante que sempre sobre pelo menos uma linha
        if (lista.children.length === 0) {
            lista.appendChild(modelo.content.cloneNode(true));
        }
    });
});