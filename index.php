<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Скрипт установки иконки -->
    <script>
        (function() {
            const favicon = document.createElement('link');
            favicon.rel = 'icon';
            favicon.href = '/webapp/icons/favicon.ico';
            favicon.type = 'image/x-icon';
            document.head.appendChild(favicon);
        })();
    </script>
    <title>Выбор роли</title>

    <!-- Favicon -->
    <link rel="icon" href="/webapp/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/webapp/icons/icon-192x192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Стили для Bitrix кнопки -->
    <style>
        .bitrix-section {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
        }

        .bitrix-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .bitrix-btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .bitrix-icon {
            vertical-align: middle;
            margin-right: 8px;
            font-size: 1.2rem;
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="user-greeting" id="user-container">
            <div class="greeting-text">Здравствуйте</div>
            <div class="user-name" id="user-fullname">Гость</div>
            <div class="avatar mb-3" id="user-avatar">
                <div class="avatar-letter">Г</div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="role-card" data-role="client">
                    <div class="role-icon">👤</div>
                    <h3>Клиент</h3>
                    <p>Хочу заказать услугу</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="role-card" data-role="performer">
                    <div class="role-icon">👷</div>
                    <h3>Исполнитель</h3>
                    <p>Готов выполнять заказы</p>
                </div>
            </div>
        </div>

        <!-- Добавленная секция для Bitrix24 -->
        <div class="bitrix-section">
            <h4>Интеграция с CRM</h4>
            <p>Тестовая страница с виджетом Bitrix24</p>
            <a href="/webapp/b24.php" class="bitrix-btn">
                <span class="bitrix-icon">📊</span> Bitrix24 Виджет
            </a>
        </div>

        <div class="desktop-warning text-center mt-4" id="desktop-warning" style="display: none;">
            ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const version = '<?= $version ?>';

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

                    // Сохраняем Telegram User ID
                    if (user.id) {
                        localStorage.setItem('tgUserId', user.id);
                    }
                }

                const avatarContainer = document.getElementById('user-avatar');
                const fullNameElement = document.getElementById('user-fullname');

                if (user) {
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const fullName = `${firstName} ${lastName}`.trim() || 'Пользователь';

                    // Обновляем приветствие
                    fullNameElement.textContent = fullName;

                    // Обновляем аватар
                    if (user.photo_url) {
                        avatarContainer.innerHTML = `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle">`;
                    } else {
                        const firstLetter = firstName.charAt(0) || 'П';
                        avatarContainer.querySelector('.avatar-letter').textContent = firstLetter;
                    }
                } else {
                    avatarContainer.querySelector('.avatar-letter').textContent = 'Г';
                    fullNameElement.textContent = 'Гость';
                }

                document.querySelectorAll('.role-card').forEach(card => {
                    card.addEventListener('click', function() {
                        const role = this.getAttribute('data-role');
                        localStorage.setItem('selectedRole', role);
                        sessionStorage.setItem('selectedRole', role);

                        if (role === 'client') {
                            window.location.href = '/webapp/client/my-services.php?v=' + version;
                        } else {
                            window.location.href = '/webapp/doer/dashboard.php?v=' + version;
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
            document.getElementById('user-container').innerHTML = `
                <div class="text-center">
                    <div class="greeting-text">Здравствуйте</div>
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