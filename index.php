<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор роли</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Здравствуйте.</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script src="/webapp/js/telegram-api.js"></script>
    <script>
        // Основная функция инициализации приложения
        function initApp() {
            // Проверка доступности Telegram WebApp API
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
                    
                    // Формируем Здравствуйтествие
                    const greeting = fullName ? `Здравствуйте, ${fullName}!` : 'Здравствуйте.';
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
                        <div class="role-error" id="role-error">Выберите действие.</div>
                    </div>
                    <div class="welcome-text">
                        Мы рады помочь Вам. <span class="heart"></span>
                    </div>
                `;
                
                // Отображаем информацию о пользователе
                document.getElementById('user-container').innerHTML = userHtml;
                
                // Настройка кнопки
                if (tg.MainButton) {
                    tg.MainButton.setText("Продолжить");
                    
                    // Обработчик для кнопки
                    tg.MainButton.onClick(function() {
                        const role = document.getElementById('role').value;
                        if (!role) {
                            document.getElementById('role-error').style.display = 'block';
                            return;
                        }
                        
                        // Сохраняем роль
                        localStorage.setItem('selectedRole', role);
                        
                        // Перенаправляем в зависимости от роли
                        if (role === 'client') {
                            window.location.assign('/webapp/client/client-form.php');
                            window.location.href = '/webapp/client/client-form.php';
                        } else {
                            // Для исполнителя другая страница
                            window.location.href = '/webapp/doer/dashboard.php';
                        }
                    });
                    
                    // Показываем кнопку
                    tg.MainButton.show();
                }
                
                // Показываем предупреждение для десктопной версии
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации Telegram WebApp:', e);
                showFallbackView();
            }
        }
        
        // Функция для отображения запасного вида
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Здравствуйте, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Добро пожаловать в наше приложение.
                </div>
            `;
        }
        
        // Инициализация при загрузке
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', initApp);
        }
    </script>
</body>
</html>
