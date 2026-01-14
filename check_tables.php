<?php
require 'db_config.php';
$stm = $pdo->query("SHOW TABLES LIKE 'relatorio_mensal_pa%'");
print_r($stm->fetchAll(PDO::FETCH_COLUMN));
