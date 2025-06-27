<?php
// telegram_gateway.php
session_start();

// Сохраняем все GET-параметры от Telegram
$_SESSION['tg_data'] = $_GET;

// Логирование для диагностики
$log_dir = '/var/www/vm20c2.ru/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'headers' => getallheaders(),
    'get_params' => $_GET
];

file_put_contents("$log_dir/gateway.log", print_r($log_data, true), FILE_APPEND);

// Перенаправляем на основной Web App
header('Location: /webapp/order_simple.php');
exit;
