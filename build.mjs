/**
 * build.mjs — build do site yugiuaioh.com.br
 *
 * Copia os arquivos do site para a pasta dist/, que e a pasta que o
 * server.mjs publica.
 *
 * Sem nenhuma dependencia externa e usando so APIs estaveis no Node 18,
 * que e a versao configurada na Hostinger.
 */

import { copyFileSync, existsSync, mkdirSync, readdirSync, rmSync, statSync } from 'node:fs';
import { join } from 'node:path';

const OUT = 'dist';

// Nao vai para o site publicado.
const IGNORAR = new Set([
  OUT,
  'node_modules',
  '.git',
  '.github',
  '.gitignore',
  '.gitattributes',
  '.env',
  '.env.local',
  '.vscode',
  '.idea',
  '.DS_Store',
  'package.json',
  'package-lock.json',
  'build.mjs',
  'server.mjs',
  'README.md',
  'LICENSE',
]);

function copiar(origem, destino) {
  if (statSync(origem).isDirectory()) {
    mkdirSync(destino, { recursive: true });
    for (const item of readdirSync(origem)) {
      copiar(join(origem, item), join(destino, item));
    }
  } else {
    copyFileSync(origem, destino);
  }
}

rmSync(OUT, { recursive: true, force: true });
mkdirSync(OUT, { recursive: true });

let copiados = 0;

for (const item of readdirSync('.')) {
  if (IGNORAR.has(item)) continue;
  copiar(item, join(OUT, item));
  copiados++;
  console.log('  + ' + item);
}

if (copiados === 0) {
  console.error('ERRO: nenhum arquivo para publicar foi encontrado na raiz do repositorio.');
  process.exit(1);
}

if (!existsSync(join(OUT, 'index.html'))) {
  console.warn('AVISO: nao existe index.html na raiz. O site pode abrir em branco.');
}

console.log('\nBuild OK: ' + copiados + ' item(ns) copiado(s) para ' + OUT + '/');
