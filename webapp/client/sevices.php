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
    <title>Сервисы для клиента</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?=$version?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?=$version?>">
</head>
<body>
    <div class="container">
        <div class="greeting">Доступные сервисы</div>
        <div class="service-list">
            <!-- Содержимое страницы услуг -->
        </div>
        <div class="back-button" onclick="goBack()">
            Назад к выбору роли
        </div>
    </div>

    <script>
        // Восстанавливаем данные авторизации
        const urlParams = new URLSearchParams(window.location.search);
        const tgInitData = urlParams.get('tgInitData');
        
        if (tgInitData) {
            sessionStorage.setItem('tgInitData', tgInitData);
            
            // Пробуем распарсить данные пользователя
            try {
                const initData = new URLSearchParams(tgInitData);
                const userStr = initData.get('user');
                if (userStr) {
                    sessionStorage.setItem('tgUser', userStr);
                }
            } catch (e) {
                console.error('Error parsing init data', e);
            }
        }
        
        const tg = window.Telegram.WebApp;
        if (tg) {
            tg.expand();
            
            // Попробуем инициализировать WebApp с сохраненными данными
            if (tgInitData && tg.initData !== tgInitData) {
                try {
                    // Для новых версий SDK
                    if (tg.initData !== tgInitData) {
                        tg.initData = tgInitData;
                        tg.initDataUnsafe = Object.fromEntries(new URLSearchParams(tgInitData));
                        
                        if (tg.initDataUnsafe.user) {
                            try {
                                tg.initDataUnsafe.user = JSON.parse(tg.initDataUnsafe.user);
                            } catch (e) {
                                console.error('Error parsing user data', e);
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error setting initData', e);
                }
            }
            
            // Показываем данные пользователя в заголовке
            if (tg.initDataUnsafe?.user) {
                const user = tg.initDataUnsafe.user;
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                if (fullName) {
                    document.querySelector('.greeting').textContent = `Привет, ${fullName}!`;
                }
            }
            
            // Инициализация кнопок и сервисов
            initServices();
        }
        
        function initServices() {
            // Ваш код инициализации сервисов
            console.log('Services initialized');
        }
        
        function goBack() {
            const tgInitData = sessionStorage.getItem('tgInitData') || '';
            window.location.href = `/index.php?tgInitData=${encodeURIComponent(tgInitData)}&v=<?=$version?>`;
        }
    </script>
    
    <style>
        /* Ваши стили */
    </style>
</body>
</html>