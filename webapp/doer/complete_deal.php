<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$logFile = __DIR__ . '/complete_deal.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Начало обработки запроса\n", FILE_APPEND);

function logMessage($message)
{
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
$TELEGRAM_BOT_TOKEN = 'bot:1845249310:AAGgqxI9crjWVgyCXlve0BDGssGgEANhh3g';

try {
    logMessage("Получен запрос: " . print_r($_POST, true));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Недопустимый метод запроса');
    }

    $dealId = $_POST['deal_id'] ?? null;
    $tgUserId = $_POST['tg_user_id'] ?? null;

    if (!$dealId) throw new Exception('Не указан ID заявки');
    if (!$tgUserId) throw new Exception('Не указан идентификатор пользователя');

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

    // Обновление сделки - только смена статуса
    $updateResult = updateDeal($dealId);
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
        throw new Exception('Не удалось обновить сделку');
    }
} catch (Exception $e) {
    http_response_code(400);
    logMessage("ОШИБКА: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка: ' . $e->getMessage()
    ]);
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

function updateDeal($dealId)
{
    global $BITRIX_WEBHOOK;
    $url = $BITRIX_WEBHOOK . 'crm.deal.update.json';
    $params = ['id' => $dealId, 'fields' => ['STAGE_ID' => 'WON']];

    logMessage("Обновление сделки: " . print_r($params, true));

    try {
        $response = makeBitrixRequest($url, $params);
        return isset($response['result']) && $response['result'] === true;
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
        'select' => ['ID', 'TITLE', 'STAGE_ID', 'CONTACT_ID', 'UF_CRM_1751128612', 'UF_CRM_1751128872']
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
