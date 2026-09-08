<?php
/**
 * AS TABELAS FIXAS DO JOGO.
 *
 * Tudo o que não muda por jogador mora aqui, em PHP, e não no banco:
 * dificuldades, níveis, relíquias, conquistas, avatares, mercadores,
 * coleções e o elenco da campanha. Assim o banco guarda só o que é do
 * jogador — progresso, posse e saldo — e uma correção de texto não
 * precisa de migração.
 */
declare(strict_types=1);

// ------------------------------------------------------------ dificuldades
function dificuldades(): array
{
    return [
        1 => ['slug'=>'muito-facil','nome'=>'Muito Fácil','cidade'=>'Os Aprendizes do Oásis',
              'cor'=>'#7BC47F','oneub'=>20,  'booster'=>5,  'carta'=>10, 'pasta'=>'n1'],
        2 => ['slug'=>'facil','nome'=>'Fácil','cidade'=>'A Caravana de Bronze',
              'cor'=>'#2EC4B6','oneub'=>45,  'booster'=>10, 'carta'=>15, 'pasta'=>'n2'],
        3 => ['slug'=>'normal','nome'=>'Normal','cidade'=>'A Guarda do Nilo Mineiro',
              'cor'=>'#2B5CA8','oneub'=>90,  'booster'=>15, 'carta'=>20, 'pasta'=>'n3'],
        4 => ['slug'=>'dificil','nome'=>'Difícil','cidade'=>'A Corte de Ouro',
              'cor'=>'#B4373A','oneub'=>170, 'booster'=>25, 'carta'=>25, 'pasta'=>'n4'],
        5 => ['slug'=>'muito-dificil','nome'=>'Muito Difícil','cidade'=>'As Sombras do Além-Túmulo',
              'cor'=>'#6B2FA0','oneub'=>320, 'booster'=>40, 'carta'=>30, 'pasta'=>'n5'],
    ];
}
function dificuldade(int $d): array { return dificuldades()[$d] ?? dificuldades()[1]; }
function dificuldadePorSlug(string $s): int
{
    foreach (dificuldades() as $i => $d) { if ($d['slug'] === $s) return $i; }
    return 1;
}

// ------------------------------------------------------------------ níveis
/** 24 níveis + o 25, que é o do Faraó. O emblema é desenhado em SVG. */
function niveis(): array
{
    return [
        1=>'Servo do Oásis',       2=>'Carregador de Papiro', 3=>'Aprendiz de Escriba',
        4=>'Escriba de Bolso',     5=>'Guarda de Portão',     6=>'Batedor de Cobre',
        7=>'Lanceiro do Vau',      8=>'Falcoeiro',            9=>'Sacerdote Menor',
        10=>'Leitor de Estrelas',  11=>'Sentinela da Noite',  12=>'Mestre de Obras',
        13=>'Ourives do Nilo',     14=>'Encantador de Naja',  15=>'Capitão de Escudo',
        16=>'Sumo Escriba',        17=>'Arquiteto de Tumba',  18=>'General do Delta',
        19=>'Vizir de Ouro',       20=>'Guardião de Portão',  21=>'Pesador de Corações',
        22=>'Sacerdote do Além',   23=>'Herdeiro do Nemes',   24=>'Duelista do Milênio',
        25=>'Faraó',
    ];
}
function tituloNivel(int $n): string { return niveis()[max(1, min(25, $n))] ?? 'Duelista'; }

/** O metal do emblema muda de faixa em faixa — é o que dá a leitura de longe. */
function metalNivel(int $n): array
{
    if ($n >= 25) return ['#D4AF37', '#6B2FA0'];
    if ($n >= 21) return ['#D4AF37', '#2B5CA8'];
    if ($n >= 17) return ['#D4AF37', '#8A6B1E'];
    if ($n >= 13) return ['#D8D8DC', '#6E6E78'];
    if ($n >= 9)  return ['#C08A4A', '#7A5427'];
    if ($n >= 5)  return ['#B87333', '#6E4420'];
    return ['#B9A88A', '#6B5C46'];
}

