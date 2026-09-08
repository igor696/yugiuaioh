<?php
/** Busca usada pelo Anel do Milênio e pelo montador. */
require __DIR__ . '/../../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $dif=difAtual();
[$linhas,] = buscarCartas(['busca'=>(string)get('b',''),'so_minhas'=>get('minhas')?1:0,'ordem'=>'nome'],
                          1, 40, (int)$eu['id'], $dif);
json(['ok'=>true,'cartas'=>array_map(fn($c)=>[
  'num'=>(int)$c['num'],'nome'=>$c['nome'],'tipo'=>$c['tipo'],'passcode'=>$c['passcode'],
  'atk'=>(int)$c['atk'],'def'=>(int)$c['def'],'nivel'=>(int)$c['nivel'],'tenho'=>(int)$c['tenho'],
],$linhas)]);
