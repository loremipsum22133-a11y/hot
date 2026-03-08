<?php
header('Content-Type: application/json');
require_once __DIR__ . '/SyncPay.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!isset($input['amount']) || !isset($input['name']) || !isset($input['cpf']) || !isset($input['email']) || !isset($input['phone'])) {
    echo json_encode(['error' => 'Parâmetros incompletos.']);
    exit;
}

try {
    $amount = floatval($input['amount']);
    $name = $input['name'];
    $cpf = preg_replace('/[^0-9]/', '', $input['cpf']);
    $email = $input['email'];
    $phone = preg_replace('/[^0-9]/', '', $input['phone']);
    $plan = isset($input['plan']) ? $input['plan'] : 'Plano Generico';
    
    // Configurar o Webhook - você precisa ter um endereço acessível da internet
    // Se você não for usar o Webhook, pode colocar uma URL inválida, mas é recomendável preencher
    $webhook_url = 'https://' . $_SERVER['HTTP_HOST'] . '/api/webhook.php'; // Ajuste conforme seu domínio

    $payload = [
        'amount' => $amount,
        'description' => "Compra do plano: " . $plan,
        'webhook_url' => $webhook_url,
        'client' => [
            'name' => $name,
            'cpf' => $cpf,
            'email' => $email,
            'phone' => str_pad($phone, 11, '0', STR_PAD_LEFT)
        ]
    ];

    $response = SyncPay::post('/api/partner/v1/cash-in', $payload);

    if ($response['code'] == 200 && isset($response['body']['pix_code']) && isset($response['body']['identifier'])) {
        echo json_encode([
            'pix_code' => $response['body']['pix_code'],
            'identifier' => $response['body']['identifier']
        ]);
        exit;
    } else {
        $errorMessage = 'Erro ao conectar à API da Syncpay';
        if (isset($response['body']['errors'])) {
            $errorMessage = json_encode($response['body']['errors']);
        } elseif (isset($response['body']['message'])) {
            $errorMessage = $response['body']['message'];
        }
        
        echo json_encode([
            'error' => $errorMessage,
            'raw' => $response['raw']
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