// --------------------------------------------------------------- relíquias
function reliquiasTodas(): array
{
    return [
        'olho' => ['nome'=>'Olho do Milênio','arquivo'=>'reliquia-olho',
            'poder'=>'Ler a mente','descricao'=>'Revela a mão do oponente por 6 segundos.',
            'quando'=>'1×/Duelo, na sua Fase Principal 1'],
        'argola' => ['nome'=>'Anel do Milênio','arquivo'=>'reliquia-argola',
            'poder'=>'Orientação','descricao'=>'Procura 1 carta no Deck e põe no topo. Embaralha depois.',
            'quando'=>'1×/Duelo, na sua Fase Principal 1'],
        'balanca' => ['nome'=>'Balança do Milênio','arquivo'=>'reliquia-balanca',
            'poder'=>'Julgamento de Maat','descricao'=>'Se a soma do ATK no seu campo for menor que a do oponente, compre 1 carta.',
            'quando'=>'1×/Duelo, no início da Fase Principal 1'],
        'chave' => ['nome'=>'Chave do Milênio','arquivo'=>'reliquia-chave',
            'poder'=>'Abrir a alma','descricao'=>'Revela para você todas as Mágicas e Armadilhas baixadas do oponente.',
            'quando'=>'1×/Duelo, em qualquer fase sua'],
        'cetro' => ['nome'=>'Vara do Milênio','arquivo'=>'reliquia-cetro',
            'poder'=>'Controle','descricao'=>'O oponente pula a Fase de Batalha do próximo turno dele.',
            'quando'=>'1×/Duelo · só na CAMPANHA'],
        'colar' => ['nome'=>'Colar do Milênio','arquivo'=>'reliquia-colar',
            'poder'=>'Ver o tempo','descricao'=>'Olhe as 3 cartas do topo do seu Deck e reordene-as.',
            'quando'=>'1×/Duelo, na Fase de Apoio'],
        'enigma' => ['nome'=>'Enigma do Milênio','arquivo'=>'reliquia-enigma',
            'poder'=>'Desejo concedido','descricao'=>'Reusa uma habilidade de relíquia já gasta no Duelo. Passivo: +10% de ONEUB em toda vitória.',
            'quando'=>'1×/Match · só quem zerou a campanha'],
    ];
}
/** As seis que podem ser sorteadas no começo — o Enigma nunca entra. */
function reliquiasSorteaveis(): array
{
    return ['olho','argola','balanca','chave','cetro','colar'];
}

