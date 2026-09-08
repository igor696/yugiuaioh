<?php
require __DIR__ . '/../app/bootstrap.php';
sair();
session_start();
recado('Até o próximo duelo.');
irPara('/index.php');
