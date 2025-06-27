<?php
require_once ' /var/www/vm20c2.ru/bitrix_api.php'; // Путь к вашему файлу

header('Content-Type: text/plain');
echo "Тест подключения к Bitrix24 API\n";

$response = callBitrixAPI('app.info', []);
print_r($response);

$response = callBitrixAPI('crm.lead.add', ['fields' => [
    'TITLE' => 'Test Lead',
    'NAME' => 'Test'
]]);
print_r($response);
