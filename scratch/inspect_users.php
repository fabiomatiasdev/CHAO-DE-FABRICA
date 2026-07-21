<?php
require 'app/Core/Database.php';

use App\Core\Database;

$user = Database::fetch("SELECT * FROM users LIMIT 1");
echo "--- COLUNAS DA TABELA users ---\n";
var_dump(array_keys($user));

$users = Database::fetchAll("SELECT id, nome, email, role FROM users");
echo "\n--- DADOS DA TABELA users ---\n";
var_dump($users);
