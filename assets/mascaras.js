// Máscaras de CNPJ e CEP
document.addEventListener('DOMContentLoaded', function () {

    function soNumeros(texto) {
        return texto.replace(/\D/g, '');
    }

    function mascaraCnpj(n) {
        var r = n.substring(0, 2);
        if (n.length > 2)  { r += '.' + n.substring(2, 5); }
        if (n.length > 5)  { r += '.' + n.substring(5, 8); }
        if (n.length > 8)  { r += '/' + n.substring(8, 12); }
        if (n.length > 12) { r += '-' + n.substring(12, 14); }
        return r;
    }

    function mascaraCep(n) {
        return n.length > 5 ? n.substring(0, 5) + '-' + n.substring(5, 8) : n;
    }

    function preencher(id, valor) {
        var campo = document.getElementById(id);
        if (campo && valor) {
            campo.value = valor;
        }
    }

    //  CNPJ: formata enquanto digita
    var campoCnpj = document.getElementById('cnpj');
    if (campoCnpj) {
        campoCnpj.addEventListener('input', function () {
            campoCnpj.value = mascaraCnpj(soNumeros(campoCnpj.value).slice(0, 14));
        });
    }

    // CEP: formata e consulta o ViaCEP quando chegar a 8 números
    var campoCep = document.getElementById('cep');
    if (campoCep) {
        campoCep.addEventListener('input', function () {
            var numeros = soNumeros(campoCep.value).slice(0, 8);
            campoCep.value = mascaraCep(numeros);
            campoCep.classList.remove('is-valid', 'is-invalid');

            if (numeros.length === 8) {
                buscarCep(numeros);
            }
        });
    }

    function buscarCep(numeros) {
        fetch('https://viacep.com.br/ws/' + numeros + '/json/')
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                if (dados.erro) {
                    campoCep.classList.add('is-invalid'); // CEP não existe
                    return;
                }
                campoCep.classList.add('is-valid');
                preencher('logradouro', dados.logradouro);
                preencher('bairro', dados.bairro);
                preencher('cidade', dados.localidade);
                preencher('uf', dados.uf);

                var numero = document.getElementById('numero');
                if (numero) {
                    numero.focus(); // leva o cursor para o campo "Número"
                }
            })
            .catch(function () {
                // Sem internet ou API fora do ar: o usuário preenche à mão
            });
    }
});