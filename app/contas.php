<?php
/**
 * CRIAÇÃO DE DUELISTA, NÚMERO #YU, CÓDIGOS DE CONVITE E PERFIL.
 *
 * O cadastro é aberto mas travado por código de convite, igual ao MTM:
 * quem já joga gera o código e passa para quem quer entrar. Não há
 * e-mail em lugar nenhum.
 */
declare(strict_types=1);

const NOME_MIN = 3;
const NOME_MAX = 32;

function nomeValido(string $nome): ?string
{
    $nome = trim($nome);
    $tam  = mb_strlen($nome);
    if ($tam < NOME_MIN) return 'O nome precisa de pelo menos ' . NOME_MIN . ' letras.';
    if ($tam > NOME_MAX) return 'O nome passa de ' . NOME_MAX . ' letras.';
    if (!preg_match('/^[\p{L}\p{N} .\'\-]+$/u', $nome)) return 'Use só letras, números, espaço, ponto, hífen e apóstrofo.';
    if (qv('SELECT COUNT(*) FROM duelistas WHERE nome = ?', [$nome], 0)) return 'Já existe um duelista com esse nome.';
    return null;
}

function proximoNumero(): string
{
    $n = (int)qv("SELECT COALESCE(MAX(CAST(SUBSTRING(numero,4) AS UNSIGNED)),0) FROM duelistas", [], 0);
    return '#YU' . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT);
}

/**
 * Cria o duelista.
 * Aqui acontece a coisa que só existe neste jogo: o sorteio da PRIMEIRA
 * RELÍQUIA. Sorteia entre as seis — o Enigma nunca entra, ele só vem
 * quando a campanha é zerada.
 */
function criarDuelista(string $nome, string $senha, string $codigoConvite, int $avatar = 1): array
{
    global $CFG;
    if ($erro = nomeValido($nome)) return ['erro' => $erro];
    $min = (int)($CFG['seguranca']['senha_min'] ?? 8);
    if (mb_strlen($senha) < $min) return ['erro' => "A senha precisa de pelo menos {$min} caracteres."];

    $convite = null;
    if (!empty($CFG['seguranca']['exige_convite'])) {
        $codigoConvite = strtoupper(trim($codigoConvite));
        $convite = q1('SELECT * FROM convites WHERE codigo = ? AND usado_por IS NULL', [$codigoConvite]);
        if (!$convite) return ['erro' => 'Código de convite inválido ou já usado.'];
    }

    $reliquia = reliquiasSorteaveis()[array_rand(reliquiasSorteaveis())];

    db()->beginTransaction();
    try {
        ex('INSERT INTO duelistas (nome, senha_hash, numero, avatar, nivel, papel, convidado_por, criado_em, ultimo_acesso)
            VALUES (?,?,?,?,?,?,?,NOW(),NOW())',
            [trim($nome), password_hash($senha, PASSWORD_DEFAULT), proximoNumero(),
             max(1, min(15, $avatar)), 1, 'jogador', $convite['criado_por'] ?? null]);
        $id = ultimoId();

        if ($convite) {
            ex('UPDATE convites SET usado_por = ?, usado_em = NOW() WHERE codigo = ?', [$id, $convite['codigo']]);
        }
        // carteira e progresso de cada dificuldade nascem separados — a regra
        // dura do projeto: nada passa de uma dificuldade para a outra.
        foreach (array_keys(dificuldades()) as $d) {
            ex('INSERT INTO carteiras (duelista_id, dificuldade, oneub, selos) VALUES (?,?,?,0)', [$id, $d, $d === 1 ? 300 : 0]);
            ex('INSERT INTO progresso (duelista_id, dificuldade) VALUES (?,?)', [$id, $d]);
        }
        ex('INSERT INTO posse_reliquias (duelista_id, reliquia, obtida_em, equipada) VALUES (?,?,NOW(),1)', [$id, $reliquia]);
        // três códigos de convite para repassar
        for ($i = 0; $i < 3; $i++) { gerarConvite($id); }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        error_log('criarDuelista: ' . $e->getMessage());
        return ['erro' => 'Não consegui criar o duelista. Tente de novo.'];
    }

    $r = reliquiasTodas()[$reliquia];
    mandarMensagem($id, 'Bem-vindo ao Bazar de Tebas',
        "Eu sou o Indawora, o Duelista. Vou ficar por aqui.\n\n"
      . "Você chegou com uma relíquia na mão: o {$r['nome']}. Não foi escolha sua nem minha — "
      . "essas coisas escolhem sozinhas. Ela já está equipada.\n\n"
      . "As outras seis estão com os seis guardiões da campanha. E o Enigma do Milênio está "
      . "partido em pedaços; só quem zerar tudo junta de volta.\n\n"
      . "Antes de qualquer duelo, abre o MANUAL. É rápido, e é o que separa quem joga de quem chuta.",
        'mascote');

    return ['ok' => true, 'id' => $id, 'reliquia' => $reliquia];
}

