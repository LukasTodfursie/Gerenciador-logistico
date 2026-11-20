<?php
// Funções de validação e formatação reutilizáveis em todo o sistema.

/** Remove tudo que não for número (pontos, traços, barras, espaços). */
function somenteNumeros($texto)
{
    return preg_replace('/\D/', '', (string) $texto);
}

/** Valida um CNPJ pelos dois dígitos verificadores. */
function validarCnpj($cnpj)
{
    $cnpj = somenteNumeros($cnpj);

    // Precisa ter 14 números e não pode ser uma sequência repetida (00000000000000, 11111111111111...)
    if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }

    $pesos = [
        12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],       // para o 1º dígito verificador
        13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],    // para o 2º dígito verificador
    ];

    foreach ($pesos as $posicao => $listaPesos) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cnpj[$i] * $listaPesos[$i];
        }
        $resto = $soma % 11;
        $digitoCorreto = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[$posicao] !== $digitoCorreto) {
            return false;
        }
    }
    return true;
}

/** Valida o formato do CEP: 8 números (com ou sem hífen). */
function validarCep($cep)
{
    $cep = somenteNumeros($cep);
    return strlen($cep) === 8 && $cep !== '00000000';
}

/** 12345678000195 -> 12.345.678/0001-95 */
function formatarCnpj($cnpj)
{
    $n = somenteNumeros($cnpj);
    return substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3)
        . '/' . substr($n, 8, 4) . '-' . substr($n, 12, 2);
}

/** 85960000 -> 85960-000 */
function formatarCep($cep)
{
    $n = somenteNumeros($cep);
    return substr($n, 0, 5) . '-' . substr($n, 5, 3);
}