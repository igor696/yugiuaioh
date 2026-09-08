<?php
/** Funções de uso geral: escape, CSRF, recados, formatação, redirecionamento. */
declare(strict_types=1);

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function num($n): string { return number_format((float)$n, 0, ',', '.'); }
function post(string $k, $p = null) { return $_POST[$k] ?? $p; }
function get(string $k, $p = null)  { return $_GET[$k] ?? $p; }

function irPara(string $url): never { header('Location: ' . $url); exit; }

/** Slug simples, usado para nomes de arquivo e chaves. */
function slug(string $s): string
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $s) ?? '');
    return trim($s, '-');
}

// ---------------------------------------------------------------- CSRF
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrfCampo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}
function csrfConfere(): void
{
    $enviado = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
    if (!hash_equals(csrfToken(), $enviado)) {
        http_response_code(419);
        exit('A página expirou. Volte, atualize e tente de novo.');
    }
}

// ------------------------------------------------------------- recados
/** Mensagem de uma tela só, guardada na sessão até ser mostrada. */
function recado(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['recados'][] = ['texto' => $texto, 'tipo' => $tipo];
}
function pegarRecados(): array
{
    $r = $_SESSION['recados'] ?? [];
    unset($_SESSION['recados']);
    return $r;
}

// ------------------------------------------------------------- resposta JSON
function json($dados, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ------------------------------------------------------------- datas
function dataBr(?string $iso, bool $comHora = true): string
{
    if (!$iso) return '—';
    $t = strtotime($iso);
    return $t ? date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $t) : '—';
}
function faz(?string $iso): string
{
    if (!$iso) return '—';
    $s = time() - (int)strtotime($iso);
    if ($s < 60)    return 'agora';
    if ($s < 3600)  return intdiv($s, 60) . ' min';
    if ($s < 86400) return intdiv($s, 3600) . ' h';
    if ($s < 2592000) return intdiv($s, 86400) . ' dia' . (intdiv($s,86400) > 1 ? 's' : '');
    return dataBr($iso, false);
}

/** O arquivo existe dentro da public_html? Usado para nunca quebrar por falta de arte. */
function temArte(string $caminhoRelativo): bool
{
    return is_file(YU_RAIZ . '/public_html' . $caminhoRelativo);
}
/** Devolve o caminho da arte se ela existir, ou '' — para o HTML decidir. */
function arte(string $caminhoRelativo): string
{
    return temArte($caminhoRelativo) ? $caminhoRelativo : '';
}

/** Pose do mascote, com queda para 'parado' quando a arte não subiu ainda. */
function mascote(string $pose = 'parado'): string
{
    $p = '/assets/img/mascote/indawora-' . $pose . '.png';
    if (temArte($p)) return $p;
    $q = '/assets/img/mascote/indawora-parado.png';
    return temArte($q) ? $q : '';
}

/** Barra de progresso reaproveitada em vários lugares. */
function barra(int $feito, int $total, string $classe = ''): string
{
    $pct = $total > 0 ? max(0, min(100, (int)round($feito * 100 / $total))) : 0;
    return '<div class="barra ' . e($classe) . '"><i style="width:' . $pct . '%"></i></div>';
}

/** Limita um texto sem cortar palavra no meio. */
function corta(string $t, int $max = 120): string
{
    if (mb_strlen($t) <= $max) return $t;
    return mb_substr($t, 0, $max - 1) . '…';
}