// ----------------------------------------------------------------- chefões
function chefoes(): array
{
    return [
      1 => ['slug'=>'escriba-supremo','arquivo'=>'chefao-01-escriba-supremo',
            'nome'=>'Ankh-Uai, o Escriba do Silêncio','reliquia'=>'argola','cor'=>'#2EC4B6',
            'eixo'=>'Controle e armadilhas',
            'lore'=>'Foi ele quem copiou as regras que você está aprendendo — palavra por palavra, num papiro que ninguém mais consegue ler. Passou tanto tempo escrevendo sobre duelos que se convenceu de que jogar é a parte mais grosseira da coisa. Nunca ataca primeiro: deixa você fazer tudo, anota seus erros e devolve com uma armadilha que você mesmo entregou.',
            'deboche'=>'Já anotei o seu nome na lista dos que não voltaram. Se ganhar, eu risco. Não vou precisar de borracha.'],
      2 => ['slug'=>'dama-dos-espelhos','arquivo'=>'chefao-02-dama-dos-espelhos',
            'nome'=>'Nefer-Trindade, a Sacerdotisa do Espelho','reliquia'=>'colar','cor'=>'#2B5CA8',
            'eixo'=>'Rituais e cópia',
            'lore'=>'Diz que enxerga três minutos à frente e por isso já venceu você antes de você sentar. Duela de olhos fechados. Sua especialidade é o Ritual: paga o preço alto, entrega tributos sem piscar e traz para o campo o monstro que ela viu no seu futuro. Fala do jogador sempre no passado.',
            'deboche'=>'Você vai baixar a carta da direita. Eu já vi. Pode baixar a da esquerda, se quiser me dar razão duas vezes.'],
      3 => ['slug'=>'barqueiro-do-nilo','arquivo'=>'chefao-03-barqueiro-do-nilo',
            'nome'=>'Sobek-Bão, o Crocodilo do Rio das Velhas','reliquia'=>'balanca','cor'=>'#5E8C3A',
            'eixo'=>'Bestas e força bruta',
            'lore'=>'O maior duelista do interior, segundo ele mesmo. Ri alto, come no meio do duelo, chama todo mundo de "meu fi". Não tem estratégia nenhuma: tem monstros grandes demais. Acha graça em você calcular ATK e DEF antes de atacar — ele nunca calculou nada na vida e está invicto na várzea há doze anos.',
            'deboche'=>'Ô trem bão, ocê contando estrelinha na carta. Conta não, meu fi, que o meu bicho não sabe ler.'],
      4 => ['slug'=>'ferreiro','arquivo'=>'chefao-04-ferreiro',
            'nome'=>'Kefer-Amon, o Ourives de Ouro Preto','reliquia'=>'chave','cor'=>'#C98A2E',
            'eixo'=>'Máquinas e equipamentos',
            'lore'=>'Fundiu a própria mão direita em ouro para não errar mais um martelo. Constrói monstros em vez de invocá-los, e trata Mágicas de Equipamento como peças de encaixe. Para ele, o seu Deck é matéria-prima mal aproveitada. Ofereceu comprar sua coleção inteira antes do duelo, "pelo peso".',
            'deboche'=>'Seu Deck tem quarenta cartas e nenhuma serventia. Me dá que eu derreto e devolvo em forma de alguma coisa útil. Um prego, quem sabe.'],
      5 => ['slug'=>'saqueador-do-deserto','arquivo'=>'chefao-05-saqueador-do-deserto',
            'nome'=>'Set-Mineiro, o Vento da Serra','reliquia'=>'cetro','cor'=>'#B4373A',
            'eixo'=>'Velocidade e ataque direto',
            'lore'=>'Não senta. Duela em pé, andando em volta da mesa. Acha o turno lento demais, a Fase de Apoio uma perda de tempo e o jogador uma pessoa que pensa muito. Ganha no terceiro turno ou desiste de você por tédio. Foi o único a nunca ter perdido para Ankh-Uai — porque nunca esperou o escriba terminar de anotar.',
            'deboche'=>'Ocê ainda tá na Fase Principal 1? Ó, eu já ganhei, já tomei um café e voltei. Joga aí que eu não tenho o dia todo.'],
      6 => ['slug'=>'farao-das-trevas','arquivo'=>'chefao-06-farao-das-trevas',
            'nome'=>'Amset-Ra, o Faraó Sem Rosto','reliquia'=>'olho','cor'=>'#6B2FA0',
            'eixo'=>'Trevas e fusão',
            'lore'=>'Ninguém sabe se ele existiu. O nome foi raspado de todas as paredes, e é exatamente por isso que ele continua aqui: quem não tem nome não pode morrer. Espera no fim da campanha, sentado, sem se levantar nem para os duelos. Não debocha do jogador por diversão — debocha porque é a última coisa que ele consegue sentir.',
            'deboche'=>'Eu já derrotei você. Faz três mil anos. A diferença é que desta vez você vai lembrar.'],
    ];
}

// ---------------------------------------------------------------- avatares
function avatares(): array
{
    return [
        1=>['nome'=>'O Aprendiz de Linho','trava'=>''],
        2=>['nome'=>'A Escriba de Kohl','trava'=>''],
        3=>['nome'=>'O Guarda de Cobre','trava'=>''],
        4=>['nome'=>'A Pastora de Íbis','trava'=>''],
        5=>['nome'=>'O Barqueiro','trava'=>''],
        6=>['nome'=>'A Sacerdotisa de Lótus','trava'=>'nivel:4'],
        7=>['nome'=>'O Ferreiro de Ouro Preto','trava'=>'nivel:6'],
        8=>['nome'=>'A Falcoeira','trava'=>'nivel:8'],
        9=>['nome'=>'O Domador de Naja','trava'=>'nivel:10'],
        10=>['nome'=>'A Astrônoma','trava'=>'nivel:12'],
        11=>['nome'=>'O Campeão do Pátio','trava'=>'conquista:11'],
        12=>['nome'=>'A Guardiã de Alabastro','trava'=>'conquista:16'],
        13=>['nome'=>'O Chacal Pesador','trava'=>'chefoes:3'],
        14=>['nome'=>'A Máscara Dourada','trava'=>'chefoes:6'],
        15=>['nome'=>'Indawora','trava'=>'campanha'],
    ];
}

