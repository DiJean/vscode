<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

// Разрешаем OPTIONS запросы
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '15M');
ini_set('memory_limit', '256M');

// Логирование
$logFile = __DIR__ . '/complete_deal.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Начало обработки запроса\n", FILE_APPEND);

function logMessage($message)
{
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
$FOLDER_ID = 1;
$TELEGRAM_BOT_TOKEN = 'bot:1845249310:AAGgqxI9crjWVgyCXlve0BDGssGgEANhh3g';
$MAX_FILE_SIZE = 5 * 1024 * 1024;
$ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

try {
    logMessage("Получен запрос: " . print_r($_POST, true));
    logMessage("Файлы: " . print_r($_FILES, true));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Недопустимый метод запроса');
    }

    $dealId = $_POST['deal_id'] ?? null;
    $tgUserId = $_POST['tg_user_id'] ?? null;
    $beforePhoto = $_FILES['before_photo'] ?? null;
    $afterPhoto = $_FILES['after_photo'] ?? null;

    if (!$dealId) throw new Exception('Не указан ID заявки');
    if (!$tgUserId) throw new Exception('Не указан идентификатор пользователя');

    // Строгая проверка файлов
    if (!$beforePhoto || !$afterPhoto) {
        throw new Exception('Необходимо загрузить оба фото');
    }

    // Проверка на пустые файлы
    if ($beforePhoto['size'] === 0 || $afterPhoto['size'] === 0) {
        throw new Exception('Файлы не должны быть пустыми');
    }

    validatePhoto($beforePhoto);
    validatePhoto($afterPhoto);

    $dealInfo = getDealInfo($dealId);
    logMessage("Информация о сделке: " . print_r($dealInfo, true));

    if (empty($dealInfo['performer_tg_id'])) {
        throw new Exception('Исполнитель не назначен на заказ');
    }

    if ($dealInfo['performer_tg_id'] != $tgUserId) {
        throw new Exception('Вы не являетесь исполнителем этого заказа. Ваш ID: ' .
            $tgUserId . ', ожидался: ' . $dealInfo['performer_tg_id']);
    }

    if ($dealInfo['stage_id'] !== 'EXECUTING') {
        throw new Exception('Заявка не в статусе исполнения. Текущий статус: ' . $dealInfo['stage_id']);
    }

    $beforeFileId = uploadFileToBitrix($beforePhoto);
    $afterFileId = uploadFileToBitrix($afterPhoto);

    logMessage("ID фото до: $beforeFileId");
    logMessage("ID фото после: $afterFileId");

    // Обновление сделки
    $updateResult = updateDeal($dealId, $beforeFileId, $afterFileId);
    logMessage("Результат обновления: " . print_r($updateResult, true));

    if ($updateResult === true) {
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
        throw new Exception('Не удалось обновить сделку. Ответ Bitrix: ' . print_r($updateResult, true));
    }
} catch (Exception $e) {
    http_response_code(400);
    logMessage("ОШИБКА: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка: ' . $e->getMessage()
    ]);
}

function validatePhoto($file)
{
    global $MAX_FILE_SIZE, $ALLOWED_MIME_TYPES;

    // Проверка на пустой файл
    if ($file['size'] === 0) {
        throw new Exception('Файл пустой: ' . $file['name']);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла: ' . getUploadErrorMessage($file['error']));
    }

    if ($file['size'] > $MAX_FILE_SIZE) {
        throw new Exception('Размер файла превышает 5MB. Фактический размер: ' .
            round($file['size'] / 1024 / 1024, 2) . 'MB');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);

    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPEG, PNG, WebP. Получен: ' . $mimeType);
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
    logMessage("Загрузка файла: {$file['name']}");

    // Дополнительная проверка перед чтением файла
    if ($file['size'] === 0) {
        throw new Exception('Файл пустой: ' . $file['name']);
    }

    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        throw new Exception('Не удалось прочитать файл');
    }

    $fileEncoded = base64_encode($fileContent);
    $fileName = sanitizeFileName($file['name']);

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
        logMessage("Ошибка загрузки файла: " . print_r($response, true));
        throw new Exception('Ошибка загрузки файла в Bitrix24: ' . print_r($response, true));
    }

    return $response['result']['ID'];
}

function makeBitrixRequest($url, $params = [])
{
    logMessage("Bitrix запрос: $url, параметры: " . print_r($params, true));

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ];

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("CURL error: $error");
    }

    curl_close($ch);
    logMessage("Bitrix ответ: HTTP $httpCode, " . $response);

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
    $url = $BITRIX_WEBHOOK . 'crm.deal.update.json';

    // Подготовка полей для обновления
    $fields = ['STAGE_ID' => 'WON'];

    // Прикрепляем файлы
    if ($beforeFileId) {
        $fields['UF_CRM_1751200529'] = [$beforeFileId];
        logMessage("Прикрепляем фото до: $beforeFileId к полю UF_CRM_1751200529");
    }

    if ($afterFileId) {
        $fields['UF_CRM_1751200549'] = [$afterFileId];
        logMessage("Прикрепляем фото после: $afterFileId к полю UF_CRM_1751200549");
    }

    $params = ['id' => $dealId, 'fields' => $fields];
    logMessage("Обновление сделки: " . print_r($params, true));

    try {
        $response = makeBitrixRequest($url, $params);

        // Проверяем ответ
        if (isset($response['result']) && $response['result'] === true) {
            return true;
        } else {
            logMessage("Ошибка обновления: " . print_r($response, true));
            return $response; // Возвращаем ответ для анализа
        }
    } catch (Exception $e) {
        throw new Exception("Ошибка обновления сделки: " . $e->getMessage());
    }
}

function getDealInfo($dealId)
{
    global $BITRIX_WEBHOOK;
    $url = $BITRIX_WEBHOOK . 'crm.deal.get.json';
    $params = [
        'id' => $dealId,
        'select' => ['ID', 'TITLE', 'STAGE_ID', 'CONTACT_ID', 'ASSIGNED_BY_ID', 'UF_CRM_1751128612', 'UF_CRM_1751128872']
    ];

    $response = makeBitrixRequest($url, $params);

    if (!isset($response['result'])) {
        throw new Exception('Не удалось получить информацию о сделке');
    }

    $deal = $response['result'];
    $clientInfo = getContactInfo($deal['CONTACT_ID']);
    $performerInfo = getContactInfo($deal['UF_CRM_1751128612']);

    return [
        'deal_id' => $dealId,
        'stage_id' => $deal['STAGE_ID'] ?? '',
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
    $url = $BITRIX_WEBHOOK . 'crm.contact.get.json';
    $params = [
        'id' => $contactId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_1751128872']
    ];

    try {
        $response = makeBitrixRequest($url, $params);
        return $response['result'] ?? [];
    } catch (Exception $e) {
        logMessage("Ошибка получения контакта $contactId: " . $e->getMessage());
        return [];
    }
}

function sendTelegramNotification($chatId, $message)
{
    global $TELEGRAM_BOT_TOKEN;
    logMessage("Отправка Telegram уведомления: $chatId - " . substr($message, 0, 50) . "...");

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
    curl_close($ch);

    logMessage("Результат отправки Telegram: HTTP $httpCode, $response");
    return $httpCode === 200;
}
