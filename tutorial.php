<?php
/** Marca que o duelista já viu o tutorial do Indawora. */
require __DIR__ . '/../../app/bootstrap.php';
exigirLogin();
csrfConfere();
ex('UPDATE duelistas SET tutorial_visto = 1 WHERE id = ?', [(int)duelistaAtual()['id']]);
json(['ok' => true]);
