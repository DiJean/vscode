<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор роли</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Наши стили -->
    <link rel="stylesheet" href="/webapp/css/style.css">
    
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        .role-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .role-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }
        
        .role-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .desktop-warning {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="greeting mb-4" id="greeting">Здравствуйте.</div>
        <div id="user-container" class="mb-4"></div>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="role-card" data-role="client">
                    <div class="role-icon">👤</div>
                    <h3>Клиент</h3>
                    <p>Хочу заказать услуги</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="role-card" data-role="performer">
                    <div class="role-icon">👷</div>
                    <h3>Исполнитель</h3>
                    <p>Хочу выполнять заказы</p>
                </div>
            </div>
        </div>
        
        <div class="desktop-warning text-center mt-4" id="desktop-warning" style="display: none;">
            ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const tg = Telegram.WebApp;
            
            try {
                tg.ready();
                
                if (tg.isExpanded !== true && tg.expand) {
                    tg.expand();
                }
                
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) {
                    tg.setHeaderColor('#6a11cb');
                }
                
                let user = null;
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                    user = tg.initDataUnsafe.user;
                }
                
                let userHtml = '';
                
                if (user) {
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const username = user.username ? `@${user.username}` : 'без username';
                    const fullName = `${firstName} ${lastName}`.trim();
                    
                    const greeting = fullName ? `Здравствуйте, ${fullName}!` : 'Здравствуйте.';
                    document.getElementById('greeting').textContent = greeting;
                    
                    userHtml = `
                        <div class="d-flex flex-column align-items-center">
                            <div class="avatar mb-3">
                                ${user.photo_url ? 
                                    `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle">` : 
                                    `<div class="d-flex align-items-center justify-content-center h-100 fw-bold fs-3">${firstName.charAt(0) || 'Г'}</div>`
                                }
                            </div>
                            <div class="user-name fs-4 mb-1">${fullName || 'Анонимный пользователь'}</div>
                            <div class="username text-muted">${username}</div>
                        </div>
                    `;
                } else {
                    userHtml = `
                        <div class="d-flex flex-column align-items-center">
                            <div class="avatar mb-3 d-flex align-items-center justify-content-center fw-bold fs-3">Г</div>
                            <div class="user-name fs-4">Гость</div>
                        </div>
                    `;
                }
                
                document.getElementById('user-container').innerHTML = userHtml;
                
                document.querySelectorAll('.role-card').forEach(card => {
                    card.addEventListener('click', function() {
                        const role = this.getAttribute('data-role');
                        localStorage.setItem('selectedRole', role);
                        sessionStorage.setItem('selectedRole', role);
                        
                        // ИЗМЕНЕНА ЛОГИКА ПЕРЕХОДА ДЛЯ КЛИЕНТА
                        if (role === 'client') {
                            window.location.href = '/webapp/client/my-services.php';
                        } else {
                            window.location.href = '/webapp/doer/dashboard.php';
                        }
                    });
                });
                
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации Telegram WebApp:', e);
                showFallbackView();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Здравствуйте, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="text-center">
                    <div class="welcome-text">
                        Добро пожаловать в наше приложение.
                    </div>
                </div>
            `;
        }
        
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', initApp);
        }
    </script>
</body>
</html>