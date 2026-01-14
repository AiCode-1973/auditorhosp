<?php
require 'db_config.php';
$stm = $pdo->query("DESCRIBE relatorio_mensal_consolidado");
print_r($stm->fetchAll(PDO::FETCH_ASSOC));
