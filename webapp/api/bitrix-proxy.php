<?php
header('Content-Type: application/json');
session_start();

// Проверка CSRF-токена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$webhook = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Базовая валидация
    if (empty($data['method']) || empty($data['params'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request format']);
        exit;
    }
    
    // Фильтрация параметров
    $filteredParams = array_map(function($param) {
        return is_string($param) ? htmlspecialchars($param, ENT_QUOTES, 'UTF-8') : $param;
    }, $data['params']);
    
    $ch = curl_init($webhook . $data['method']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($filteredParams),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        http_response_code(500);
        echo json_encode(['error' => 'Proxy error: ' . $error]);
    } else {
        echo $response;
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
