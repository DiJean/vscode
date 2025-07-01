<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Основные настройки
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/ugw0splnpwusjx89/';
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
    sendErrorResponse('Не указан ID заявки');
}

if (!$beforePhoto || !$afterPhoto) {
    sendErrorResponse('Необходимо загрузить оба фото');
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
        if (!empty($dealInfo['performer_tg_id'])) {
            sendTelegramNotification(
                $dealInfo['performer_tg_id'],
                "✅ Вы завершили заказ #{$dealId}\nКлиент: {$dealInfo['client_name']}"
            );
        }

        // Отправляем уведомление клиенту
        if (!empty($dealInfo['client_tg_id'])) {
            sendTelegramNotification(
                $dealInfo['client_tg_id'],
                "✅ Ваш заказ #{$dealId} завершен!\nИсполнитель: {$dealInfo['performer_name']}"
            );
        }

        // Логирование успешного завершения
    } else {
        error_log("Deal update failed for ID: $dealId");
        sendErrorResponse('Не удалось обновить сделку');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error completing deal: ' . $e->getMessage());
    sendErrorResponse($e->getMessage());
}

/**
 * Отправка ошибки в формате JSON
 */
function sendErrorResponse($message)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

/**
 * Валидация загружаемого фото
 */
function validatePhoto($file)
{
    global $MAX_FILE_SIZE, $ALLOWED_MIME_TYPES;

    // Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла: ' . getUploadErrorMessage($file['error']));
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
 * Получение понятного описания ошибки загрузки
 */
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

    $url = $BITRIX_WEBHOOK . 'disk.folder.uploadfile';
    $params = [
        'id' => $FOLDER_ID,
        'data' => [
            'NAME' => $fileName,
            'FILE_CONTENT' => $fileEncoded
        ],
        'generateUniqueName' => true
    ];

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        error_log('Bitrix file upload error: ' . print_r($response, true));
        throw new Exception('Ошибка загрузки файла в Bitrix24');
    }

    return $response['result']['ID'];
}

/**
 * Универсальный запрос к Bitrix API
 */
function makeBitrixRequest($method, $params = [])
{
    $url = $method . '.json';
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

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
    $url = $BITRIX_WEBHOOK . 'crm.deal.update';
    $params = [
        'id' => $dealId,
        'fields' => [
            'STAGE_ID' => 'WON',
            'UF_CRM_1751200529' => $beforeFileId,
            'UF_CRM_1751200549' => $afterFileId,
        ]
    ];

    $response = makeBitrixRequest($url, $params);
    return isset($response['result']) && $response['result'] === true;
}

/**
 * Получение информации о сделке
 */
function getDealInfo($dealId)
{
    $url = $BITRIX_WEBHOOK . 'crm.deal.get';
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

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        error_log('Bitrix deal get error: ' . print_r($response, true));
        throw new Exception('Не удалось получить информацию о сделке');
    }

    $deal = $response['result'];

    // Получаем информацию о клиенте
    $clientInfo = getContactInfo($deal['CONTACT_ID']);

    // Получаем информацию об исполнителе
    $performerInfo = !empty($deal['UF_CRM_1751128612']) ?
        getContactInfo($deal['UF_CRM_1751128612']) :
        [];

    return [
        'deal_id' => $dealId,
        'client_name' => trim(($clientInfo['NAME'] ?? '') . ' ' . ($clientInfo['LAST_NAME'] ?? '')),
        'client_tg_id' => $clientInfo['UF_CRM_1751128872'] ?? null,
        'performer_name' => trim(($performerInfo['NAME'] ?? '') . ' ' . ($performerInfo['LAST_NAME'] ?? '')),
        'performer_tg_id' => $performerInfo['UF_CRM_1751128872'] ?? null,
    ];
}

/**
 * Получение информации о контакте
 */
function getContactInfo($contactId)
{
    if (!$contactId) return [];

    $url = $BITRIX_WEBHOOK . 'crm.contact.get';
    $params = [
        'id' => $contactId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_1751128872'] // Telegram ID
    ];

    try {
        $response = makeBitrixRequest($url, $params);
        return $response['result'] ?? [];
    } catch (Exception $e) {
        error_log('Error getting contact info: ' . $e->getMessage());
        return [];
    }
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
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Telegram send error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['ok'] ?? false;
}
