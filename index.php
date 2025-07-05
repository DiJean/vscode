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
            favicon.href = '/webapp/icons/favicon.png';
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

    <!-- Стили для кнопок и иконок -->
    <style>
        /* Новый стиль для фона */
        body.theme-beige {
            background-image: url('/webapp/css/icon/marble_back.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .bot-avatar-container {
            position: relative;
            margin: 0 auto 20px;
            width: 80px;
            height: 80px;
        }

        .bot-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #0088cc;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border: 3px solid white;
            overflow: hidden;
        }

        .bot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .bitrix-section {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all 0.3s;
            margin: 5px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-about {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        }

        .btn-icon {
            margin-right: 8px;
        }

        /* Обновленные стили для аватара пользователя */
        .user-greeting {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <!-- Аватар бота -->
        <div class="bot-avatar-container">
            <div class="bot-avatar">
                <!-- Изображение аватара бота -->
                <img src="/webapp/css/icons/bot-avatar.jpg" alt="Аватар бота">
            </div>
        </div>

        <div class="header-container">
            <h1>Уход за местами погребения</h1>
            <p>Профессиональные услуги по содержанию и благоустройству</p>
        </div>

        <div class="user-greeting" id="user-container">
            <div class="greeting-text">Здравствуйте</div>
            <div class="user-name" id="user-fullname">Гость</div>
            <div class="avatar mb-3" id="user-avatar">
                <div class="avatar-letter">Г</div>
            </div>
        </div>

        <!-- Только стиль с кнопками для выбора роли -->
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

        <!-- Секция для Bitrix24 -->
        <div class="bitrix-section">
            <h4>Интеграция с CRM</h4>
            <p>Тестовая страница с виджетом Bitrix24</p>
            <a href="/webapp/b24.php" class="btn">
                <svg class="btn-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="white" d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z" />
                </svg>
                Bitrix24 Виджет
            </a>
            <!-- Ссылка на страницу "О нас" -->
            <a href="/webapp/about.php" class="btn btn-about">
                <svg class="btn-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="white" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                </svg>
                О нашем сервисе
            </a>
        </div>

        <div class="desktop-warning text-center mt-4" id="desktop-warning" style="display: none;">
            ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const version = '<?= $version ?>';
        let appInitialized = false;

        function initApp() {
            if (appInitialized) return;
            appInitialized = true;

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

        // Инициализация при загрузке
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', initApp);
        }

        // Восстановление состояния при возврате
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || performance.getEntriesByType("navigation")[0].type === 'back_forward') {
                initApp();
            }
        });
    </script>
</body>

</html>