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
        <div class="greeting" id="greeting">Привет!</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script src="/webapp/js/telegram-api.js?<?=$version?>"></script>
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
        
        function checkScriptLoaded() {
            const errors = [];
            if (typeof Telegram === 'undefined') errors.push('Telegram SDK не загружен');
            if (typeof initTelegramApp === 'undefined') errors.push('telegram-api.js не загружен');
            if (errors.length > 0) {
                console.error('Ошибки:', errors.join(', '));
                showFallbackView();
                return false;
            }
            return true;
        }
        
        function initApp() {
            if (!checkScriptLoaded()) return;
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const tg = Telegram.WebApp;
            try {
                tg.ready();
                if (tg.isExpanded !== true && tg.expand) tg.expand();
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) tg.setHeaderColor('#6a11cb');
                
                let user = null;
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) user = tg.initDataUnsafe.user;
                
                let userHtml = '';
                if (user) {
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const username = user.username ? `@${user.username}` : 'без username';
                    const fullName = `${firstName} ${lastName}`.trim();
                    const greeting = fullName ? `Привет, ${fullName}!` : 'Привет!';
                    document.getElementById('greeting').textContent = greeting;
                    
                    userHtml += `
                        <div class="avatar">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}">` : 
                                `<div>${firstName.charAt(0) || 'Г'}</div>`
                            }
                        </div>
                        <div class="user-name">${fullName || 'Аноним'}</div>
                        <div class="username">${username}</div>
                    `;
                } else {
                    userHtml = `<div class="avatar">Г</div><div class="user-name">Гость</div>`;
                }
                
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
                
                document.getElementById('user-container').innerHTML = userHtml;
                
                if (tg.MainButton) {
                    tg.MainButton.setText("Продолжить");
                    tg.MainButton.onClick(function() {
                        const role = document.getElementById('role').value;
                        if (!role) {
                            document.getElementById('role-error').style.display = 'block';
                            return;
                        }
                        
                        localStorage.setItem('selectedRole', role);
                        if (role === 'client') {
                            window.location.href = '/webapp/client/services.php?v=' + CURRENT_VERSION;
                        } else {
                            window.location.href = '/webapp/doer/dashboard.php?v=' + CURRENT_VERSION;
                        }
                    });
                    tg.MainButton.show();
                }
                
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Привет, Гость!';
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
</body>
</html>