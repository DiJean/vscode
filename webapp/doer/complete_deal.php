<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Конфигурация
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u';
$FOLDER_ID = 1;
$TELEGRAM_BOT_TOKEN = 'ВАШ_TELEGRAM_BOT_TOKEN';
$MAX_FILE_SIZE = 5 * 1024 * 1024;
$ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

// Включим подробное логирование
file_put_contents('debug.log', "\n\n" . date('Y-m-d H:i:s') . " - Начало обработки запроса\n", FILE_APPEND);

try {
    // Получаем данные
    $dealId = $_POST['deal_id'] ?? null;
    $tgUserId = $_POST['tg_user_id'] ?? null;
    $beforePhoto = $_FILES['before_photo'] ?? null;
    $afterPhoto = $_FILES['after_photo'] ?? null;

    file_put_contents('debug.log', "Полученные данные:\n" . print_r([
        'deal_id' => $dealId,
        'tg_user_id' => $tgUserId,
        'before_photo' => $beforePhoto ? $beforePhoto['name'] : 'N/A',
        'after_photo' => $afterPhoto ? $afterPhoto['name'] : 'N/A'
    ], true) . "\n", FILE_APPEND);

    // Валидация входных данных
    if (!$dealId) throw new Exception('Не указан ID заявки');
    if (!$tgUserId) throw new Exception('Не указан идентификатор пользователя');
    if (!$beforePhoto || !$afterPhoto) throw new Exception('Необходимо загрузить оба фото');

    // Проверка файлов
    validatePhoto($beforePhoto);
    validatePhoto($afterPhoto);

    // Получаем информацию о сделке
    $dealInfo = getDealInfo($dealId);
    file_put_contents('debug.log', "Информация о сделке:\n" . print_r($dealInfo, true) . "\n", FILE_APPEND);

    // Проверка прав доступа
    if (empty($dealInfo['performer_tg_id'])) {
        throw new Exception('Исполнитель не назначен на заказ');
    }

    if ($dealInfo['performer_tg_id'] != $tgUserId) {
        throw new Exception('Вы не являетесь исполнителем этого заказа');
    }

    // Загрузка файлов
    $beforeFileId = uploadFileToBitrix($beforePhoto);
    $afterFileId = uploadFileToBitrix($afterPhoto);

    file_put_contents('debug.log', "Файлы загружены: before=$beforeFileId, after=$afterFileId\n", FILE_APPEND);

    // Обновление сделки
    $updateResult = updateDeal($dealId, $beforeFileId, $afterFileId);

    if ($updateResult) {
        // Отправка уведомлений
        if (!empty($dealInfo['performer_tg_id'])) {
            sendTelegramNotification(
                $dealInfo['performer_tg_id'],
                "✅ Вы завершили заказ #{$dealId}\nКлиент: {$dealInfo['client_name']}"
            );
        }

        if (!empty($dealInfo['client_tg_id'])) {
            sendTelegramNotification(
                $dealInfo['client_tg_id'],
                "✅ Ваш заказ #{$dealId} завершен!\nИсполнитель: {$dealInfo['performer_name']}"
            );
        }

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Не удалось обновить сделку');
    }
} catch (Exception $e) {
    $errorMessage = 'Ошибка: ' . $e->getMessage();
    file_put_contents('debug.log', $errorMessage . "\n", FILE_APPEND);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'trace' => $e->getTraceAsString()
    ]);
}

// Функции =====================================================================

function validatePhoto($file)
{
    global $MAX_FILE_SIZE, $ALLOWED_MIME_TYPES;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла: ' . getUploadErrorMessage($file['error']));
    }

    if ($file['size'] > $MAX_FILE_SIZE) {
        throw new Exception('Размер файла превышает 5MB');
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPEG, PNG, WebP');
    }

    if (!getimagesize($file['tmp_name'])) {
        throw new Exception('Файл не является изображением');
    }
}

function getUploadErrorMessage($errorCode)
{
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'Размер файла превышает разрешенный сервером',
        UPLOAD_ERR_FORM_SIZE  => 'Размер файла превышает разрешенный формой',
        UPLOAD_ERR_PARTIAL    => 'Файл загружен частично',
        UPLOAD_ERR_NO_FILE    => 'Файл не был загружен',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
        UPLOAD_ERR_EXTENSION  => 'Расширение PHP остановило загрузку файла',
    ];

    return $errors[$errorCode] ?? "Неизвестная ошибка ($errorCode)";
}

