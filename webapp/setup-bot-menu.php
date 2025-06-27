<?php
$botToken = '1845249310:AAGgqxI9crjWVgyCXlve0BDGssGgEANhh3g';
$webAppUrl = 'https://vm20c2.ru/webapp/index.php';

// Установка кнопки меню
$setMenuUrl = "https://api.telegram.org/bot$botToken/setChatMenuButton";
$menuData = [
    'menu_button' => [
        'type' => 'web_app',
        'text' => 'Открыть услуги',
        'web_app' => ['url' => $webAppUrl]
    ]
];

$ch = curl_init($setMenuUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($menuData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);
?>
