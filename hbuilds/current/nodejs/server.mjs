/**
 * server.mjs — entry file do site yugiuaioh.com.br
 *
 * So e necessario se a Hostinger pedir um "Entry file" (modo aplicacao Node).
 * No modo estatico (Output directory = dist) este arquivo nao chega a rodar.
 *
 * Servidor estatico sem nenhuma dependencia externa.
 */

import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize, resolve, sep } from 'node:path';

const RAIZ = resolve('dist');
const PORTA = process.env.PORT || 3000;

const TIPOS = {
  '.html': 'text/html; charset=utf-8',
  '.htm': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.webp': 'image/webp',
  '.avif': 'image/avif',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.otf': 'font/otf',
  '.mp3': 'audio/mpeg',
  '.ogg': 'audio/ogg',
  '.mp4': 'video/mp4',
  '.webm': 'video/webm',
  '.txt': 'text/plain; charset=utf-8',
  '.pdf': 'application/pdf',
  '.zip': 'application/zip',
};

async function resolverArquivo(urlPath) {
  // Bloqueia path traversal (../../etc/passwd)
  const relativo = normalize(decodeURIComponent(urlPath)).replace(/^(\.\.[/\\])+/, '');
  let alvo = resolve(join(RAIZ, relativo));
  if (alvo !== RAIZ && !alvo.startsWith(RAIZ + sep)) return null;

  try {
    const info = await stat(alvo);
    if (info.isDirectory()) alvo = join(alvo, 'index.html');
  } catch {
    return null;
  }

  try {
    await stat(alvo);
    return alvo;
  } catch {
    return null;
  }
}

createServer(async (req, res) => {
  const caminhoUrl = new URL(req.url, `http://${req.headers.host || 'localhost'}`).pathname;
  const arquivo = await resolverArquivo(caminhoUrl);

  if (!arquivo) {
    const pagina404 = await resolverArquivo('/404.html');
    if (pagina404) {
      res.writeHead(404, { 'Content-Type': TIPOS['.html'] });
      res.end(await readFile(pagina404));
      return;
    }
    res.writeHead(404, { 'Content-Type': TIPOS['.txt'] });
    res.end('404 - pagina nao encontrada');
    return;
  }

  const tipo = TIPOS[extname(arquivo).toLowerCase()] || 'application/octet-stream';
  res.writeHead(200, { 'Content-Type': tipo });
  res.end(await readFile(arquivo));
}).listen(PORTA, () => {
  console.log(`Servidor no ar em http://localhost:${PORTA} (servindo ${RAIZ})`);
});
