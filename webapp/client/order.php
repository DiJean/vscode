<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать запрос</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <style>
        /* ... (стили остаются без изменений) ... */
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">Новый запрос</div>
        
        <div class="form-container">
            <form id="request-form">
                <!-- ... (форма без изменений) ... -->
            </form>
        </div>
    </div>

    <script>
        // Функция для отображения ошибки загрузки модуля
        function showModuleError(message) {
            const container = document.querySelector('.container');
            container.innerHTML = `
                <div class="greeting">Ошибка!</div>
                <div class="form-container" style="color: #ff6b6b; text-align: center; padding: 20px;">
                    <p>${message}</p>
                    <p>Попробуйте перезагрузить страницу</p>
                    <button onclick="window.location.reload()" style="margin-top: 20px; padding: 12px 24px; background: #6a11cb; color: white; border-radius: 12px;">
                        Перезагрузить
                    </button>
                </div>
            `;
        }

        // Основная функция инициализации приложения
        function initApp() {
            // Проверяем загрузку модуля CRM
            if (typeof BitrixCRM === 'undefined' || typeof BitrixCRM.createServiceRequest !== 'function') {
                showModuleError('Модуль интеграции с CRM не загружен');
                return;
            }

            const form = document.getElementById('request-form');
            const tg = window.Telegram && Telegram.WebApp;
            
            // Если пользователь авторизован в Telegram
            if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const user = tg.initDataUnsafe.user;
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                
                // Автозаполнение имени, если доступно
                if (firstName || lastName) {
                    document.getElementById('fullName').value = `${firstName} ${lastName}`.trim();
                }
            }
            
            // Установка текущей даты как минимальной для выбора
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('serviceDate').min = today;
            
            // Обработка отправки формы
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Показываем индикатор загрузки
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
                
                // Собираем данные формы
                const formData = {
                    fullName: document.getElementById('fullName').value,
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    service: document.getElementById('service').value,
                    serviceDate: document.getElementById('serviceDate').value,
                    city: document.getElementById('city').value,
                    cemetery: document.getElementById('cemetery').value,
                    sector: document.getElementById('sector').value,
                    row: document.getElementById('row').value,
                    plot: document.getElementById('plot').value,
                    comments: document.getElementById('comments').value
                };
                
                try {
                    // Используем функцию из модуля CRM
                    const result = await BitrixCRM.createServiceRequest(formData);
                    
                    if (result.result) {
                        // Сохраняем email для последующего использования
                        localStorage.setItem('userEmail', formData.email);
                        
                        // Показываем уведомление
                        if (tg && tg.showAlert) {
                            tg.showAlert('✅ Запрос успешно создан!');
                        } else {
                            alert('✅ Запрос успешно создан!');
                        }
                        
                        // Возвращаемся к списку сервисов
                        setTimeout(() => {
                            window.location.href = '/webapp/client/services.php';
                        }, 1500);
                    } else {
                        console.error('Bitrix24 error:', result);
                        const errorMsg = `❌ Ошибка: ${result.error_description || 'Неизвестная ошибка'}`;
                        
                        if (tg && tg.showAlert) {
                            tg.showAlert(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                        
                        // Восстанавливаем кнопку
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить запрос';
                    }
                } catch (error) {
                    console.error('Request failed:', error);
                    const errorMsg = '🚫 Ошибка сети или сервера. Попробуйте позже.';
                    
                    if (tg && tg.showAlert) {
                        tg.showAlert(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Восстанавливаем кнопку
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Отправить запрос';
                }
            });
        }

        // Обработчик события загрузки модуля CRM
        document.addEventListener('BitrixCRMLoaded', function() {
            console.log('Событие BitrixCRMLoaded получено');
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