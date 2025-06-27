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
        <div class="greeting" id="greeting">Доступные сервисы</div>
        <div class="service-list">
            <div class="service-card" data-service="Дизайн" data-price="5000">
                <h3>Дизайн</h3>
                <p>Логотипы, брендинг, UI/UX</p>
                <div class="price">5 000 ₽</div>
            </div>
            <div class="service-card" data-service="Разработка сайта" data-price="25000">
                <h3>Разработка</h3>
                <p>Сайты, приложения, боты</p>
                <div class="price">25 000 ₽</div>
            </div>
            <div class="service-card" data-service="Маркетинг" data-price="15000">
                <h3>Маркетинг</h3>
                <p>SMM, SEO, реклама</p>
                <div class="price">15 000 ₽</div>
            </div>
            <div class="service-card" data-service="Консультация" data-price="3000">
                <h3>Консультации</h3>
                <p>Экспертные советы</p>
                <div class="price">3 000 ₽</div>
            </div>
        </div>
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
                if (tg.setHeaderColor) tg.setHeaderColor('#6a11cb');
                if (tg.setBackgroundColor) tg.setBackgroundColor('#6a11cb');
            } catch (e) {
                console.log('Color methods not supported');
            }
            
            // Инициализация сервисов
            initServices();
        }
        
        function initServices() {
            // Обработчики карточек сервисов
            document.querySelectorAll('.service-card').forEach(card => {
                card.addEventListener('click', () => {
                    const service = card.getAttribute('data-service');
                    const price = card.getAttribute('data-price');
                    
                    // Переходим на страницу заказа с передачей данных
                    const tgInitData = sessionStorage.getItem('tgInitData') || '';
                    window.location.href = `/webapp/client/order.php?service=${encodeURIComponent(service)}&price=${price}&tgInitData=${encodeURIComponent(tgInitData)}&v=<?=$version?>`;
                });
            });
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
        .service-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .service-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .service-card h3 {
            font-size: 1.4rem;
            margin-bottom: 8px;
        }
        
        .service-card p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4ade80;
        }
        
        .back-button {
            display: block;
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-align: center;
            border-radius: 16px;
            margin-top: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</body>
</html>