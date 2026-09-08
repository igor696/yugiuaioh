<?php
/**
 * OS ÍCONES.
 *
 * Desenhados em SVG aqui dentro, e não em PNG. Motivo prático: ícone de
 * menu tem que acompanhar a cor do texto (hover, estado ativo, contraste
 * no celular) e tem que ficar nítido em qualquer tela. PNG não faz nem
 * uma coisa nem outra, e ainda são 20 requisições a mais na Hostinger.
 *
 * As artes PNG geradas ficam para o que é ilustração — mascote, relíquia,
 * oponente, selo. Ícone de interface é vetor.
 */
declare(strict_types=1);

function svg(string $corpo, int $t = 20, string $classe = 'ico'): string
{
    return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" width="' . $t . '" height="' . $t
         . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"'
         . ' stroke-linejoin="round" aria-hidden="true">' . $corpo . '</svg>';
}

/** Olho de Hórus — a marca da casa, usada em tudo que é "identidade". */
function icoOlho(int $t = 20): string
{
    return svg('<path d="M2.5 12c3-4.5 6.5-6.5 9.5-6.5s6.5 2 9.5 6.5"/>'
             . '<path d="M2.5 12c3 4.5 6.5 6.5 9.5 6.5s6.5-2 9.5-6.5"/>'
             . '<circle cx="12" cy="12" r="3"/><path d="M15 15.5l1.5 4M9.5 15l-1 3.5"/>', $t);
}

function icoDuelo(int $t = 20): string
{
    return svg('<path d="M4 4l10 10M20 4L10 14"/><path d="M4 20l4-4M20 20l-4-4"/>'
             . '<circle cx="12" cy="10" r="1.6"/>', $t);
}
function icoDeck(int $t = 20): string
{
    return svg('<rect x="7" y="3" width="12" height="16" rx="2"/>'
             . '<path d="M4.5 6v13a2 2 0 002 2h9"/><path d="M13 9l2 2-2 2-2-2z"/>', $t);
}
function icoLoja(int $t = 20): string
{
    return svg('<path d="M3 8l1.6-4h14.8L21 8"/><path d="M3 8h18v3a3 3 0 01-6 0 3 3 0 01-6 0 3 3 0 01-6 0z"/>'
             . '<path d="M5 13v7h14v-7"/>', $t);
}
function icoCofre(int $t = 20): string
{
    return svg('<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="12" r="4"/>'
             . '<path d="M12 8.4V12M12 12l2.2 2.2"/>', $t);
}
function icoCampanha(int $t = 20): string
{
    return svg('<path d="M12 3l8 15H4z"/><path d="M7.5 14c2-1 3-3 4.5-3s2.5 2 4.5 3"/>'
             . '<path d="M12 6.5v3"/>', $t);
}
function icoLan(int $t = 20): string
{
    return svg('<rect x="3" y="15" width="5" height="6" rx="1"/><rect x="16" y="15" width="5" height="6" rx="1"/>'
             . '<path d="M5.5 15V9h13v6"/><path d="M12 9V4"/><circle cx="12" cy="3" r="1.4"/>', $t);
}
function icoOnline(int $t = 20): string
{
    return svg('<circle cx="12" cy="12" r="8"/><ellipse cx="12" cy="12" rx="8" ry="3.4"/>'
             . '<path d="M12 4v16"/>', $t);
}
function icoPasta(int $t = 20): string
{
    return svg('<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>', $t);
}
function icoCarta(int $t = 20): string
{
    return svg('<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M12 8l2.5 4-2.5 4-2.5-4z"/>', $t);
}
function icoSalvos(int $t = 20): string
{
    return svg('<path d="M6 3h12v18l-6-4-6 4z"/><path d="M9.5 8.5h5M9.5 11.5h5"/>', $t);
}
function icoPerfil(int $t = 20): string
{
    return svg('<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c0-3.6 3.1-5.5 7-5.5s7 1.9 7 5.5"/>', $t);
}
function icoManual(int $t = 20): string
{
    return svg('<path d="M4 5.5A2.5 2.5 0 016.5 3H19v15H6.5A2.5 2.5 0 004 20.5z"/>'
             . '<path d="M8 7.5h7M8 10.5h7M8 13.5h4"/>', $t);
}
function icoQuemSomos(int $t = 20): string
{
    return svg('<path d="M20 4H4v12h4v4l4-4h8z"/><circle cx="12" cy="10" r="2.2"/>'
             . '<path d="M8.4 10h-.9M16.5 10h-.9"/>', $t);
}
function icoSair(int $t = 20): string
{
    return svg('<path d="M14 4H6a2 2 0 00-2 2v12a2 2 0 002 2h8"/><path d="M17 8l4 4-4 4M21 12H10"/>', $t);
}
function icoConquista(int $t = 20): string
{
    return svg('<circle cx="12" cy="10" r="6"/><path d="M9 15.5L8 22l4-2 4 2-1-6.5"/>'
             . '<path d="M12 7l1.1 2.2 2.4.3-1.7 1.7.4 2.4-2.2-1.1-2.2 1.1.4-2.4L8.5 9.5l2.4-.3z"/>', $t);
}
function icoReliquia(int $t = 20): string
{
    return svg('<path d="M12 3l7 5-7 13-7-13z"/><path d="M5 8h14M12 3v18"/>', $t);
}
function icoPresente(int $t = 20): string
{
    return svg('<rect x="3" y="9" width="18" height="11" rx="1.5"/><path d="M3 13h18M12 9v11"/>'
             . '<path d="M12 9S10 4 7.5 5.2 9.5 9 12 9s4.5-2.6 2-3.8S12 9 12 9z"/>', $t);
}
function icoSuporte(int $t = 20): string
{
    return svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/>', $t);
}
function icoMensagem(int $t = 20): string
{
    return svg('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5L12 13l8.5-6.5"/>', $t);
}
function icoResgatar(int $t = 20): string
{
    return svg('<circle cx="8" cy="12" r="3.5"/><path d="M11.5 12H21l-2 2.5M17 12v3"/>', $t);
}
function icoFarao(int $t = 20): string
{
    return svg('<path d="M12 3l6 4v5c0 4-2.6 7.2-6 9-3.4-1.8-6-5-6-9V7z"/><circle cx="12" cy="10.5" r="2"/>'
             . '<path d="M9.5 15h5"/>', $t);
}
function icoBusca(int $t = 20): string
{
    return svg('<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/>', $t);
}

