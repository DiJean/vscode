<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Выбор роли</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?=$version?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?=$version?>">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Здравствуйте.</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script>
        const CURRENT_VERSION = "<?=$version?>";
        const storedVersion = localStorage.getItem('appVersion');
        
        if (storedVersion && storedVersion !== CURRENT_VERSION) {
            localStorage.clear();
            sessionStorage.clear();
            if (typeof caches !== 'undefined') {
                caches.keys().then(names => {
                    names.forEach(name => caches.delete(name));
                });
            }
        }
        localStorage.setItem('appVersion', CURRENT_VERSION);
        
        // Проверяем, есть ли сохраненная роль и данные
        const savedRole = sessionStorage.getItem('selectedRole');
        const savedInitData = sessionStorage.getItem('tgInitData');
        const savedUser = sessionStorage.getItem('tgUser');
        
        // Если есть сохраненная роль, перенаправляем сразу
        if (savedRole && savedInitData) {
            if (savedRole === 'client') {
                window.location.href = `/webapp/client/services.php?tgInitData=${encodeURIComponent(savedInitData)}&v=${CURRENT_VERSION}`;
            } else {
                window.location.href = `/webapp/doer/dashboard.php?tgInitData=${encodeURIComponent(savedInitData)}&v=${CURRENT_VERSION}`;
            }
            // Останавливаем выполнение скрипта
            throw new Error("Redirecting to saved role...");
        }
        
        function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const tg = Telegram.WebApp;
            try {
                tg.ready();
                if (tg.isExpanded !== true && tg.expand) tg.expand();
                
                // Сохраняем данные авторизации в sessionStorage
                if (tg.initData) {
                    sessionStorage.setItem('tgInitData', tg.initData);
                    sessionStorage.setItem('tgUser', JSON.stringify(tg.initDataUnsafe.user || {}));
                }
                
                // Показываем данные пользователя
                showUserData(tg);
                
                // Инициализация кнопки
                initMainButton(tg);
                
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        function showUserData(tg) {
            let user = null;
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                user = tg.initDataUnsafe.user;
            } else {
                // Пробуем восстановить из sessionStorage
                const savedUser = sessionStorage.getItem('tgUser');
                if (savedUser) {
                    try {
                        user = JSON.parse(savedUser);
                    } catch (e) {
                        console.error('Error parsing saved user data', e);
                    }
                }
            }
            
            let userHtml = '';
            if (user) {
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const username = user.username ? `@${user.username}` : 'без username';
                const fullName = `${firstName} ${lastName}`.trim();
                const greeting = fullName ? `Здравствуйте, ${fullName}!` : 'Здравствуйте.';
                document.getElementById('greeting').textContent = greeting;
                
                userHtml += `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${fullName}">` : 
                            `<div>${firstName.charAt(0) || 'Г'}</div>`}
                    </div>
                    <div class="user-name">${fullName || 'Аноним'}</div>
                    <div class="username">${username}</div>
                `;
            } else {
                userHtml = `<div class="avatar">Г</div><div class="user-name">Гость</div>`;
            }
            
            // Добавляем чекбокс "Запомнить выбор"
            userHtml += `
                <div class="role-selection">
                    <div class="role-label">Выберите роль:</div>
                    <select class="role-select" id="role">
                        <option value="" disabled selected>Выберите роль...</option>
                        <option value="client">Клиент</option>
                        <option value="performer">Исполнитель</option>
                    </select>
                    <div class="role-error" id="role-error">Выберите роль!</div>
                    
                    <div class="remember-container">
                        <input type="checkbox" id="remember-role" checked>
                        <label for="remember-role">Запомнить мой выбор</label>
                    </div>
                </div>
                <div class="welcome-text">
                    Мы готовы помочь Вам. <span class="heart">❤️</span>
                </div>
            `;
            
            document.getElementById('user-container').innerHTML = userHtml;
            
            // Обработчик изменения роли
            document.getElementById('role').addEventListener('change', function() {
                document.getElementById('role-error').style.display = 'none';
            });
        }
        
        function initMainButton(tg) {
            if (tg.MainButton) {
                tg.MainButton.setText("Продолжить");
                tg.MainButton.onClick(function() {
                    const role = document.getElementById('role').value;
                    if (!role) {
                        document.getElementById('role-error').style.display = 'block';
                        return;
                    }
                    
                    const rememberRole = document.getElementById('remember-role').checked;
                    const tgInitData = sessionStorage.getItem('tgInitData') || '';
                    
                    // Сохраняем выбранную роль, если нужно
                    if (rememberRole) {
                        sessionStorage.setItem('selectedRole', role);
                    } else {
                        sessionStorage.removeItem('selectedRole');
                    }
                    
                    // Переходим на страницу роли
                    if (role === 'client') {
                        window.location.href = `/webapp/client/services.php?tgInitData=${encodeURIComponent(tgInitData)}&v=${CURRENT_VERSION}`;
                    } else {
                        window.location.href = `/webapp/doer/dashboard.php?tgInitData=${encodeURIComponent(tgInitData)}&v=${CURRENT_VERSION}`;
                    }
                });
                tg.MainButton.show();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Здравствуйте, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">Добро пожаловать!</div>
                <div style="margin-top: 20px; color: #ff6b6b;">
                    <p>⚠️ Произошла ошибка</p>
                    <p>Попробуйте перезагрузить</p>
                </div>
                <div class="role-selection" style="margin-top: 30px;">
                    <a href="/webapp/client/services.php?v=${CURRENT_VERSION}" style="display: block; padding: 15px; background: #6a11cb; color: white; text-align: center; border-radius: 16px; margin-bottom: 15px;">
                        Войти как клиент
                    </a>
                    <a href="/webapp/doer/dashboard.php?v=${CURRENT_VERSION}" style="display: block; padding: 15px; background: #2575fc; color: white; text-align: center; border-radius: 16px;">
                        Войти как исполнитель
                    </a>
                </div>
            `;
        }
        
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    if (typeof Telegram === 'undefined') showFallbackView();
                    else initApp();
                }, 1000);
            });
        }
    </script>
    
    <style>
        .remember-container {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        
        .remember-container input {
            margin-right: 10px;
        }
        
        .remember-container label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</body>
</html>