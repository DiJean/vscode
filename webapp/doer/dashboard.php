<?php
header('Content-Type: text/html; charset=utf-8');
$version = $_GET['v'] ?? time();
$tgInitData = $_GET['tgInitData'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель исполнителя</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?=$version?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?=$version?>">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Ваши заказы</div>
        <!-- ... остальной контент ... -->
        <div class="back-button" onclick="goBackToRoleSelection()">
            Сменить роль
        </div>
    </div>

    <script>
        // Восстанавливаем данные авторизации
        const urlParams = new URLSearchParams(window.location.search);
        const tgInitData = urlParams.get('tgInitData');
        
        if (tgInitData) {
            // Сохраняем для последующих страниц
            sessionStorage.setItem('tgInitData', tgInitData);
            
            // Пробуем распарсить данные пользователя
            try {
                const initData = new URLSearchParams(tgInitData);
                const userStr = initData.get('user');
                if (userStr) {
                    const user = JSON.parse(decodeURIComponent(userStr));
                    sessionStorage.setItem('tgUser', JSON.stringify(user));
                    
                    // Обновляем приветствие
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const fullName = `${firstName} ${lastName}`.trim();
                    if (fullName) {
                        document.getElementById('greeting').textContent = `Привет, ${fullName}!`;
                    }
                }
            } catch (e) {
                console.error('Error parsing init data', e);
            }
        }
        
        const tg = window.Telegram.WebApp;
        if (tg) {
            tg.expand();
            
            // Устанавливаем цвета
            try {
                if (tg.setHeaderColor) tg.setHeaderColor('#2575fc');
                if (tg.setBackgroundColor) tg.setBackgroundColor('#2575fc');
            } catch (e) {
                console.log('Color methods not supported');
            }
        }
        
        function goBackToRoleSelection() {
            // Удаляем сохраненную роль
            sessionStorage.removeItem('selectedRole');
            
            // Переходим на главную страницу
            const tgInitData = sessionStorage.getItem('tgInitData') || '';
            window.location.href = `/index.php?tgInitData=${encodeURIComponent(tgInitData)}&v=<?=$version?>`;
        }
    </script>
    
    <style>
        /* Стили для страницы исполнителя */
    </style>
</body>
</html>