// ------------------------------------------------------------------ convites
function gerarConvite(int $criadoPor): string
{
    do {
        $c = 'UAI-' . strtoupper(bin2hex(random_bytes(3)));
    } while (qv('SELECT COUNT(*) FROM convites WHERE codigo = ?', [$c], 0));
    ex('INSERT INTO convites (codigo, criado_por, criado_em) VALUES (?,?,NOW())', [$c, $criadoPor]);
    return $c;
}
function meusConvites(int $id): array
{
    return q('SELECT c.*, d.nome AS nome_usado FROM convites c
              LEFT JOIN duelistas d ON d.id = c.usado_por
              WHERE c.criado_por = ? ORDER BY c.usado_em IS NOT NULL, c.id DESC', [$id]);
}

// -------------------------------------------------------------------- perfil
function avatarUrl(?array $d): string
{
    $n = str_pad((string)max(1, min(15, (int)($d['avatar'] ?? 1))), 2, '0', STR_PAD_LEFT);
    $p = '/assets/img/avatares/avatar-' . $n . '.png';
    return temArte($p) ? $p : '';
}
function iniciais(?array $d): string
{
    $partes = preg_split('/\s+/', trim((string)($d['nome'] ?? '?'))) ?: ['?'];
    $s = mb_strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) $s .= mb_strtoupper(mb_substr(end($partes), 0, 1));
    return $s;
}
function nivelDe(?array $d): int
{
    if (!$d) return 1;
    if (($d['papel'] ?? '') === 'farao') return 25;
    return max(1, min(24, (int)$d['nivel']));
}

/** O avatar está liberado para este duelista? */
function avatarLiberado(int $n, array $d): bool
{
    $a = avatares()[$n] ?? null;
    if (!$a) return false;
    if ($a['trava'] === '') return true;
    if (ehFarao()) return true;
    [$tipo, $valor] = array_pad(explode(':', $a['trava']), 2, '');
    return match ($tipo) {
        'nivel'     => nivelDe($d) >= (int)$valor,
        'conquista' => (int)qv('SELECT COUNT(*) FROM posse_conquistas WHERE duelista_id = ? AND conquista >= ?', [$d['id'], (int)$valor], 0) > 0,
        'chefoes'   => (int)qv('SELECT COUNT(DISTINCT chefe) FROM chefoes_vencidos WHERE duelista_id = ?', [$d['id']], 0) >= (int)$valor,
        'campanha'  => (int)qv('SELECT COUNT(*) FROM posse_reliquias WHERE duelista_id = ? AND reliquia = "enigma"', [$d['id']], 0) > 0,
        default     => false,
    };
}

/** Presença diária: 7 dias seguidos, com o ganho subindo. */
function registrarPresenca(int $id): void
{
    $hoje = date('Y-m-d');
    $d = q1('SELECT presenca_dia, presenca_seq FROM duelistas WHERE id = ?', [$id]);
    if (!$d) return;
    if ((string)$d['presenca_dia'] === $hoje) return;

    $ontem = date('Y-m-d', strtotime('-1 day'));
    $seq   = ((string)$d['presenca_dia'] === $ontem) ? min(7, (int)$d['presenca_seq'] + 1) : 1;
    ex('UPDATE duelistas SET presenca_dia = ?, presenca_seq = ? WHERE id = ?', [$hoje, $seq, $id]);

    // Do 2º dia em diante rende ONEUB. Cai na carteira da dificuldade 1,
    // que é a única que todo mundo tem aberta desde o começo.
    $premio = [1 => 0, 2 => 20, 3 => 30, 4 => 50, 5 => 80, 6 => 120, 7 => 200][$seq] ?? 0;
    if ($premio > 0) {
        creditar($id, 1, $premio, 'presenca', "Dia {$seq} seguido no bazar");
        if ($seq === 7) {
            mandarMensagem($id, 'Sete dias seguidos', 'Sete dias seguidos. O Egito inteiro anotou.', 'mascote');
        }
    }
}

/**
 * A DIFICULDADE EM QUE O DUELISTA ESTÁ AGORA.
 *
 * Guardada na sessão porque ela troca o site inteiro de contexto:
 * carteira, coleção, decks, progresso e loja. Cada dificuldade é um jogo
 * separado, e essa é a regra dura do projeto — nada atravessa.
 */
function difAtual(): int
{
    $d = (int)($_SESSION['dificuldade'] ?? 0);
    if ($d < 1 || $d > 5) {
        $eu = duelistaAtual();
        $d = $eu ? max(1, min(5, (int)($eu['dificuldade_atual'] ?? 1))) : 1;
        $_SESSION['dificuldade'] = $d;
    }
    return $d;
}
function trocarDificuldade(int $d): void
{
    $d = max(1, min(5, $d));
    $_SESSION['dificuldade'] = $d;
    $eu = duelistaAtual();
    if ($eu) { ex('UPDATE duelistas SET dificuldade_atual = ? WHERE id = ?', [$d, (int)$eu['id']]); }
}
