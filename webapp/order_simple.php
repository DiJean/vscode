<?php
// order_simple.php
session_start();

// Проверяем и получаем данные из сессии
if (empty($_SESSION['tg_data'])) {
    // Логирование ошибки отсутствия данных
    $log_dir = '/var/www/vm20c2.ru/logs';
    file_put_contents("$log_dir/error.log", date('[Y-m-d H:i:s]') . " No session data\n", FILE_APPEND);
    
    // Показываем ошибку пользователю
    die("<h1>Ошибка: Данные сессии не найдены</h1><p>Пожалуйста, откройте Web App через кнопку в боте</p>");
}

$tgData = $_SESSION['tg_data'];
unset($_SESSION['tg_data']); // Очищаем сразу после получения

// Извлекаем данные
$tgWebAppData = $tgData['tgWebAppData'] ?? '';
$tgWebAppVersion = $tgData['tgWebAppVersion'] ?? '';
$tgWebAppPlatform = $tgData['tgWebAppPlatform'] ?? '';
$tgUser = [];

if (isset($tgData['user'])) {
    $user_json = urldecode($tgData['user']);
    $tgUser = json_decode($user_json, true) ?? [];
}

// Логирование полученных данных
file_put_contents("$log_dir/webapp_data.log", print_r($tgData, true), FILE_APPEND);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Telegram WebApp Test</title>
    <script src="https://telegram.org/js/telegram-web-app.js?3"></script>
    <style>
        /* Ваши стили */
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h1>Telegram WebApp Test</h1>
            
            <div class="status">
                <h2>Telegram Data:</h2>
                <p><strong>Platform:</strong> <span id="platform"><?= htmlspecialchars($tgWebAppPlatform) ?></span></p>
                <p><strong>Version:</strong> <span id="version"><?= htmlspecialchars($tgWebAppVersion) ?></span></p>
                <p><strong>User Data:</strong></p>
                <pre id="userData"><?= json_encode($tgUser, JSON_PRETTY_PRINT) ?></pre>
            </div>
            
            <button id="submitBtn">Отправить тестовые данные</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const tg = window.Telegram.WebApp;
        
        // Инициализация
        if (tg) {
            tg.ready();
            tg.expand();
            
            // Обработчик кнопки
            document.getElementById('submitBtn').addEventListener('click', () => {
                tg.sendData(JSON.stringify({
                    action: 'test',
                    timestamp: Date.now()
                }));
                tg.close();
            });
        }
    });
    </script>
<script>
const tgParams = localStorage.getItem('tg_params');
if (tgParams) {
    const params = JSON.parse(tgParams);
    console.log('Telegram params:', params);
    // Далее используйте params
    localStorage.removeItem('tg_params'); // Очищаем после использования
}
</script>
</body>
</html>
