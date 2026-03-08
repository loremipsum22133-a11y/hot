<?php
header('Content-Type: application/json');

class SyncPay {
    private static $client_id = '05835457-fc65-481f-bbb1-eeb2bf5ce2a1';
    private static $client_secret = '9a137442-1dc5-4a81-b88a-c883379c3483';
    private static $api_base = 'https://api.syncpayments.com.br';
    private static $token_file = __DIR__ . '/.syncpay_token.json';

    public static function getToken() {
        if (file_exists(self::$token_file)) {
            $data = json_decode(file_get_contents(self::$token_file), true);
            if ($data && isset($data['access_token']) && isset($data['expires_at'])) {
                if (strtotime($data['expires_at']) > time() + 300) {
                    return $data['access_token'];
                }
            }
        }

        $ch = curl_init(self::$api_base . '/api/partner/v1/auth-token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'client_id' => self::$client_id,
            'client_secret' => self::$client_secret
        ]));
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resDecoded = json_decode($response, true);

        if ($httpcode == 200 && isset($resDecoded['access_token'])) {
            file_put_contents(self::$token_file, $response);
            return $resDecoded['access_token'];
        }

        throw new Exception("Falha ao obter token da SyncPay: " . $response);
    }
    
    public static function get($endpoint) {
        $token = self::getToken();
        $ch = curl_init(self::$api_base . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public static function post($endpoint, $data) {
        $token = self::getToken();
        $ch = curl_init(self::$api_base . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $httpcode, 'body' => json_decode($response, true), 'raw' => $response];
    }
}
