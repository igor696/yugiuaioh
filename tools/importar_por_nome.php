<?php
/**
 * SEGUNDA PASSADA DO IMPORTADOR — PROCURA PELO NOME.
 *
 * O importador principal casa as cartas pelo PASSCODE. Para uma parte
 * delas o número que veio na lista de origem não é o `id` que o
 * YGOPRODeck usa, e aí a carta volta sem texto e sem arte.
 *
 * Este script pega SÓ essas — as que continuam sem texto — e procura
 * pelo nome. Quando acha, ele:
 *
 *   · grava o texto de regras;
 *   · acerta `fusao` e `ritual_monstro` pela classificação oficial;
 *   · CORRIGE O PASSCODE no banco para o número certo, senão a arte
 *     baixada teria um nome de arquivo que o site nunca procuraria;
 *   · baixa a arte para public_html/cache/cartas/.
 *
 * Pode rodar quantas vezes quiser: ele só olha o que ainda falta.
 *
 *     php tools/importar_por_nome.php            (só o texto)
 *     php tools/importar_por_nome.php --imagens  (texto + arte)
 *     php tools/importar_por_nome.php --listar   (só mostra o que falta)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Este script roda só pela linha de comando.\n"); }
require __DIR__ . '/../app/bootstrap.php';

$API        = 'https://db.ygoprodeck.com/api/v7/cardinfo.php';
$comImagens = in_array('--imagens', $argv, true);
$soListar   = in_array('--listar', $argv, true);
$pastaArte  = YU_RAIZ . '/public_html/cache/cartas';
if (!is_dir($pastaArte)) { mkdir($pastaArte, 0755, true); }

function pegar(string $url): ?array
{
    $ctx = stream_context_create(['http' => [
        'timeout' => 25, 'ignore_errors' => true,
        'header'  => "User-Agent: YuGiUaiOh/1.0 (projeto de fã)\r\n",
    ]]);
    $bruto = @file_get_contents($url, false, $ctx);
    if ($bruto === false) return null;
    $j = json_decode($bruto, true);
    return (is_array($j) && !empty($j['data'])) ? $j['data'] : null;
}

/** Compara nomes ignorando maiúscula, acento e pontuação. */
function mesmoNome(string $a, string $b): bool
{
    $limpa = function (string $s): string {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        return preg_replace('/[^a-z0-9]+/', '', strtolower($s)) ?? '';
    };
    return $limpa($a) === $limpa($b);
}

$faltando = q('SELECT num, nome, passcode FROM cartas
               WHERE texto IS NULL OR texto = "" ORDER BY num');
echo 'Cartas ainda sem texto: ' . count($faltando) . PHP_EOL . PHP_EOL;

if ($soListar) {
    foreach ($faltando as $c) {
        printf("  %3d  %-42s  passcode: %s\n", $c['num'], $c['nome'], $c['passcode'] ?: '(vazio)');
    }
    exit(PHP_EOL . "Nada foi alterado — isto foi só a listagem.\n");
}

$achou = 0; $arte = 0; $naoAchou = [];

foreach ($faltando as $i => $c) {
    $nome = (string)$c['nome'];

    // 1ª tentativa: nome exato. 2ª: busca por trecho, aceitando só quando
    // o nome devolvido é de fato o mesmo — busca solta casaria "Dark Plant"
    // com meia dúzia de cartas diferentes, e carta errada é pior que carta
    // faltando.
    $r = pegar($API . '?name=' . rawurlencode($nome));
    if (!$r) {
        $r = pegar($API . '?fname=' . rawurlencode($nome));
        if ($r) {
            $bom = null;
            foreach ($r as $cand) { if (mesmoNome((string)$cand['name'], $nome)) { $bom = $cand; break; } }
            $r = $bom ? [$bom] : null;
        }
    }
    usleep(220000);                       // bem abaixo do teto de 20/s

    if (!$r) { $naoAchou[] = $c['num'] . ' · ' . $nome; continue; }

    $api  = $r[0];
    $tipo = (string)($api['type'] ?? '');
    $pass = str_pad((string)$api['id'], 8, '0', STR_PAD_LEFT);

    ex('UPDATE cartas SET texto = ?, nome_pt = ?, passcode = ?, fusao = ?, ritual_monstro = ? WHERE num = ?', [
        (string)($api['desc'] ?? ''),
        (string)($api['name'] ?? ''),
        $pass,
        str_contains($tipo, 'Fusion') ? 1 : 0,
        (str_contains($tipo, 'Ritual') && str_contains($tipo, 'Monster')) ? 1 : 0,
        (int)$c['num'],
    ]);
    $achou++;
    $trocou = $pass !== (string)$c['passcode'] ? "  (passcode {$c['passcode']} → $pass)" : '';
    printf("  ok  %3d  %-40s%s\n", $c['num'], $nome, $trocou);

    if ($comImagens) {
        $destino = $pastaArte . '/' . $pass . '.jpg';
        if (!is_file($destino)) {
            $url = $api['card_images'][0]['image_url'] ?? '';
            if ($url) {
                $bin = @file_get_contents($url, false, stream_context_create([
                    'http' => ['timeout' => 30, 'header' => "User-Agent: YuGiUaiOh/1.0\r\n"]
                ]));
                if ($bin !== false && strlen($bin) > 2000) { file_put_contents($destino, $bin); $arte++; }
            }
            usleep(150000);
        }
    }
}

echo PHP_EOL . "Recuperadas nesta passada: $achou" . ($comImagens ? "  ·  artes novas: $arte" : '') . PHP_EOL;
echo 'Com texto no banco agora: ' . qv('SELECT COUNT(*) FROM cartas WHERE texto IS NOT NULL AND texto <> ""', [], 0) . ' de 722' . PHP_EOL;
echo 'Fusão: ' . qv('SELECT COUNT(*) FROM cartas WHERE fusao = 1', [], 0)
   . '  ·  Ritual: ' . qv('SELECT COUNT(*) FROM cartas WHERE ritual_monstro = 1', [], 0) . PHP_EOL;
echo 'Artes no disco: ' . count(glob($pastaArte . '/*.jpg')) . PHP_EOL;

if ($naoAchou) {
    echo PHP_EOL . 'Continuam sem correspondência (' . count($naoAchou) . "):\n  " . implode("\n  ", $naoAchou) . PHP_EOL;
    echo PHP_EOL . "Estas o site trata como blefe: aparecem pelo verso, ocupam zona e não mudam o jogo.\n";
}
