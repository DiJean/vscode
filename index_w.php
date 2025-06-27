<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Персональные приветствия</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            text-align: center;
            color: white;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin: 20px 0;
        }
        
        .greeting {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;
            margin: 0 auto 15px;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5rem;
            overflow: hidden;
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-size: 1.8rem;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .username {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .welcome-text {
            font-size: 1.5rem;
            line-height: 1.4;
            margin-top: 25px;
        }
        
        .heart {
            color: #ff2e63;
            display: inline-block;
        }
        
        .desktop-warning {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            max-width: 500px;
        }

        /* Стили для выбора роли */
        .role-selection {
            margin: 25px 0;
            width: 100%;
        }
        
        .role-label {
            display: block;
            margin-bottom: 12px;
            font-size: 1.3rem;
            font-weight: 500;
        }
        
        .role-select {
            width: 100%;
            padding: 14px 18px;
            border-radius: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 1.1rem;
            appearance: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.25);
        }
        
        .role-select option {
            background: rgba(106, 17, 203, 0.9);
            color: white;
            padding: 10px;
        }
        
        .role-error {
            color: #ff2e63;
            margin-top: 10px;
            font-weight: 500;
            display: none;
        }
        
        @media (max-width: 480px) {
            .greeting { font-size: 2rem; }
            .user-name { font-size: 1.5rem; }
            .username { font-size: 1rem; }
            .welcome-text { font-size: 1.2rem; }
            .role-label { font-size: 1.1rem; }
            .role-select { padding: 12px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Привет!</div>
        
        <div id="user-container">
            <!-- Здесь отобразится аватар и информация о пользователе -->
        </div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script>
        // Основная функция инициализации приложения
        function initApp() {
            // Проверка наличия Telegram WebApp API
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const tg = Telegram.WebApp;
            
            try {
                // Инициализация WebApp
                tg.ready();
                
                // Пытаемся раскрыть на весь экран
                if (tg.isExpanded !== true && tg.expand) {
                    tg.expand();
                }
                
                // Установка цвета фона
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) {
                    tg.setHeaderColor('#6a11cb');
                }
                
                // Получаем данные пользователя
                let user = null;
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                    user = tg.initDataUnsafe.user;
                }
                
                // Генерировать HTML для пользователя
                let userHtml = '';
                
                if (user) {
                    // Если есть данные пользователя
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const username = user.username ? `@${user.username}` : 'без username';
                    const fullName = `${firstName} ${lastName}`.trim();
                    
                    // Формируем приветствие
                    const greeting = fullName ? `Привет, ${fullName}!` : 'Привет!';
                    document.getElementById('greeting').textContent = greeting;
                    
                    // Формируем аватар
                    userHtml += `
                        <div class="avatar">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}">` : 
                                `<div>${firstName.charAt(0) || 'Г'}</div>`
                            }
                        </div>
                        <div class="user-name">${fullName || 'Анонимный пользователь'}</div>
                        <div class="username">${username}</div>
                    `;
                } else {
                    // Если данные пользователя недоступны
                    userHtml = `
                        <div class="avatar">Г</div>
                        <div class="user-name">Гость</div>
                    `;
                }
                
                // Добавляем блок выбора роли
                userHtml += `
                    <div class="role-selection">
                        <div class="role-label">Выберите роль:</div>
                        <select class="role-select" id="role">
                            <option value="" disabled selected>Выберите роль...</option>
                            <option value="client">Клиент</option>
                            <option value="performer">Исполнитель</option>
                        </select>
                        <div class="role-error" id="role-error">Выберите роль!</div>
                    </div>
                    <div class="welcome-text">
                        Мы рады видеть вас здесь! <span class="heart">❤️</span>
                    </div>
                `;
                
                // Отображаем информацию о пользователе
                document.getElementById('user-container').innerHTML = userHtml;
                
                // Настройка кнопки
                if (tg.MainButton) {
                    tg.MainButton.setText("Продолжить");
                    
                    // Обработчик для кнопки
                    tg.MainButton.onClick(async function() {
                        const roleSelect = document.getElementById('role');
                        const selectedRole = roleSelect.value;
                        
                        if (!selectedRole) {
                            document.getElementById('role-error').style.display = 'block';
                            return;
                        }
                        
                        // Получаем данные пользователя
                        const userData = {
                            role: selectedRole,
                            userId: user?.id || null,
                            firstName: user?.first_name || null,
                            lastName: user?.last_name || null,
                            username: user?.username || null,
                            timestamp: new Date().toISOString()
                        };
                        
                        try {
                            // Отправляем данные в Bitrix24
                            const bitrixResponse = await sendToBitrix24(userData);
                            
                            if (!bitrixResponse.ok) {
                                console.error('Ошибка интеграции с Bitrix24');
                            }
                        } catch (error) {
                            console.error('Ошибка при отправке в Bitrix24:', error);
                        }
                        
                        // Отправляем данные в бота
                        tg.sendData(JSON.stringify(userData));
                        
                        // Сохраняем в локальное хранилище
                        localStorage.setItem('selectedRole', selectedRole);
                        
                        // Закрываем приложение
                        tg.close();
                    });
                    
                    // Показываем кнопку
                    tg.MainButton.show();
                }
                
                // Восстановление сохраненной роли
                const roleSelect = document.getElementById('role');
                const savedRole = localStorage.getItem('selectedRole');
                
                if (savedRole) {
                    roleSelect.value = savedRole;
                }
                
                // Скрываем ошибку при изменении выбора
                roleSelect.addEventListener('change', function() {
                    document.getElementById('role-error').style.display = 'none';
                });
                
                // Показываем предупреждение для десктопной версии
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации Telegram WebApp:', e);
                showFallbackView();
            }
        }
        
        // Функция для отправки данных в Bitrix24
        async function sendToBitrix24(userData) {
            const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
            const BITRIX_METHOD = 'crm.lead.add';
            
            // Формируем данные для Bitrix24
            const leadData = {
                fields: {
                    TITLE: `Новый ${userData.role === 'client' ? 'клиент' : 'исполнитель'} из Telegram`,
                    NAME: userData.firstName || 'Неизвестно',
                    LAST_NAME: userData.lastName || '',
                    COMMENTS: `Telegram ID: ${userData.userId || 'N/A'}\nUsername: @${userData.username || 'отсутствует'}\nРоль: ${userData.role}\nВремя: ${userData.timestamp}`
                }
            };
            
            // Формируем URL запроса
            const requestUrl = `${BITRIX_WEBHOOK}${BITRIX_METHOD}`;
            
            // Отправляем запрос
            return fetch(requestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(leadData),
            });
        }
        
        // Функция для отображения запасного вида
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Привет, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Добро пожаловать в наше приложение!
                </div>
            `;
        }
        
        // Инициализация при загрузке
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', initApp);
            window.addEventListener('telegramready', initApp);
            setTimeout(initApp, 1000);
        }
    </script>
</body>
</html>
