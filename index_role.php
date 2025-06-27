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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Привет!</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script src="telegram-api.js"></script>
    <script>
        // Основная функция инициализации приложения
        function initApp() {
            const tgApp = initTelegramApp((tg) => {
                // Получаем данные пользователя
                const user = getUserData(tg) || {};
                
                // Формируем приветствие
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                const greeting = fullName ? `Привет, ${fullName}!` : 'Привет!';
                document.getElementById('greeting').textContent = greeting;
                
                // Формируем HTML
                let userHtml = `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${fullName}">` : 
                            `<div>${firstName.charAt(0) || 'Г'}</div>`
                        }
                    </div>
                    <div class="user-name">${fullName || 'Анонимный пользователь'}</div>
                    <div class="username">${user.username ? '@' + user.username : 'без username'}</div>
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
                
                document.getElementById('user-container').innerHTML = userHtml;
                
                // Настройка кнопки
                setupMainButton(tg, () => {
                    const role = document.getElementById('role').value;
                    if (!role) {
                        document.getElementById('role-error').style.display = 'block';
                        return;
                    }
                    
                    // Сохраняем роль
                    localStorage.setItem('selectedRole', role);
                    
                    // Перенаправляем в зависимости от роли
                    if (role === 'client') {
                        window.location.href = 'client-form.php';
                    } else {
                        // Для исполнителя другая страница
                        window.location.href = 'performer-dashboard.php';
                    }
                });
                
                // Показываем предупреждение для десктопной версии
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
                return true;
            });
            
            if (!tgApp) {
                showFallbackView();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Привет, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Добро пожаловать в наше приложение!
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>
