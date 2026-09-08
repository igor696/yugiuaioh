<?php
/** Ajustes chave/valor do site, mexidos pelo FARAÓ. */
declare(strict_types=1);

function ajustes(): array
{
    static $c = null;
    if ($c === null) {
        $c = [];
        try { foreach (q('SELECT chave, valor FROM ajustes') as $l) { $c[$l['chave']] = $l['valor']; } }
        catch (Throwable $e) { $c = []; }
    }
    return $c;
}
function ajuste(string $chave, $padrao = null) { return ajustes()[$chave] ?? $padrao; }
function ajusteLigado(string $chave, bool $padrao = false): bool
{
    $v = ajuste($chave);
    return $v === null ? $padrao : in_array((string)$v, ['1','sim','true','on'], true);
}
function gravarAjuste(string $chave, string $valor): void
{
    ex('INSERT INTO ajustes (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$chave, $valor]);
}
