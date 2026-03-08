<?php
header('Content-Type: application/json');
// Esse endpoint age apenas como callback inativo caso a transação ja esteja paga
// Na versão original, poderia ter sido usado para dar 'update' no BD.

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!isset($input['identifier']) || !isset($input['status'])) {
    echo json_encode(['error' => 'Parâmetros incompletos.']);
    exit;
}

echo json_encode(['success' => true]);
