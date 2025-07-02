<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор роли</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        .user-greeting {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }

        .greeting-text {
            font-size: 1.8rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 15px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
            border: 3px solid white;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-letter {
            font-size: 3rem;
            font-weight: bold;
            color: white;
        }

        @media (max-width: 768px) {
            .greeting-text {
                font-size: 1.5rem;
            }

            .user-name {
                font-size: 1.3rem;
            }

            .avatar {
                width: 80px;
                height: 80px;
            }

            .avatar-letter {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="user-greeting" id="user-container">
            <div class="greeting-text">Здравствуйте</div>
            <div class="avatar mb-3" id="user-avatar">
                <div class="avatar-letter">Г</div>
            </div>
            <div class="user-name" id="user-fullname">Гость</div>
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