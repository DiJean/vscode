<?php
header('Content-Type: application/json');

// Основные настройки
$BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
$FOLDER_ID = 1; // ID папки в Битрикс24 для загрузки файлов

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
?>