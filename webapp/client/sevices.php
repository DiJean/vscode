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
        /* ... (стили остаются без изменений) ... */
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

    <script>
        // Функция для отображения ошибки загрузки модуля
        function showModuleError(message) {
            const container = document.querySelector('.container');
            container.innerHTML = `
                <div class="greeting">Ошибка!</div>
                <div style="color: #ff6b6b; text-align: center; padding: 20px;">
                    <p>${message}</p>
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

        // Основная функция инициализации приложения
        async function initApp() {
            // Проверяем загрузку модуля CRM
            if (typeof BitrixCRM === 'undefined' || typeof BitrixCRM.getUserRequests !== 'function') {
                showModuleError('Модуль интеграции с CRM не загружен');
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
        }

        // Обработчик события загрузки модуля CRM
        document.addEventListener('BitrixCRMLoaded', function() {
            console.log('Событие BitrixCRMLoaded получено (services)');
            if (typeof BitrixCRM !== 'undefined') {
                initApp();
            } else {
                showModuleError('Модуль CRM загружен, но не инициализирован');
            }
        });

        // Загрузка скрипта CRM с обработкой ошибок
        function loadCrmModule() {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = '/webapp/js/bitrix-integration.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // Основной скрипт
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Загружаем модуль CRM
                await loadCrmModule();
                
                // Устанавливаем таймаут на случай, если событие не придет
                setTimeout(() => {
                    if (typeof BitrixCRM === 'undefined') {
                        showModuleError('Модуль CRM не загрузился в течение 3 секунд');
                    }
                }, 3000);
            } catch (error) {
                console.error('Ошибка загрузки скрипта CRM:', error);
                showModuleError('Не удалось загрузить модуль CRM');
            }
        });
    </script>
</body>
</html>