<?php
/**
 * Entrada e saída. Login é NOME DE DUELISTA + senha, sem e-mail —
 * mesma regra do Magic the Minas.
 */
declare(strict_types=1);

function duelistaAtual(): ?array
{
    static $cache = null;
    if ($cache !== null) return $cache ?: null;
    $id = (int)($_SESSION['duelista_id'] ?? 0);
    if (!$id) { $cache = false; return null; }

    global $CFG;
    $limite = (int)($CFG['seguranca']['sessao_minutos'] ?? 240) * 60;
    if (!empty($_SESSION['visto_em']) && (time() - (int)$_SESSION['visto_em']) > $limite) {
        sair(); $cache = false; return null;
    }
    $_SESSION['visto_em'] = time();

    $d = q1('SELECT * FROM duelistas WHERE id = ? AND ativo = 1', [$id]);
    $cache = $d ?: false;
    return $d ?: null;
}
function estaLogado(): bool { return duelistaAtual() !== null; }
function ehFarao(): bool
{
    $d = duelistaAtual();
    return $d !== null && ($d['papel'] ?? '') === 'farao';
}
/** O dono da casa — só ele manda presente. */
function ehDono(): bool
{
    $d = duelistaAtual();
    return ehFarao() && trim((string)($d['numero'] ?? '')) === '#YU0001';
}
function exigirLogin(): void
{
    if (!estaLogado()) {
        $_SESSION['voltar_para'] = $_SERVER['REQUEST_URI'] ?? '/painel.php';
        recado('Entre para continuar.', 'aviso');
        irPara('/index.php');
    }
}
function exigirFarao(): void
{
    exigirLogin();
    if (!ehFarao()) { http_response_code(403); exit('Só o Faraó entra aqui.'); }
}

function entrar(string $nome, string $senha): array
{
    global $CFG;
    $nome = trim($nome);
    if ($nome === '' || $senha === '') return ['erro' => 'Preencha o nome e a senha.'];

    $d = q1('SELECT * FROM duelistas WHERE nome = ?', [$nome]);
    if (!$d) { usleep(300000); return ['erro' => 'Nome ou senha não conferem.']; }

    if (!empty($d['travado_ate']) && strtotime((string)$d['travado_ate']) > time()) {
        $faltam = (int)ceil((strtotime((string)$d['travado_ate']) - time()) / 60);
        return ['erro' => "Conta segura por mais {$faltam} minuto(s) por erro de senha."];
    }
    if (!password_verify($senha, (string)$d['senha_hash'])) {
        $erros = (int)$d['erros_senha'] + 1;
        $max   = (int)($CFG['seguranca']['tentativas_max'] ?? 5);
        if ($erros >= $max) {
            $mins = (int)($CFG['seguranca']['bloqueio_minutos'] ?? 15);
            ex('UPDATE duelistas SET erros_senha = 0, travado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?', [$mins, $d['id']]);
            return ['erro' => "Errou {$max} vezes. A conta fica segura por {$mins} minutos."];
        }
        ex('UPDATE duelistas SET erros_senha = ? WHERE id = ?', [$erros, $d['id']]);
        usleep(300000);
        return ['erro' => 'Nome ou senha não conferem.'];
    }
    if ((int)$d['ativo'] !== 1) return ['erro' => 'Esta conta está desligada.'];

    session_regenerate_id(true);
    $_SESSION['duelista_id'] = (int)$d['id'];
    $_SESSION['visto_em']    = time();
    ex('UPDATE duelistas SET erros_senha = 0, travado_ate = NULL, ultimo_acesso = NOW() WHERE id = ?', [$d['id']]);
    registrarPresenca((int)$d['id']);
    return ['ok' => true, 'duelista' => $d];
}

function sair(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