// -------------------------------------------------------------- mercadores
function mercadores(): array
{
    return [
      'sefu'  =>['nome'=>'Sefu, o Cambista de Papiro','vende'=>'Cartas avulsas','aba'=>'cartas',
                 'fala'=>'Papiro é memória, duelista. Escolhe uma e ela vira sua para sempre.'],
      'nubet' =>['nome'=>'Nubet, a Ourives do Nilo','vende'=>'Molduras e emblemas','aba'=>'cosmetico',
                 'fala'=>'O ouro não te faz ganhar. Faz te lembrarem de quando você ganhou.'],
      'hapi'  =>['nome'=>'Hapi-Uai, o Barqueiro do Rio das Almas','vende'=>'Boosters','aba'=>'booster',
                 'fala'=>'Chegou barco novo. Não pergunta de onde veio que eu não sei.'],
      'merit' =>['nome'=>'Merit-Ka, a Perfumista de Ébano','vende'=>'Itens de sorte','aba'=>'sorte',
                 'fala'=>'Isto aqui não muda a sua carta. Muda a hora em que ela aparece.'],
      'bastet'=>['nome'=>'Bastet-Mina, a Guardiã dos Gatos','vende'=>'Avatares','aba'=>'avatar',
                 'fala'=>'Escolhe com calma. O rosto é o que o adversário vê antes do seu Deck.'],
      'djed'  =>['nome'=>'Djed, o Pedreiro de Obeliscos','vende'=>'Melhorias','aba'=>'melhoria',
                 'fala'=>'Eu não vendo carta. Eu vendo lugar pra guardar as suas.'],
      'wadjet'=>['nome'=>'Wadjet, a Encantadora de Serpentes','vende'=>'Mercado sombrio','aba'=>'sombrio',
                 'fala'=>'Limitada é só um jeito educado de dizer cara. Você tem ONEUB?'],
      'thoth' =>['nome'=>'Thoth-Bão, o Contador de Escrivaninha','vende'=>'Cofre e extrato','aba'=>'cofre',
                 'fala'=>'Está tudo anotado. Absolutamente tudo. Inclusive o que você gastou ontem.'],
    ];
}

// ---------------------------------------------------------------- coleções
/**
 * As 14 coleções. 'regra' é lida pelo montador de booster: ele filtra as
 * 722 por ela. 'tipo' aceita Monster/Magic/Trap/Equip/Ritual/Field,
 * 'sub' é o tipo do monstro, 'nivel_max' e 'atk_min' apertam o recorte.
 */
function colecoes(): array
{
    return [
      'aurora'   =>['nome'=>'Aurora de Tebas','preco'=>150,'cor'=>'#D9B44A','regra'=>['tipo'=>['Monster','Magic'],'nivel_max'=>4]],
      'dragao'   =>['nome'=>'Legado do Dragão','preco'=>400,'cor'=>'#9FC6E8','regra'=>['sub'=>['Dragon','Fairy','Thunder']]],
      'metal'    =>['nome'=>'Guardiões de Metal','preco'=>350,'cor'=>'#C08A4A','regra'=>['sub'=>['Machine','Warrior','Beast-Warrior']]],
      'escriba'  =>['nome'=>'Decreto do Escriba','preco'=>300,'cor'=>'#3FA98C','regra'=>['tipo'=>['Magic','Equip','Field']]],
      'servos'   =>['nome'=>'Servos do Faraó','preco'=>300,'cor'=>'#C25B8F','regra'=>['tipo'=>['Trap']]],
      'labirinto'=>['nome'=>'Labirinto de Pesadelos','preco'=>450,'cor'=>'#8B5FBF','regra'=>['sub'=>['Zombie','Fiend','Spellcaster']]],
      'nilo'     =>['nome'=>'Cheia do Nilo','preco'=>250,'cor'=>'#2EC4B6','regra'=>['sub'=>['Aqua','Fish','Sea Serpent']]],
      'areia'    =>['nome'=>'Areia e Escaravelho','preco'=>250,'cor'=>'#C7863F','regra'=>['sub'=>['Insect','Rock','Plant']]],
      'chama'    =>['nome'=>'Chama de Sekhmet','preco'=>250,'cor'=>'#E07A3F','regra'=>['sub'=>['Pyro','Dinosaur','Reptile']]],
      'serra'    =>['nome'=>'Sopro da Serra','preco'=>250,'cor'=>'#8FD18A','regra'=>['sub'=>['Winged Beast','Beast']]],
      'ritual'   =>['nome'=>'Câmara do Ritual','preco'=>600,'cor'=>'#4A7FC1','regra'=>['tipo'=>['Ritual']]],
      'fusao'    =>['nome'=>'Fusão do Além','preco'=>600,'cor'=>'#B06FD0','regra'=>['atk_min'=>2300]],
      'relicario'=>['nome'=>'Relicário do Milênio','preco'=>0,'cor'=>'#D4AF37','regra'=>['atk_min'=>2600],'so_premio'=>true],
      'espelho'  =>['nome'=>'Caixa do Espelho','preco'=>1200,'cor'=>'#9A8FB0','regra'=>['atk_min'=>2450]],
    ];
}

