<?php
header('Content-Type: text/html; charset=utf-8');
$version = $_GET['v'] ?? time();
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
        <div class="greeting">Ваши заказы</div>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">5</div>
                <div class="stat-label">Активные</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">12</div>
                <div class="stat-label">Завершённые</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">4.8</div>
                <div class="stat-label">Рейтинг</div>
            </div>
        </div>
        
        <div class="order-list">
            <div class="order-card">
                <h3>Дизайн логотипа</h3>
                <div class="order-meta">
                    <span class="order-status new">Новый</span>
                    <span class="order-price">5 000 ₽</span>
                </div>
                <p>Срок: 3 дня</p>
            </div>
            <div class="order-card">
                <h3>Разработка сайта</h3>
                <div class="order-meta">
                    <span class="order-status progress">В работе</span>
                    <span class="order-price">25 000 ₽</span>
                </div>
                <p>Срок: 14 дней</p>
            </div>
            <div class="order-card">
                <h3>SEO оптимизация</h3>
                <div class="order-meta">
                    <span class="order-status completed">Завершён</span>
                    <span class="order-price">15 000 ₽</span>
                </div>
                <p>Оценка: ★★★★★</p>
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
            tg.setHeaderColor('#2575fc');
            tg.MainButton.hide();
            
            // Инициализация темы
            function initTheme() {
                document.body.classList.toggle('dark', tg.colorScheme === 'dark');
            }
            
            initTheme();
            tg.onEvent('themeChanged', initTheme);
        }
        
        // Обработчики карточек заказов
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('click', () => {
                const orderName = card.querySelector('h3').textContent;
                if (tg && tg.showPopup) {
                    tg.showPopup({
                        title: 'Детали заказа',
                        message: `Заказ: ${orderName}\nСтатус: ${card.querySelector('.order-status').textContent}`,
                        buttons: [
                            {id: 'details', type: 'default', text: 'Подробнее'},
                            {id: 'close', type: 'cancel', text: 'Закрыть'}
                        ]
                    }, (buttonId) => {
                        if (buttonId === 'details') {
                            alert(`Детали заказа "${orderName}"`);
                        }
                    });
                } else {
                    alert(`Детали заказа "${orderName}"`);
                }
            });
        });
    </script>
    
    <style>
        .stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 10px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .order-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .order-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .order-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .order-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .new { background: rgba(40, 167, 69, 0.3); }
        .progress { background: rgba(255, 193, 7, 0.3); }
        .completed { background: rgba(108, 117, 125, 0.3); }
        
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