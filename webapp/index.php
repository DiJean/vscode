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
    <style>
        /* Локальные стили для анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .role-selection {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Привет!</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script>
        // Telegram API функции
        function initTelegramApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                return null;
            }
            
            const tg = Telegram.WebApp;
            
            try {
                tg.ready();
                if (tg.isExpanded !== true) {
                    tg.expand();
                }
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) {
                    tg.setHeaderColor('#6a11cb');
                }
                return tg;
            } catch (e) {
                console.error('Telegram init error:', e);
                return null;
            }
        }

        function getUserData() {
            try {
                if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                    return Telegram.WebApp.initDataUnsafe?.user || null;
                }
            } catch (e) {
                console.error('Error getting user data:', e);
            }
            return null;
        }

        function isMobile() {
            const tg = Telegram.WebApp;
            return tg?.isMobile || false;
        }

        const MainButton = {
            show: function(text, color = '#6a11cb', textColor = '#ffffff') {
                try {
                    if (typeof Telegram !== 'undefined' && Telegram.WebApp?.MainButton) {
                        const mainButton = Telegram.WebApp.MainButton;
                        mainButton.setText(text);
                        mainButton.color = color;
                        mainButton.textColor = textColor;
                        mainButton.show();
                        return true;
                    }
                } catch (e) {
                    console.error('Error showing main button:', e);
                }
                return false;
            },
            hide: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.hide();
                        return true;
                    }
                } catch (e) {
                    console.error('Error hiding main button:', e);
                }
                return false;
            },
            onClick: function(handler) {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.onClick(handler);
                        return true;
                    }
                } catch (e) {
                    console.error('Error setting main button click:', e);
                }
                return false;
            },
            enable: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.enable();
                        return true;
                    }
                } catch (e) {
                    console.error('Error enabling main button:', e);
                }
                return false;
            },
            disable: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.disable();
                        return true;
                    }
                } catch (e) {
                    console.error('Error disabling main button:', e);
                }
                return false;
            }
        };

        // Логика страницы
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
                        `<img src="${user.photo_url}" alt="${fullName}" crossorigin="anonymous">` : 
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
                    <div class="role-error" id="role-error" style="display: none;">Выберите роль!</div>
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const tg = initTelegramApp();
            const user = getUserData();
            
            // Если Telegram WebApp не инициализирован, показываем запасной вид
            if (!tg) {
                showFallbackView();
                return;
            }
            
            renderUserInfo(user);
            
            // Настраиваем главную кнопку
            MainButton.show("Продолжить");
            MainButton.onClick(handleRoleSubmit);
            
            // Показываем предупреждение для десктопной версии
            if (!isMobile()) {
                document.getElementById('desktop-warning').style.display = 'block';
            }
            
            // Обработчик изменения выбора роли
            const roleSelect = document.getElementById('role');
            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    document.getElementById('role-error').style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>