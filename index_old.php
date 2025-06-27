<?php
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
header("Access-Control-Allow-Origin: https://web.telegram.org");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: text/html; charset=utf-8');
<script src="https://telegram.org/js/telegram-web-app.js?2"></script>
// Конфигурация
define('BOT_TOKEN', 'bot1845249310:AAGgqxI9crjWVgyCXlve0BDGssGgEANhh3g');
define('BITRIX_WEBHOOK', 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/');
define('ADMIN_CHAT_ID', '155393476');
define('WEBAPP_BASE_URL', 'https://vm20c2.ru/webapp/');
//define('WEBAPP_BASE_URL', 'https://vm20c2.ru/webapp/');
// Обработка входящих запросов
$webapp_url = 'https://vm20c2.ru/telegram_gateway.php';
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if ($update) {
    processTelegramUpdate($update);
} else {
    echo "OK";
}

function processTelegramUpdate($update) {
    try {
        // Обработка данных из Web App
        if (isset($update['web_app_data'])) {
            handleWebAppData($update);
            return;
        }
        
        if (!isset($update['message'])) return;
        
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $photo = $message['photo'] ?? null;
        $location = $message['location'] ?? null;

        // Обработка команд
        if (strpos($text, '/') === 0) {
            handleCommand($chatId, $text);
        } 
        // Обработка фотоотчетов от исполнителей
        elseif ($photo) {
            handlePhotoReport($chatId, $photo, $location);
        }
        // Обработка текстовых сообщений
        else {
            handleTextMessage($chatId, $text);
        }
    } catch (Exception $e) {
        sendTelegramMessage(ADMIN_CHAT_ID, "Ошибка: " . $e->getMessage());
    }
}

function handleCommand($chatId, $command) {
    switch ($command) {
        case '/start':
            $response = "👋 Здравствуйте! Я ваш помощник по уходу за могилами.\n\n"
                      . "Для заказчиков:\n"
                      . "/order - Создать новый заказ\n"
                      . "/status - Проверить статус\n\n"
                      . "Для исполнителей:\n"
                      . "/report - Отправить отчет";
            sendTelegramMessage($chatId, $response);
            break;
            
        case '/order':
            $keyboard = [
                'inline_keyboard' => [
                    [[
                        'text' => '📝 Создать заказ',
                        'web_app' => ['url' => WEBAPP_BASE_URL . 'order.php']
                    ]]
                ]
            ];
            sendTelegramMessage($chatId, "Нажмите кнопку ниже чтобы создать заказ:", $keyboard);
            break;
            
        case '/status':
            $keyboard = [
                'inline_keyboard' => [
                    [[
                        'text' => '🔍 Проверить статус',
                        'web_app' => ['url' => WEBAPP_BASE_URL . 'status.php']
                    ]]
                ]
            ];
            sendTelegramMessage($chatId, "Нажмите кнопку ниже чтобы проверить статус заказа:", $keyboard);
            break;
            
        case '/report':
            $keyboard = [
                'inline_keyboard' => [
                    [[
                        'text' => '📸 Отправить отчет',
                        'web_app' => ['url' => WEBAPP_BASE_URL . 'report.php']
                    ]]
                ]
            ];
            sendTelegramMessage($chatId, "Нажмите кнопку ниже чтобы отправить отчет:", $keyboard);
            break;
            
        default:
            if (strpos($command, '/status') === 0) {
                $orderId = trim(str_replace('/status', '', $command));
                $response = getOrderStatus($orderId);
                sendTelegramMessage($chatId, $response);
            } else {
                sendTelegramMessage($chatId, "❌ Неизвестная команда. Используйте /start для списка команд");
            }
    }
}

function handleWebAppData($update) {
    $chatId = $update['message']['from']['id'];
    $data = json_decode($update['web_app_data']['data'], true);
    
    switch ($data['action']) {
        case 'create_order':
            createBitrixLead($chatId, $data);
            break;
            
        case 'get_status':
            $status = getOrderStatus($data['order_id']);
            sendTelegramMessage($chatId, $status);
            break;
            
        case 'submit_report':
            $taskId = $data['task_id'];
            $photoUrl = $data['photo_url'];
            $location = $data['location'];
            
            $bitrixResponse = callBitrixAPI('task.commentitem.add', [
                'TASKID' => $taskId,
                'FIELDS' => [
                    'POST_MESSAGE' => "Фотоотчет от исполнителя\nГеолокация: {$location['latitude']}, {$location['longitude']}",
                    'UF_FORUM_MESSAGE_DOC' => [["fileURL" => $photoUrl]]
                ]
            ]);
            
            if (isset($bitrixResponse['error'])) {
                sendTelegramMessage($chatId, "❌ Ошибка при отправке отчета: " . $bitrixResponse['error_description']);
            } else {
                sendTelegramMessage($chatId, "✅ Отчет успешно отправлен!");
            }
            break;
    }
}

function createBitrixLead($chatId, $data) {
    $leadData = [
        'TITLE' => 'Заказ на услуги: ' . $data['service'],
        'NAME' => 'Клиент Telegram',
        'SOURCE_DESCRIPTION' => "Город: {$data['city']}\nКладбище: {$data['cemetery']}\nУчасток: {$data['section']}, Ряд: {$data['row']}, Номер: {$data['number']}\nДата: {$data['date']}\nПожелания: {$data['notes']}",
        'COMMENTS' => "Заказ создан через Telegram Web App",
        'ASSIGNED_BY_ID' => 1
    ];
    
    $response = callBitrixAPI('crm.lead.add', ['fields' => $leadData]);
    
    if (isset($response['result'])) {
        $leadId = $response['result'];
        sendTelegramMessage($chatId, "✅ Заявка #$leadId создана! Менеджер свяжется для уточнения деталей.");
    } else {
        $error = $response['error_description'] ?? 'Неизвестная ошибка';
        sendTelegramMessage($chatId, "❌ Ошибка при создании заявки: $error");
    }
}

function getOrderStatus($orderId) {
    $response = callBitrixAPI('crm.lead.get', ['id' => $orderId]);
    
    if (isset($response['result'])) {
        $lead = $response['result'];
        $status = $lead['STATUS_ID'] ?? 'в обработке';
        $stage = $lead['STAGE_ID'] ?? 'не назначен';
        $assigned = $lead['ASSIGNED_BY_NAME'] ?? 'не назначен';
        
        $response = "📊 Информация о заказе #$orderId:\n";
        $response .= "• Статус: " . strtoupper($status) . "\n";
        $response .= "• Этап: $stage\n";
        $response .= "• Ответственный: $assigned\n";
        
        if (!empty($lead['COMMENTS'])) {
            $response .= "\nКомментарий менеджера:\n" . $lead['COMMENTS'];
        }
        
        return $response;
    }
    return "❌ Заказ #$orderId не найден";
}

function callBitrixAPI($method, $params) {
    $url = BITRIX_WEBHOOK . $method . '.json';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function sendTelegramMessage($chatId, $text, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_exec($ch);
    curl_close($ch);
}
?>
