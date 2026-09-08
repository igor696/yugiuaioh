-- =====================================================================
--  YU GI UAI OH — estrutura do banco
--  MariaDB / MySQL 5.7+   ·   utf8mb4
--
--  Rode este arquivo UMA VEZ, no phpMyAdmin da Hostinger ou por linha
--  de comando, no banco que você criou no hPanel. Depois rode o
--  02_cartas.sql, que enche o catálogo das 722.
--
--  UMA OBSERVAÇÃO SOBRE O DESENHO:
--  quase toda tabela de posse tem a coluna `dificuldade` na chave.
--  Não é redundância — é a regra dura do projeto escrita no banco:
--  coleção, carteira, decks e progresso de uma dificuldade não
--  atravessam para outra. Se um dia alguém tirar essa coluna, o jogo
--  inteiro muda de sentido.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------- pessoas
CREATE TABLE IF NOT EXISTS duelistas (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome               VARCHAR(32)  NOT NULL,           -- 32 e não 24: o nome do dono da casa tem 29
  senha_hash         VARCHAR(255) NOT NULL,
  numero             CHAR(7)      NOT NULL,           -- #YU0001
  avatar             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  nivel              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  papel              ENUM('jogador','farao') NOT NULL DEFAULT 'jogador',
  dificuldade_atual  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  convidado_por      INT UNSIGNED NULL,
  ativo              TINYINT(1)   NOT NULL DEFAULT 1,
  erros_senha        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  travado_ate        DATETIME NULL,
  presenca_dia       DATE NULL,
  presenca_seq       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sequencia_vitorias SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  tutorial_visto     TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em          DATETIME NOT NULL,
  ultimo_acesso      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nome (nome),
  UNIQUE KEY uq_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS convites (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo     VARCHAR(16) NOT NULL,
  criado_por INT UNSIGNED NULL,
  usado_por  INT UNSIGNED NULL,
  criado_em  DATETIME NOT NULL,
  usado_em   DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_codigo (codigo),
  KEY k_criador (criado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  tipo        ENUM('sistema','mascote','farao') NOT NULL DEFAULT 'sistema',
  titulo      VARCHAR(120) NOT NULL,
  corpo       TEXT NOT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lida_em     DATETIME NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, lida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ajustes (
  chave VARCHAR(48) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ economia
CREATE TABLE IF NOT EXISTS carteiras (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  oneub       INT NOT NULL DEFAULT 0,
  selos       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (duelista_id, dificuldade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extrato (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  valor       INT NOT NULL,
  motivo      VARCHAR(24) NOT NULL,
  detalhe     VARCHAR(180) NOT NULL DEFAULT '',
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, dificuldade, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------- cartas
CREATE TABLE IF NOT EXISTS cartas (
  num            SMALLINT UNSIGNED NOT NULL,          -- 1 a 722
  nome           VARCHAR(120) NOT NULL,
  nome_pt        VARCHAR(120) NULL,
  tipo           ENUM('Monster','Magic','Equip','Field','Ritual','Trap') NOT NULL,
  sub            VARCHAR(24) NOT NULL DEFAULT '',
  nivel          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  atk            SMALLINT NOT NULL DEFAULT 0,
  def            SMALLINT NOT NULL DEFAULT 0,
  passcode       CHAR(8) NULL,
  fusao          TINYINT(1) NOT NULL DEFAULT 0,
  ritual_monstro TINYINT(1) NOT NULL DEFAULT 0,
  colecao        VARCHAR(16) NOT NULL DEFAULT 'aurora',
  texto          TEXT NULL,                           -- vem do importador
  PRIMARY KEY (num),
  KEY k_passcode (passcode),
  KEY k_tipo (tipo, sub),
  KEY k_atk (atk),
  KEY k_colecao (colecao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posse_cartas (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  carta_num   SMALLINT UNSIGNED NOT NULL,
  quantidade  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (duelista_id, dificuldade, carta_num),
  KEY k_carta (carta_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS colecao_livre (
  duelista_id INT UNSIGNED NOT NULL,
  carta_num   SMALLINT UNSIGNED NOT NULL,
  quantidade  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (duelista_id, carta_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------- decks
CREATE TABLE IF NOT EXISTS decks (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  nome        VARCHAR(40) NOT NULL,
  ativo       TINYINT(1) NOT NULL DEFAULT 0,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, dificuldade, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deck_cartas (
  deck_id    INT UNSIGNED NOT NULL,
  carta_num  SMALLINT UNSIGNED NOT NULL,
  parte      ENUM('principal','adicional','auxiliar') NOT NULL DEFAULT 'principal',
  quantidade TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (deck_id, carta_num, parte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS decks_reais (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  nome        VARCHAR(40) NOT NULL,
  nota        TEXT NULL,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------- campanha
CREATE TABLE IF NOT EXISTS progresso (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (duelista_id, dificuldade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oponentes_vencidos (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  oponente    TINYINT UNSIGNED NOT NULL,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (duelista_id, dificuldade, oponente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chefoes_vencidos (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  chefe       TINYINT UNSIGNED NOT NULL,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (duelista_id, dificuldade, chefe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oponente_estado (
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  oponente    TINYINT UNSIGNED NOT NULL,
  slot        TINYINT UNSIGNED NOT NULL DEFAULT 1,   -- qual dos 5 decks ele traz
  duelos      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (duelista_id, dificuldade, oponente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duelos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  modo        ENUM('campanha','lan','online') NOT NULL DEFAULT 'campanha',
  dificuldade TINYINT UNSIGNED NOT NULL,
  alvo_tipo   VARCHAR(12) NOT NULL DEFAULT 'oponente',
  alvo_n      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  alvo_nome   VARCHAR(80) NOT NULL DEFAULT '',
  resultado   ENUM('vitoria','derrota') NOT NULL,
  sem_dano    TINYINT(1) NOT NULL DEFAULT 0,
  slot        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, modo, resultado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- relíquias
CREATE TABLE IF NOT EXISTS posse_reliquias (
  duelista_id INT UNSIGNED NOT NULL,
  reliquia    VARCHAR(12) NOT NULL,   -- olho, argola, balanca, chave, cetro, colar, enigma
  obtida_em   DATETIME NOT NULL,
  equipada    TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (duelista_id, reliquia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pecas_enigma (
  duelista_id INT UNSIGNED NOT NULL,
  chefe       TINYINT UNSIGNED NOT NULL,
  obtida_em   DATETIME NOT NULL,
  PRIMARY KEY (duelista_id, chefe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------- conquistas
CREATE TABLE IF NOT EXISTS posse_conquistas (
  duelista_id  INT UNSIGNED NOT NULL,
  conquista    TINYINT UNSIGNED NOT NULL,
  concluida_em DATETIME NOT NULL,
  vista_em     DATETIME NULL,
  PRIMARY KEY (duelista_id, conquista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas (
  duelista_id INT UNSIGNED NOT NULL,
  marca       VARCHAR(24) NOT NULL,   -- tributo, cadeia3, ritual, fusao, deck40, semdano
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (duelista_id, marca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------- loja e cofre
CREATE TABLE IF NOT EXISTS boosters (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  colecao     VARCHAR(16) NOT NULL,
  cartas      VARCHAR(255) NOT NULL,   -- JSON com os números sorteados
  super       TINYINT(1) NOT NULL DEFAULT 0,
  criado_em   DATETIME NOT NULL,
  aberto_em   DATETIME NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, dificuldade, aberto_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  slug        VARCHAR(24) NOT NULL,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY k_dono (duelista_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------- jogos salvos
CREATE TABLE IF NOT EXISTS jogos_salvos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id INT UNSIGNED NOT NULL,
  dificuldade TINYINT UNSIGNED NOT NULL,
  slot        TINYINT UNSIGNED NOT NULL,
  nome        VARCHAR(40) NOT NULL,
  retrato     MEDIUMTEXT NOT NULL,     -- JSON com o progresso daquela dificuldade
  automatico  TINYINT(1) NOT NULL DEFAULT 0,
  salvo_em    DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lugar (duelista_id, dificuldade, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- LAN/online
CREATE TABLE IF NOT EXISTS salas (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo       VARCHAR(12) NOT NULL,
  tipo         ENUM('lan','online') NOT NULL DEFAULT 'lan',
  dono_id      INT UNSIGNED NOT NULL,
  convidado_id INT UNSIGNED NULL,
  dificuldade  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criada_em    DATETIME NOT NULL,
  entrou_em    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_codigo (codigo),
  KEY k_tipo (tipo, convidado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------ presentes e suporte
CREATE TABLE IF NOT EXISTS presentes (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo    VARCHAR(24) NOT NULL,
  oneub     INT NOT NULL DEFAULT 0,
  booster   TINYINT(1) NOT NULL DEFAULT 0,
  usos      INT NOT NULL DEFAULT 0,
  usos_max  INT NOT NULL DEFAULT 0,       -- 0 = sem limite
  criado_em DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presentes_usados (
  presente_id INT UNSIGNED NOT NULL,
  duelista_id INT UNSIGNED NOT NULL,
  criado_em   DATETIME NOT NULL,
  PRIMARY KEY (presente_id, duelista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suporte (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  duelista_id   INT UNSIGNED NOT NULL,
  titulo        VARCHAR(80) NOT NULL,
  corpo         TEXT NOT NULL,
  resposta      TEXT NULL,
  criado_em     DATETIME NOT NULL,
  respondido_em DATETIME NULL,
  PRIMARY KEY (id),
  KEY k_aberto (resposta(1), id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------- ajustes iniciais
INSERT INTO ajustes (chave, valor) VALUES
  ('tutorial_ativo', '1'),
  ('cadastro_aberto', '1')
ON DUPLICATE KEY UPDATE valor = valor;

SET FOREIGN_KEY_CHECKS = 1;
