<?php
header("Access-Control-Allow-Origin: https://web.telegram.org");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
// Функции для работы с Bitrix24 API

/**
 * Создание лида в Bitrix24
 */
function createBitrixLead($data) {
    $leadData = [
        'TITLE' => 'Заказ на услуги: ' . $data['service'],
        'NAME' => $data['name'] ?? 'Клиент Telegram',
        'SOURCE_DESCRIPTION' => "Город: {$data['city']}\nКладбище: {$data['cemetery']}\n"
                               . "Участок: {$data['section']}, Ряд: {$data['row']}, Номер: {$data['number']}\n"
                               . "Дата: {$data['date']}\nПожелания: {$data['notes']}",
        'COMMENTS' => "Заказ создан через Telegram Web App",
        'ASSIGNED_BY_ID' => 1 // ID ответственного
    ];
    
    return callBitrixAPI('crm.lead.add', ['fields' => $leadData]);
}

/**
 * Получение информации о лиде (заказе)
 */
function getBitrixLead($leadId) {
    return callBitrixAPI('crm.lead.get', ['id' => $leadId]);
}

/**
 * Общий метод для вызова Bitrix24 API
 */
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'error' => 'HTTP Error ' . $httpCode,
            'error_description' => 'Ошибка соединения с Bitrix24'
        ];
    }
    
    return json_decode($response, true);
}

/**
 * Создание комментария к задаче с фото
 */
function addTaskCommentWithPhoto($taskId, $comment, $photoUrl) {
    return callBitrixAPI('task.commentitem.add', [
        'TASKID' => $taskId,
        'FIELDS' => [
            'POST_MESSAGE' => $comment,
            'UF_FORUM_MESSAGE_DOC' => [["fileURL" => $photoUrl]]
        ]
    ]);
}
?>
