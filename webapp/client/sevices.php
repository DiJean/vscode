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
            <div class="request-item">
                <div class="request-service">Загрузка ваших запросов...</div>
            </div>
        </div>
    </div>

    <script src="/webapp/js/bitrix-integration.js"></script>
    <script>
        // Обработчик для проверки загрузки модуля CRM
        function checkCrmModule() {
            if (typeof BitrixCRM === 'undefined') {
                console.error('BitrixCRM module not loaded');
                return false;
            }
            
            if (!BitrixCRM.getUserRequests) {
                console.error('getUserRequests function missing');
                return false;
            }
            
            return true;
        }

        // Показать ошибку загрузки модуля
        function showModuleError() {
            const container = document.querySelector('.container');
            container.innerHTML = `
                <div class="greeting">Ошибка!</div>
                <div style="color: #ff6b6b; text-align: center; padding: 20px;">
                    <p>Не удалось загрузить модуль интеграции с CRM</p>
                    <p>Попробуйте перезагрузить страницу</p>
                    <button onclick="window.location.reload()" style="margin-top: 20px; padding: 12px 24px; background: #6a11cb; color: white; border-radius: 12px;">
                        Перезагрузить
                    </button>
                </div>
            `;
        }

        // Форматирование статуса заявки
        function formatStatus(status) {
            const statusMap = {
                'NEW': 'Новая',
                'PROCESSED': 'В обработке',
                'FINALIZED': 'Завершена',
                'JUNK': 'Невалидная'
            };
            return statusMap[status] || status;
        }

        document.addEventListener('DOMContentLoaded', async function() {
            // Проверяем загрузку модуля CRM
            if (!checkCrmModule()) {
                showModuleError();
                return;
            }

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
                // Используем функцию из модуля CRM
                const response = await BitrixCRM.getUserRequests(email);
                
                if (response.error) {
                    throw new Error(response.message);
                }
                
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
                        const date = new Date(lead.DATE_CREATE).toLocaleDateString('ru-RU', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        
                        // Получаем услугу (если есть)
                        const service = lead.UF_CRM_685D2956C64E0 || 'Услуга не указана';
                        
                        requestsHtml += `
                            <div class="request-item">
                                <div class="request-service">${service}</div>
                                <div class="request-date">Создано: ${date}</div>
                                <div class="request-status">Статус: ${formatStatus(lead.STATUS_ID)}</div>
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
                        <div class="request-status">${error.message || 'Попробуйте позже'}</div>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>