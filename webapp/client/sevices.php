<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои запросы</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <style>
        .btn-create {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            text-align: center;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }
        
        .requests-list {
            margin-top: 30px;
        }
        
        .request-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .request-service {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .request-date {
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .request-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">Мои запросы</div>
        
        <a href="/webapp/client/order.php" class="btn-create">+ Создать новый запрос</a>
        
        <div class="requests-list" id="requests-list">
            <!-- Сюда будут загружаться заявки -->
            <div class="request-item">
                <div class="request-service">Загрузка...</div>
            </div>
        </div>
    </div>

    <script src="/webapp/js/bitrix-integration.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const tg = window.Telegram && Telegram.WebApp;
            const email = localStorage.getItem('userEmail');
            
            if (!email) {
                document.getElementById('requests-list').innerHTML = `
                    <div class="request-item">
                        <div class="request-service">Вы еще не создавали заявок</div>
                    </div>
                `;
                return;
            }
            
            try {
                // Используем функцию getUserRequests из bitrix-integration.js
                const response = await getUserRequests(email);
                const leads = response.result || [];
                
                let requestsHtml = '';
                
                if (leads.length === 0) {
                    requestsHtml = `
                        <div class="request-item">
                            <div class="request-service">У вас пока нет заявок</div>
                        </div>
                    `;
                } else {
                    leads.forEach(lead => {
                        // Форматируем дату
                        const date = new Date(lead.DATE_CREATE).toLocaleDateString('ru-RU');
                        // Получаем услугу (если есть)
                        const service = lead.UF_CRM_685D2956C64E0 || 'Не указано';
                        
                        requestsHtml += `
                            <div class="request-item">
                                <div class="request-service">${service}</div>
                                <div class="request-date">Создано: ${date}</div>
                                <div class="request-status">Статус: ${lead.STATUS_ID || 'Новый'}</div>
                            </div>
                        `;
                    });
                }
                
                document.getElementById('requests-list').innerHTML = requestsHtml;
                
            } catch (error) {
                console.error('Ошибка при загрузке заявок:', error);
                document.getElementById('requests-list').innerHTML = `
                    <div class="request-item">
                        <div class="request-service">Ошибка загрузки данных</div>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>