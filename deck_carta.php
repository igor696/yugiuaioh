<?php
/** Pôr e tirar carta do deck sem recarregar a página. */
require __DIR__ . '/../../app/bootstrap.php';
exigirLogin();
csrfConfere();
$eu=duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
$deckId=(int)post('deck'); $num=(int)post('num'); $parte=(string)post('parte','principal');
if (!deck($deckId,$id)) json(['erro'=>'Deck não é seu.'],403);
if ((string)post('acao')==='tirar') { tirarDoDeck($deckId,$num,$parte); }
else { $r=porNoDeck($deckId,$id,$dif,$num,$parte); if (!empty($r['erro'])) json(['erro'=>$r['erro']],400); }
$c=conferirDeck($deckId);
json(['ok'=>true,'n'=>$c['n'],'valido'=>$c['valido'],'erros'=>$c['erros'],'avisos'=>$c['avisos']]);
