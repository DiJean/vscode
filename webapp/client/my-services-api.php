<?php
header('Content-Type: application/json');
session_start();

// Проверка CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_CSRF_TOKEN']) || 
    $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit;
}

// Здесь должна быть логика получения данных из Bitrix
// Для примера возвращаем фиктивные данные
echo json_encode([
    'success' => true,
    'services' => [
        [
            'ID' => 1,
            'TITLE' => 'Уборка участка',
            'DATE_CREATE' => '15.05.2023',
            'STATUS_ID' => 'IN_PROCESS',
            'UF_CRM_1749802456' => '30.05.2023',
            'UF_CRM_1749802469' => 'Москва',
            'UF_CRM_1749802574' => ['Уборка', 'Цветы'],
            'COMMENTS' => 'Тестовый комментарий'
        ]
    ]
]);
?>
