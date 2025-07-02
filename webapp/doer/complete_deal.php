<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '10M');
ini_set('memory_limit', '256M');

// Конфигурация (ОБНОВЛЕННЫЙ ВЕБХУК)
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/'; // Добавлен завершающий слэш
$FOLDER_ID = 1;
$TELEGRAM_BOT_TOKEN = 'ВАШ_TELEGRAM_BOT_TOKEN';
$MAX_FILE_SIZE = 5 * 1024 * 1024;
$ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

try {
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Получаем данные
    $dealId = $_POST['deal_id'] ?? null;
    $tgUserId = $_POST['tg_user_id'] ?? null;
    $beforePhoto = $_FILES['before_photo'] ?? null;
    $afterPhoto = $_FILES['after_photo'] ?? null;

    // Валидация входных данных
    if (!$dealId) throw new Exception('Не указан ID заявки');
    if (!$tgUserId) throw new Exception('Не указан идентификатор пользователя');
    if (!$beforePhoto || !$afterPhoto) throw new Exception('Необходимо загрузить оба фото');

    // Проверка файлов
    validatePhoto($beforePhoto);
    validatePhoto($afterPhoto);

    // Получаем информацию о сделке
    $dealInfo = getDealInfo($dealId);

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

    // Обновление сделки
    $updateResult = updateDeal($dealId, $beforeFileId, $afterFileId);

    if ($updateResult) {
        // Отправка уведомлений (опционально)
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка: ' . $e->getMessage()
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

    // Исправленное определение MIME-типа
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);

    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPEG, PNG, WebP');
    }
}

// Остальные функции остаются без изменений, но с исправленными URL:

function uploadFileToBitrix($file)
{
    global $BITRIX_WEBHOOK, $FOLDER_ID;

    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        throw new Exception('Не удалось прочитать файл');
    }

    $fileEncoded = base64_encode($fileContent);
    $fileName = sanitizeFileName($file['name']);

    // ИСПРАВЛЕННЫЙ URL (без дублирования слэшей)
    $url = $BITRIX_WEBHOOK . 'disk.folder.uploadfile.json';

    $params = [
        'id' => $FOLDER_ID,
        'fields' => [
            'NAME' => $fileName,
            'FILE_CONTENT' => $fileEncoded
        ],
        'generateUniqueName' => true
    ];

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        throw new Exception('Ошибка загрузки файла в Bitrix24: ' . print_r($response, true));
    }

    return $response['result']['ID'];
}

function updateDeal($dealId, $beforeFileId, $afterFileId)
{
    global $BITRIX_WEBHOOK;

    // ИСПРАВЛЕННЫЙ URL
    $url = $BITRIX_WEBHOOK . 'crm.deal.update.json';

    $fields = ['STAGE_ID' => 'WON'];
    if ($beforeFileId) $fields['UF_CRM_1751200529'] = [$beforeFileId];
    if ($afterFileId) $fields['UF_CRM_1751200549'] = [$afterFileId];

    $params = [
        'id' => $dealId,
        'fields' => $fields
    ];

    try {
        $response = makeBitrixRequest($url, $params);
        return isset($response['result']) && $response['result'] === true;
    } catch (Exception $e) {
        throw new Exception("Ошибка обновления сделки: " . $e->getMessage());
    }
}

// Остальные функции без изменений
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
