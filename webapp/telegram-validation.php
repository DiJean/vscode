<?php
function validateTelegramInitData($botToken, $initData)
{
    // Парсим строку initData
    parse_str($initData, $params);

    // Проверяем наличие хэша
    if (!isset($params['hash'])) {
        return false;
    }

    // Извлекаем хэш и удаляем его из параметров
    $receivedHash = $params['hash'];
    unset($params['hash']);

    // Сортируем параметры по ключу
    ksort($params);

    // Формируем строку для проверки
    $dataCheckString = [];
    foreach ($params as $key => $value) {
        $dataCheckString[] = "$key=$value";
    }
    $dataCheckString = implode("\n", $dataCheckString);

    // Генерируем секретный ключ
    $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);

    // Вычисляем HMAC
    $computedHash = bin2hex(
        hash_hmac('sha256', $dataCheckString, $secretKey, true)
    );

    // Сравниваем хэши
    return hash_equals($computedHash, $receivedHash);
}

// Пример использования:
$botToken = 'ВАШ_BOT_TOKEN';
$initData = $_POST['initData'] ?? '';

if (validateTelegramInitData($botToken, $initData)) {
    echo json_encode(['valid' => true]);
} else {
    echo json_encode(['valid' => false, 'error' => 'Invalid hash']);
}
