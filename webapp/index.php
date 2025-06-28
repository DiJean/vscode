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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Привет!</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script type="module">
        import { initTelegramApp, MainButton, getUserData, isMobile } from './js/telegram-api.js';

        function renderUserInfo(user) {
            const greetingEl = document.getElementById('greeting');
            const userContainer = document.getElementById('user-container');
            
            if (!user) {
                greetingEl.textContent = 'Привет, Гость!';
                userContainer.innerHTML = `
                    <div class="welcome-text">
                        Добро пожаловать! Для продолжения откройте приложение в Telegram.
                    </div>
                `;
                return;
            }
            
            const firstName = user.first_name || '';
            const lastName = user.last_name || '';
            const username = user.username ? `@${user.username}` : '';
            const fullName = `${firstName} ${lastName}`.trim() || 'Анонимный пользователь';
            const avatarLetter = firstName.charAt(0) || 'Г';
            
            greetingEl.textContent = fullName ? `Привет, ${firstName}!` : 'Привет!';
            
            userContainer.innerHTML = `
                <div class="avatar">
                    ${user.photo_url ? 
                        `<img src="${user.photo_url}" alt="${fullName}">` : 
                        `<div class="avatar-letter">${avatarLetter}</div>`
                    }
                </div>
                <div class="user-name">${fullName}</div>
                ${username ? `<div class="username">${username}</div>` : ''}
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
        }
        
        function handleRoleSubmit() {
            const roleSelect = document.getElementById('role');
            const role = roleSelect.value;
            const errorEl = document.getElementById('role-error');
            
            if (!role) {
                errorEl.style.display = 'block';
                return;
            }
            
            localStorage.setItem('selectedRole', role);
            
            // Сохраняем данные пользователя для передачи на следующую страницу
            const user = getUserData();
            if (user) {
                sessionStorage.setItem('telegramUser', JSON.stringify(user));
            }
            
            if (role === 'client') {
                window.location.href = 'client/client-form.php';
            } else {
                window.location.href = 'performer/dashboard.php';
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Привет!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для использования приложения откройте его в Telegram.
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const tg = initTelegramApp();
            const user = getUserData();
            
            if (!tg) {
                showFallbackView();
                return;
            }
            
            renderUserInfo(user);
            
            MainButton.show("Продолжить");
            MainButton.onClick(handleRoleSubmit);
            
            if (!isMobile()) {
                document.getElementById('desktop-warning').style.display = 'block';
            }
        });
    </script>
</body>
</html>