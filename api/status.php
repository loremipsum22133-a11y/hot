<?php
header('Content-Type: application/json');
require_once __DIR__ . '/SyncPay.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!isset($input['identifier'])) {
    echo json_encode(['error' => 'Parâmetros incompletos.']);
    exit;
}

try {
    $identifier = $input['identifier'];

    $ch = curl_init(SyncPay::$api_base . '/api/partner/v1/transaction/' . urlencode($identifier));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . SyncPay::getToken()
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $body = json_decode($response, true);
    
    // O retorno esperado pela página em index.html: { data: { status: "completed" ... } }
    // Devolveremos conforme a estrutura recebida.
    
    if ($httpcode == 200 && isset($body['data'])) {
        echo json_encode($body);
    } else {
        echo json_encode(['status' => 'PENDING', 'raw' => $response]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