/** A moeda ONEUB: escaravelho de perfil dentro do disco. */
function icoOneub(int $t = 18): string
{
    return '<svg class="ico ico-oneub" viewBox="0 0 24 24" width="' . $t . '" height="' . $t . '" aria-hidden="true">'
         . '<circle cx="12" cy="12" r="10" fill="#D4AF37"/><circle cx="12" cy="12" r="10" fill="none" stroke="#8A6B1E" stroke-width="1.4"/>'
         . '<ellipse cx="12" cy="13" rx="3.6" ry="4.6" fill="#8A6B1E"/>'
         . '<circle cx="12" cy="7.4" r="1.9" fill="#8A6B1E"/>'
         . '<path d="M8.4 10.5L5.4 8.6M15.6 10.5l3-1.9M8.4 14l-3 1.4M15.6 14l3 1.4" stroke="#8A6B1E" stroke-width="1.3" stroke-linecap="round"/>'
         . '</svg>';
}

/**
 * O EMBLEMA DE NÍVEL.
 * Cartucho egípcio com o número dentro. Substitui qualquer forma
 * geométrica em todo lugar do site que mostre nível — inclusive ao lado
 * do nome, no cabeçalho.
 */
function emblemaNivel(int $n, string $tam = 'm'): string
{
    [$metal, $sombra] = metalNivel($n);
    $t = ['p' => 20, 'm' => 28, 'g' => 44, 'gg' => 76][$tam] ?? 28;
    $fonte = round($t * 0.42);
    $titulo = tituloNivel($n) . ' · nível ' . $n;
    return '<span class="emblema emblema-' . e($tam) . '" title="' . e($titulo) . '">'
         . '<svg viewBox="0 0 40 26" width="' . round($t * 1.5) . '" height="' . $t . '" aria-hidden="true">'
         . '<rect x="1" y="1" width="38" height="24" rx="12" fill="' . $sombra . '"/>'
         . '<rect x="2.4" y="2.4" width="35.2" height="21.2" rx="10.6" fill="none" stroke="' . $metal . '" stroke-width="1.8"/>'
         . '<path d="M6 21.5h28" stroke="' . $metal . '" stroke-width="1.8" stroke-linecap="round"/>'
         . '<text x="20" y="16.4" text-anchor="middle" font-family="Barlow Condensed, sans-serif"'
         . ' font-size="' . max(11, $fonte * 0.62) . '" font-weight="700" fill="' . $metal . '">' . $n . '</text>'
         . '</svg><span class="visualmente-oculto">' . e($titulo) . '</span></span>';
}

/** Cor do tipo de carta, do manual em PDF (pág. 7 a 17). */
function corDoTipo(string $tipo, ?string $sub = null): string
{
    return match ($tipo) {
        'Magic'  => '#3FA98C',
        'Equip'  => '#3FA98C',
        'Field'  => '#3FA98C',
        'Trap'   => '#C25B8F',
        'Ritual' => '#4A7FC1',
        default  => '#D9B44A',
    };
}
function rotuloDoTipo(string $tipo): string
{
    return match ($tipo) {
        'Magic'  => 'Mágica',
        'Equip'  => 'Mágica de Equipamento',
        'Field'  => 'Mágica de Campo',
        'Trap'   => 'Armadilha',
        'Ritual' => 'Mágica de Ritual',
        default  => 'Monstro',
    };
}