function uploadFileToBitrix($file)
{
    global $BITRIX_WEBHOOK, $FOLDER_ID;

    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        throw new Exception('Не удалось прочитать файл');
    }

    $fileEncoded = base64_encode($fileContent);
    $fileName = sanitizeFileName($file['name']);

    $url = $BITRIX_WEBHOOK . '/disk.folder.uploadfile.json';
    $params = [
        'id' => $FOLDER_ID,
        'fields' => [
            'NAME' => $fileName,
            'FILE_CONTENT' => $fileEncoded
        ],
        'generateUniqueName' => true
    ];

    file_put_contents('debug.log', "Запрос на загрузку файла: $url\nПараметры: " . json_encode([
        'id' => $FOLDER_ID,
        'fields' => ['NAME' => $fileName, 'FILE_CONTENT' => '[base64_data]'],
        'generateUniqueName' => true
    ]) . "\n", FILE_APPEND);

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        throw new Exception('Ошибка загрузки файла в Bitrix24: ' . print_r($response, true));
    }

    return $response['result']['ID'];
}

function makeBitrixRequest($url, $params = [])
{
    $ch = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('curl_debug.log', 'a+'),
    ];

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    file_put_contents('debug.log', "HTTP запрос: $url\nКод ответа: $httpCode\nОтвет: $response\n", FILE_APPEND);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("CURL error: $error");
    }

    curl_close($ch);

    if ($httpCode === 401) {
        throw new Exception('Ошибка авторизации в Bitrix24. Проверьте вебхук');
    }

    if ($httpCode !== 200) {
        throw new Exception("Bitrix API вернул HTTP $httpCode");
    }

    $result = json_decode($response, true);

    if (isset($result['error'])) {
        $errorMsg = $result['error_description'] ?? $result['error'];
        throw new Exception("Bitrix API error: $errorMsg");
    }

    return $result;
}

function sanitizeFileName($filename)
{
    $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename);
    return substr($filename, 0, 100);
}

function updateDeal($dealId, $beforeFileId, $afterFileId)
{
    global $BITRIX_WEBHOOK;
    $url = $BITRIX_WEBHOOK . '/crm.deal.update.json';

    $fields = [
        'STAGE_ID' => 'WON'
    ];

    if ($beforeFileId) $fields['UF_CRM_1751200529'] = [$beforeFileId];
    if ($afterFileId) $fields['UF_CRM_1751200549'] = [$afterFileId];

    $params = [
        'id' => $dealId,
        'fields' => $fields
    ];

    file_put_contents('debug.log', "Обновление сделки: $url\nПараметры: " . json_encode($params) . "\n", FILE_APPEND);

    try {
        $response = makeBitrixRequest($url, $params);
        return isset($response['result']) && $response['result'] === true;
    } catch (Exception $e) {
        file_put_contents('debug.log', "Ошибка при обновлении сделки: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

function getDealInfo($dealId)
{
    global $BITRIX_WEBHOOK;
    $url = $BITRIX_WEBHOOK . '/crm.deal.get.json';
    $params = [
        'id' => $dealId,
        'select' => [
            'ID',
            'TITLE',
            'CONTACT_ID',
            'ASSIGNED_BY_ID',
            'UF_CRM_1751128612',
            'UF_CRM_1751128872'
        ]
    ];

    file_put_contents('debug.log', "Получение информации о сделке: $url\n", FILE_APPEND);

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        throw new Exception('Не удалось получить информацию о сделке');
    }

    $deal = $response['result'];
    $clientInfo = getContactInfo($deal['CONTACT_ID']);
    $performerInfo = getContactInfo($deal['UF_CRM_1751128612']);

    return [
        'deal_id' => $dealId,
        'client_name' => trim(($clientInfo['NAME'] ?? '') . ' ' . ($clientInfo['LAST_NAME'] ?? '')),
        'client_tg_id' => $clientInfo['UF_CRM_1751128872'] ?? null,
        'performer_name' => trim(($performerInfo['NAME'] ?? '') . ' ' . ($performerInfo['LAST_NAME'] ?? '')),
        'performer_tg_id' => $performerInfo['UF_CRM_1751128872'] ?? null,
    ];
}

function getContactInfo($contactId)
{
    if (!$contactId) return [];

    global $BITRIX_WEBHOOK;
    $url = $BITRIX_WEBHOOK . '/crm.contact.get.json';
    $params = [
        'id' => $contactId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_1751128872']
    ];

    file_put_contents('debug.log', "Получение информации о контакте: $url\n", FILE_APPEND);

    try {
        $response = makeBitrixRequest($url, $params);
        return $response['result'] ?? [];
    } catch (Exception $e) {
        file_put_contents('debug.log', "Ошибка при получении контакта: " . $e->getMessage() . "\n", FILE_APPEND);
        return [];
    }
}

function sendTelegramNotification($chatId, $message)
{
    global $TELEGRAM_BOT_TOKEN;

    if (!$chatId || !$TELEGRAM_BOT_TOKEN) return false;

    $url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML'];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    file_put_contents('debug.log', "Telegram запрос: $url\nКод: $httpCode\nОтвет: $response\n", FILE_APPEND);

    if (curl_errno($ch)) {
        file_put_contents('debug.log', "Ошибка Telegram: " . curl_error($ch) . "\n", FILE_APPEND);
    }

    curl_close($ch);
    return $httpCode === 200;
}
