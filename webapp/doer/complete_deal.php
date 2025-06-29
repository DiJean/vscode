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
        sendTelegramNotification(
            $dealInfo['performer_tg_id'],
            "✅ Вы завершили заказ #{$dealId}\nКлиент: {$dealInfo['client_name']}"
        );
        
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

// Функция загрузки файла в Битрикс24
function uploadFileToBitrix($file) {
    global $BITRIX_WEBHOOK, $FOLDER_ID;
    
    $uploadUrl = $BITRIX_WEBHOOK . 'disk.folder.uploadfile.json';
    $postFields = [
        'id' => $FOLDER_ID,
        'data' => json_encode(['NAME' => $file['name']]),
        'fileContent' => base64_encode(file_get_contents($file['tmp_name']))
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['result']['ID'])) {
        return $result['result']['ID'];
    } else {
        throw new Exception('Ошибка загрузки файла: ' . ($result['error_description'] ?? 'Неизвестная ошибка'));
    }
}

// Функция обновления сделки
function updateDeal($dealId, $beforeFileId, $afterFileId) {
    global $BITRIX_WEBHOOK;
    
    $updateUrl = $BITRIX_WEBHOOK . 'crm.deal.update.json';
    $postFields = [
        'id' => $dealId,
        'fields' => [
            'UF_CRM_1751200529' => [$beforeFileId], // Фото до работ
            'UF_CRM_1751200549' => [$afterFileId],  // Фото после работ
            'STAGE_ID' => 'WON' // Успешно завершена
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $updateUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['result']) && $result['result'] === true;
}

// Функция получения информации о сделке
function getDealInfo($dealId) {
    global $BITRIX_WEBHOOK;
    
    $url = $BITRIX_WEBHOOK . 'crm.deal.get.json';
    $params = [
        'id' => $dealId,
        'select' => [
            'ID', 
            'TITLE',
            'CONTACT_ID',
            'ASSIGNED_BY_ID'
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
    $performerInfo = getUserInfo($deal['ASSIGNED_BY_ID']);
    
    return [
        'deal_id' => $dealId,
        'client_name' => $clientInfo['NAME'] . ' ' . $clientInfo['LAST_NAME'],
        'client_tg_id' => $clientInfo['UF_CRM_TG_USER_ID'] ?? null,
        'performer_name' => $performerInfo['NAME'] . ' ' . $performerInfo['LAST_NAME'],
        'performer_tg_id' => $performerInfo['UF_CRM_TG_USER_ID'] ?? null,
    ];
}

// Функция получения информации о контакте
function getContactInfo($contactId) {
    global $BITRIX_WEBHOOK;
    
    $url = $BITRIX_WEBHOOK . 'crm.contact.get.json';
    $params = [
        'id' => $contactId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_TG_USER_ID']
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

// Функция получения информации о пользователе
function getUserInfo($userId) {
    global $BITRIX_WEBHOOK;
    
    $url = $BITRIX_WEBHOOK . 'user.get.json';
    $params = [
        'id' => $userId,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_TG_USER_ID']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!isset($result['result'][0])) {
        throw new Exception('Не удалось получить информацию о пользователе');
    }
    
    return $result['result'][0];
}

// Функция отправки уведомления в Telegram
function sendTelegramNotification($chatId, $message) {
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
?>