<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Основные настройки
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
$FOLDER_ID = 1; // ID папки в Битрикс24 для загрузки файлов
$TELEGRAM_BOT_TOKEN = 'ВАШ_TELEGRAM_BOT_TOKEN'; // Замените на реальный токен бота
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
$ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

// Обработка входящих данных
$dealId = $_POST['deal_id'] ?? null;
$beforePhoto = $_FILES['before_photo'] ?? null;
$afterPhoto = $_FILES['after_photo'] ?? null;

// Валидация входных данных
if (!$dealId) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID заявки']);
    exit;
}

if (!$beforePhoto || !$afterPhoto) {
    echo json_encode(['success' => false, 'error' => 'Необходимо загрузить оба фото']);
    exit;
}

try {
    // Валидация фото "До"
    validatePhoto($beforePhoto);

    // Валидация фото "После"
    validatePhoto($afterPhoto);

    // Загрузка фото "До"
    $beforeFileId = uploadFileToBitrix($beforePhoto);

    // Загрузка фото "После"
    $afterFileId = uploadFileToBitrix($afterPhoto);

    // Обновление сделки
    $updateResult = updateDeal($dealId, $beforeFileId, $afterFileId);

    if ($updateResult) {
        // Получаем информацию о сделке для уведомления
        $dealInfo = getDealInfo($dealId);

        // Отправляем уведомление исполнителю
        if ($dealInfo['performer_tg_id']) {
            sendTelegramNotification(
                $dealInfo['performer_tg_id'],
                "✅ Вы завершили заказ #{$dealId}\nКлиент: {$dealInfo['client_name']}"
            );
        }

        // Отправляем уведомление клиенту
        if ($dealInfo['client_tg_id']) {
            sendTelegramNotification(
                $dealInfo['client_tg_id'],
                "✅ Ваш заказ #{$dealId} завершен!\nИсполнитель: {$dealInfo['performer_name']}"
            );
        }

        // Логирование успешного завершения
        error_log("Deal $dealId completed successfully");
        echo json_encode(['success' => true]);
    } else {
        error_log("Deal update failed for ID: $dealId");
        echo json_encode(['success' => false, 'error' => 'Не удалось обновить сделку']);
    }
} catch (Exception $e) {
    error_log('Error completing deal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Валидация загружаемого фото
 */
function validatePhoto($file)
{
    global $MAX_FILE_SIZE, $ALLOWED_MIME_TYPES;

    // Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла: ' . $file['error']);
    }

    // Проверка размера файла
    if ($file['size'] > $MAX_FILE_SIZE) {
        throw new Exception('Размер файла превышает 5MB');
    }

    // Проверка MIME-типа
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPEG, PNG, WebP');
    }

    // Дополнительная проверка изображения
    if (!@getimagesize($file['tmp_name'])) {
        throw new Exception('Файл не является изображением');
    }
}

/**
 * Загрузка файла в Bitrix24
 */
function uploadFileToBitrix($file)
{
    global $BITRIX_WEBHOOK, $FOLDER_ID;

    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        throw new Exception('Не удалось прочитать файл');
    }

    $fileEncoded = base64_encode($fileContent);
    $fileName = sanitizeFileName($file['name']);

    $url = $BITRIX_WEBHOOK . 'disk.folder.uploadfile.json';
    $params = [
        'id' => $FOLDER_ID,
        'data' => [
            'NAME' => $fileName,
            'FILE_CONTENT' => $fileEncoded
        ],
        'generateUniqueName' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('CURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Bitrix API returned HTTP $httpCode");
    }

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        error_log('Bitrix file upload error: ' . print_r($result, true));
        throw new Exception('Ошибка загрузки файла в Bitrix24');
    }

    return $result['result']['ID'];
}

/**
 * Санитайзинг имени файла
 */
function sanitizeFileName($filename)
{
    $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename);
    return substr($filename, 0, 100);
}

/**
 * Обновление сделки
 */
function updateDeal($dealId, $beforeFileId, $afterFileId)
{
    global $BITRIX_WEBHOOK;

    $url = $BITRIX_WEBHOOK . 'crm.deal.update.json';
    $params = [
        'id' => $dealId,
        'fields' => [
            'STAGE_ID' => 'WON', // Устанавливаем статус "Успешно завершена"
            'UF_CRM_1751200529' => $beforeFileId, // ID фото "До"
            'UF_CRM_1751200549' => $afterFileId, // ID фото "После"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('CURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Bitrix API returned HTTP $httpCode");
    }

    $result = json_decode($response, true);

    if (isset($result['error'])) {
        error_log('Bitrix deal update error: ' . $result['error_description']);
    }

    return isset($result['result']) && $result['result'] === true;
}

/**
 * Получение информации о сделке
 */
function getDealInfo($dealId)
{
    global $BITRIX_WEBHOOK;

    $url = $BITRIX_WEBHOOK . 'crm.deal.get.json';
    $params = [
        'id' => $dealId,
        'select' => [
            'ID',
            'TITLE',
            'CONTACT_ID',
            'ASSIGNED_BY_ID',
            'UF_CRM_1751128612' // ID исполнителя (контакт)
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('CURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Bitrix API returned HTTP $httpCode");
    }

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        error_log('Bitrix deal get error: ' . print_r($result, true));
        throw new Exception('Не удалось получить информацию о сделке');
    }

    $deal = $result['result'];

    // Получаем информацию о клиенте
    $clientInfo = getContactInfo($deal['CONTACT_ID']);

    // Получаем информацию об исполнителе
    $performerInfo = $deal['UF_CRM_1751128612'] ? getContactInfo($deal['UF_CRM_1751128612']) : [];

    return [
        'deal_id' => $dealId,
        'client_name' => $clientInfo['NAME'] . ' ' . $clientInfo['LAST_NAME'],
        'client_tg_id' => $clientInfo['UF_CRM_1751128872'] ?? null,
        'performer_name' => $performerInfo ? ($performerInfo['NAME'] . ' ' . $performerInfo['LAST_NAME']) : 'Не назначен',
        'performer_tg_id' => $performerInfo['UF_CRM_1751128872'] ?? null,
    ];
}

/**
 * Получение информации о контакте
 */
function getContactInfo($contactId)
{
    global $BITRIX_WEBHOOK;

    if (!$contactId) return [];

    $url = $BITRIX_WEBHOOK . 'crm.contact.get.json';
    $params = [
        'id' => $contactId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_1751128872'] // Telegram ID
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('CURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Bitrix API returned HTTP $httpCode");
    }

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        error_log('Bitrix contact get error: ' . print_r($result, true));
        return [];
    }

    return $result['result'];
}

/**
 * Отправка уведомления в Telegram
 */
function sendTelegramNotification($chatId, $message)
{
    global $TELEGRAM_BOT_TOKEN;

    if (!$chatId || !$TELEGRAM_BOT_TOKEN) return false;

    $url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Telegram send error: ' . curl_error($ch));
        return false;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['ok'] ?? false;
}
