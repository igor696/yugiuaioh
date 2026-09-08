<?php
/** Correio interno: mensagens do sistema, do mascote e do Faraó. */
declare(strict_types=1);

function mandarMensagem(int $duelistaId, string $titulo, string $corpo, string $tipo = 'sistema'): void
{
    ex('INSERT INTO mensagens (duelista_id, tipo, titulo, corpo) VALUES (?,?,?,?)',
       [$duelistaId, $tipo, $titulo, $corpo]);
}
function mensagensNaoLidas(int $duelistaId): int
{
    return (int)qv('SELECT COUNT(*) FROM mensagens WHERE duelista_id = ? AND lida_em IS NULL', [$duelistaId], 0);
}
function minhasMensagens(int $duelistaId, int $limite = 40): array
{
    return q('SELECT * FROM mensagens WHERE duelista_id = ? ORDER BY id DESC LIMIT ' . (int)$limite, [$duelistaId]);
}
function marcarLida(int $id, int $duelistaId): void
{
    ex('UPDATE mensagens SET lida_em = NOW() WHERE id = ? AND duelista_id = ? AND lida_em IS NULL', [$id, $duelistaId]);
}
