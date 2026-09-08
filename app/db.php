<?php
/** Banco de dados (PDO). Uma conexão só, aberta na primeira vez que alguém pedir. */
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    global $CFG;
    $d = $CFG['db'];
    $dsn = !empty($d['socket'])
        ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $d['socket'], $d['nome'])
        : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $d['host'], $d['porta'] ?? 3306, $d['nome']);
    try {
        $pdo = new PDO($dsn, $d['user'], $d['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        error_log('Falha ao conectar no banco: ' . $e->getMessage());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "\nNão consegui falar com o banco.\n  banco: {$d['nome']}\n  usuário: {$d['user']}\n  MySQL disse: {$e->getMessage()}\n");
            exit(1);
        }
        http_response_code(500);
        exit('Não consegui falar com o banco de dados. Confira o app/config.php.');
    }
    return $pdo;
}

function q(string $sql, array $p = []): array   { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function q1(string $sql, array $p = []): ?array { $s = db()->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ?: null; }
function qv(string $sql, array $p = [], $padrao = null) { $s = db()->prepare($sql); $s->execute($p); $v = $s->fetchColumn(); return $v === false ? $padrao : $v; }
function ex(string $sql, array $p = []): int    { $s = db()->prepare($sql); $s->execute($p); return $s->rowCount(); }
function ultimoId(): int { return (int)db()->lastInsertId(); }

/** Existe a tabela? Usado pelo diagnóstico e pelas migrações. */
function temTabela(string $t): bool
{
    return (bool)qv('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$t], 0);
}