// ---------------------------------------------------- mensagens do mascote
function mensagemDoNivel(int $n): string
{
    $m = [
     1=>'Uai, chegou! Senta aí. Antes de qualquer carta, a primeira regra: o texto da carta manda mais que eu.',
     2=>'Cinco cartas na mão e um Deck inteiro atrás. É mais do que a maioria começa.',
     3=>'Você já venceu um duelo. Agora vem a parte difícil: vencer o segundo.',
     4=>'Tributo não é perda, é troca. Quem entende isso passa do quarto nível.',
     5=>'Cadeia resolve de trás para frente. Guarda isso que vai te salvar mais vezes que qualquer monstro.',
     6=>'O oásis ficou para trás. Daqui em diante ninguém mais vai pegar leve.',
     7=>'Ritual custa caro. Por isso é que impressiona.',
     8=>'Fusão é matéria mais matéria mais coragem. Você já tem as três.',
     9=>'A caravana te respeitou. Poucos conseguem isso sem gastar ONEUB.',
     10=>'Mil ONEUB no cofre. Não gasta tudo com o Sefu, ele sabe o que faz.',
     11=>'Cem cartas diferentes. Já dá pra montar Deck em vez de juntar carta.',
     12=>'Duas relíquias. Escolhe bem qual leva pro duelo — só uma entra por vez.',
     13=>'A guarda te chamou de duelista sem rir. Anota o dia.',
     14=>'Quarenta cartas, todas suas, nenhuma emprestada. Isso é um Deck de verdade.',
     15=>'Oito mil pontos intactos. Não sei nem se eu consigo isso.',
     16=>'A corte ficou calada. Eles odeiam ficar calados.',
     17=>'Cinco seguidas. Cuidado com a sexta — é sempre a sexta.',
     18=>'O escriba parou de anotar. Você virou o problema dele.',
     19=>'Ela viu o futuro errado. Foi você que mudou.',
     20=>'O rio parou de rir. Isso, meu fi, é raro.',
     21=>'A forja esfriou. Ele nunca deixou a forja esfriar.',
     22=>'O vento parou. E você continua em pé.',
     23=>'As sombras te reconheceram. Não sei se é bom sinal.',
     24=>'O Enigma está inteiro. Foi para isso que eu fiquei aqui esse tempo todo.',
     25=>'Faraó. Não tenho nada para te ensinar. Vim só pelo café.',
    ];
    return $m[max(1, min(25, $n))] ?? $m[1];
}

// ------------------------------------------------------------- os oponentes
function oponentes(): array
{
    static $c = null;
    if ($c === null) { $c = require YU_APP . '/dados_oponentes.php'; }
    return $c;
}
function oponentesDaDificuldade(int $d): array { return oponentes()[$d] ?? []; }
function oponente(int $d, int $n): ?array
{
    foreach (oponentesDaDificuldade($d) as $o) { if ((int)$o['n'] === $n) return $o; }
    return null;
}
/** Caminho da arte do oponente, ou '' se ela ainda não subiu. */
function arteOponente(int $d, array $o, bool $rosto = false): string
{
    if (empty($o['img'])) return '';
    $p = dificuldade($d)['pasta'];
    $c = '/assets/img/oponentes/' . $p . ($rosto ? '/rosto/' : '/') . $o['img'];
    return temArte($c) ? $c : '';
}
