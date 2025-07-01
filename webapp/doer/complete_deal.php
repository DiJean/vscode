<?php
header('Content-Type: application/json');

// Основные настройки
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
$FOLDER_ID = 1; // ID папки в Битрикс24 для загрузки файлов
$TELEGRAM_BOT_TOKEN = 'ВАШ_TELEGRAM_BOT_TOKEN'; // Замените на реальный токен бота

// Обработка входящих данных
$dealId = $_POST['deal_id'] ?? null;
$beforePhoto = $_FILES['before_photo'] ?? null;
$afterPhoto = $_FILES['after_photo'] ?? null;

if (!$dealId || !$beforePhoto || !$afterPhoto) {
    echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
    exit;
}

try {
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

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Не удалось обновить сделку']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Функция загрузки файла в Bitrix24
function uploadFileToBitrix($file)
{
    global $BITRIX_WEBHOOK, $FOLDER_ID;

    $fileContent = file_get_contents($file['tmp_name']);
    $fileEncoded = base64_encode($fileContent);

    $url = $BITRIX_WEBHOOK . 'disk.folder.uploadfile.json';
    $params = [
        'id' => $FOLDER_ID,
        'data' => [
            'NAME' => $file['name'],
            'FILE_CONTENT' => $fileEncoded
        ],
        'generateUniqueName' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        throw new Exception('Ошибка загрузки файла: ' . json_encode($result));
    }

    return $result['result']['ID'];
}

// Функция обновления сделки
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
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return isset($result['result']) && $result['result'] === true;
}

// Функция получения информации о сделке
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
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        throw new Exception('Не удалось получить информацию о сделке');
    }

    $deal = $result['result'];

    // Получаем информацию о клиенте
    $clientInfo = getContactInfo($deal['CONTACT_ID']);

    // Получаем информацию об исполнителе
    $performerInfo = getContactInfo($deal['UF_CRM_1751128612']);

    return [
        'deal_id' => $dealId,
        'client_name' => $clientInfo['NAME'] . ' ' . $clientInfo['LAST_NAME'],
        'client_tg_id' => $clientInfo['UF_CRM_1751128872'] ?? null,
        'performer_name' => $performerInfo['NAME'] . ' ' . $performerInfo['LAST_NAME'],
        'performer_tg_id' => $performerInfo['UF_CRM_1751128872'] ?? null,
    ];
}

// Функция получения информации о контакте
function getContactInfo($contactId)
{
    global $BITRIX_WEBHOOK;

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
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!isset($result['result'])) {
        throw new Exception('Не удалось получить информацию о контакте');
    }

    return $result['result'];
}

// Функция отправки уведомления в Telegram
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
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true)['ok'] ?? false;
}
