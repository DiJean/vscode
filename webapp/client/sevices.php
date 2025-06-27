<?php
header('Content-Type: text/html; charset=utf-8');
$version = $_GET['v'] ?? time();
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
        <div class="back-button" onclick="window.location.href='/index.php?v=<?=$version?>'">
            Назад к выбору роли
        </div>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        if (tg) {
            tg.expand();
            tg.setHeaderColor('#6a11cb');
            tg.MainButton.hide();
            
            // Обработчики карточек сервисов
            document.querySelectorAll('.service-card').forEach(card => {
                card.addEventListener('click', () => {
                    const service = card.getAttribute('data-service');
                    const price = card.getAttribute('data-price');
                    
                    if (tg.showPopup) {
                        tg.showPopup({
                            title: 'Подтверждение',
                            message: `Вы выбрали: ${service}\nСтоимость: ${parseInt(price).toLocaleString('ru-RU')} ₽`,
                            buttons: [
                                {id: 'confirm', type: 'ok', text: 'Оформить заказ'},
                                {id: 'cancel', type: 'cancel', text: 'Отмена'}
                            ]
                        }, (buttonId) => {
                            if (buttonId === 'confirm') {
                                window.location.href = `/webapp/client/order.php?service=${encodeURIComponent(service)}&price=${price}&v=<?=$version?>`;
                            }
                        });
                    } else {
                        window.location.href = `/webapp/client/order.php?service=${encodeURIComponent(service)}&price=${price}&v=<?=$version?>`;
                    }
                });
            });
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
            position: relative;
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