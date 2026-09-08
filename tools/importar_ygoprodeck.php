<?php
/**
 * IMPORTADOR DO YGOPRODECK — texto de regras, tradução e artes.
 *
 * Rode uma vez, pela linha de comando, depois do 01 e do 02:
 *
 *     php tools/importar_ygoprodeck.php            (texto + classificação)
 *     php tools/importar_ygoprodeck.php --imagens  (baixa as artes)
 *     php tools/importar_ygoprodeck.php --pt       (nome em português)
 *
 * =====================================================================
 *  POR QUE AS ARTES FICAM AQUI DENTRO, E NÃO NO SERVIDOR DELES
 * =====================================================================
 *  O YGOPRODeck PROÍBE hotlink de imagem e bloqueia o IP de quem
 *  insiste — e num servidor compartilhado como o da Hostinger, o IP
 *  bloqueado não é só o seu. Por isso a arte é baixada uma vez e
 *  servida da nossa pasta public_html/cache/cartas.
 *
 *  O limite deles é de 20 requisições por segundo. Este script pede
 *  em lotes e dorme entre eles, com folga. Não tire o sleep.
 * =====================================================================
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { exit("Este script roda só pela linha de comando.\n"); }
require __DIR__ . '/../app/bootstrap.php';

$API      = 'https://db.ygoprodeck.com/api/v7/cardinfo.php';
$comImagens = in_array('--imagens', $argv, true);
$comPt      = in_array('--pt', $argv, true);
$pastaArte  = YU_RAIZ . '/public_html/cache/cartas';
if (!is_dir($pastaArte)) { mkdir($pastaArte, 0755, true); }

function buscar(string $url): ?array
{
    $ctx = stream_context_create(['http' => [
        'timeout' => 25,
        'header'  => "User-Agent: YuGiUaiOh/1.0 (projeto de fã; contato pelo site)\r\n",
    ]]);
    $bruto = @file_get_contents($url, false, $ctx);
    if ($bruto === false) return null;
    $j = json_decode($bruto, true);
    return is_array($j) ? $j : null;
}

/* ------------------------------------------------------------------ 1
   O TEXTO E A CLASSIFICAÇÃO.
   Pedimos por passcode em lotes; o campo `type` do YGOPRODeck é o que
   diz se a carta é "Fusion Monster" ou "Ritual Monster" — é dele que
   saem as colunas `fusao` e `ritual_monstro`, que o nosso arquivo de
   origem não tinha como saber. */
$cartas = q('SELECT num, nome, passcode FROM cartas WHERE passcode IS NOT NULL AND passcode <> "" ORDER BY num');
echo 'Cartas com passcode: ' . count($cartas) . PHP_EOL;

$lotes = array_chunk($cartas, 40);
$achadas = 0; $perdidas = [];

foreach ($lotes as $i => $lote) {
    $ids = implode(',', array_map(fn($c) => ltrim((string)$c['passcode'], '0'), $lote));
    $url = $API . '?id=' . urlencode($ids) . ($comPt ? '&language=pt' : '');
    $r = buscar($url);
    if (!$r || empty($r['data'])) {
        echo "  lote " . ($i + 1) . '/' . count($lotes) . " — nada voltou\n";
        sleep(2);
        continue;
    }
    foreach ($r['data'] as $c) {
        $pass = str_pad((string)$c['id'], 8, '0', STR_PAD_LEFT);
        $tipo = (string)($c['type'] ?? '');
        ex('UPDATE cartas SET texto = ?, fusao = ?, ritual_monstro = ?' . ($comPt ? ', nome_pt = ?' : '') . ' WHERE passcode = ?',
           $comPt
             ? [(string)($c['desc'] ?? ''), str_contains($tipo, 'Fusion') ? 1 : 0, str_contains($tipo, 'Ritual') && str_contains($tipo, 'Monster') ? 1 : 0, (string)($c['name'] ?? ''), $pass]
             : [(string)($c['desc'] ?? ''), str_contains($tipo, 'Fusion') ? 1 : 0, str_contains($tipo, 'Ritual') && str_contains($tipo, 'Monster') ? 1 : 0, $pass]);
        $achadas++;

        /* ---------------------------------------------------------- 2
           A ARTE. Uma requisição por carta, com sleep — e só se ainda
           não estiver no disco, para o script poder ser interrompido e
           retomado sem começar do zero. */
        if ($comImagens) {
            $destino = $pastaArte . '/' . $pass . '.jpg';
            if (!is_file($destino)) {
                $urlImg = $c['card_images'][0]['image_url'] ?? '';
                if ($urlImg) {
                    $bin = @file_get_contents($urlImg, false, stream_context_create([
                        'http' => ['timeout' => 30, 'header' => "User-Agent: YuGiUaiOh/1.0\r\n"]
                    ]));
                    if ($bin !== false && strlen($bin) > 2000) { file_put_contents($destino, $bin); }
                }
                usleep(120000);   // ~8 por segundo: bem abaixo do teto de 20
            }
        }
    }
    echo '  lote ' . ($i + 1) . '/' . count($lotes) . " ok\n";
    sleep(1);
}

/* ---- o que não casou -------------------------------------------- */
foreach ($cartas as $c) {
    if (!qv('SELECT COUNT(*) FROM cartas WHERE num = ? AND texto IS NOT NULL AND texto <> ""', [(int)$c['num']], 0)) {
        $perdidas[] = $c['num'] . ' · ' . $c['nome'];
    }
}

echo PHP_EOL . "Atualizadas: $achadas" . PHP_EOL;
echo 'Com texto no banco: ' . qv('SELECT COUNT(*) FROM cartas WHERE texto IS NOT NULL AND texto <> ""', [], 0) . ' de 722' . PHP_EOL;
echo 'Marcadas como Fusão: ' . qv('SELECT COUNT(*) FROM cartas WHERE fusao = 1', [], 0) . PHP_EOL;
echo 'Marcadas como Ritual: ' . qv('SELECT COUNT(*) FROM cartas WHERE ritual_monstro = 1', [], 0) . PHP_EOL;
if ($comImagens) { echo 'Artes no disco: ' . count(glob($pastaArte . '/*.jpg')) . PHP_EOL; }
if ($perdidas) {
    echo PHP_EOL . 'Sem correspondência (' . count($perdidas) . "):\n  " . implode("\n  ", array_slice($perdidas, 0, 40)) . PHP_EOL;
    echo "As 24 cartas sem passcode na lista de origem entram aqui — elas ficam sem texto e sem arte até alguém preencher o passcode à mão.\n";
}
