<?php
/** Abrir pacote carta por carta, sem recarregar. */
require __DIR__ . '/../../app/bootstrap.php';
exigirLogin();
csrfConfere();
$r = abrirBooster((int)duelistaAtual()['id'], (int)post('id'));
if (!empty($r['erro'])) json(['erro'=>$r['erro']],400);
json($r);
