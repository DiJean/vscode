<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои услуги</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
        body.theme-beige {
            background-image: url('/webapp/css/icons/marble_back.jpg');
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
            color: #333;
        }

        .container * {
            color: #333 !important;
        }

        .btn-change-role {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.5);
            color: #333 !important;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 15px;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn-change-role:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateY(-2px);
        }

        .role-icon-small {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="/" class="btn-change-role me-3">
                    <img src="/webapp/css/icons/doer_ava.png" alt="Исполнитель" class="role-icon-small">
                    Сменить роль
                </a>
                <h1 class="h3 mb-0">Мои услуги</h1>
            </div>
            <div class="d-flex align-items-center" id="user-info">
                <div class="spinner-border spinner-border-sm text-light me-2" role="status"></div>
                Загрузка...
            </div>
        </div>

        <!-- Содержимое страницы клиента -->
        <div id="services-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const version = '<?= $version ?>';
        let tg = null;
        let user = null;

        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }

            tg = Telegram.WebApp;

            try {
                user = tg.initDataUnsafe.user || {};

                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim() || 'Пользователь';

                document.getElementById('user-info').innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="avatar me-2">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle" style="width:40px;height:40px;object-fit:cover;">` : 
                                `<div class="d-flex align-items-center justify-content-center rounded-circle bg-light text-dark fw-bold" style="width:40px;height:40px;">${firstName.charAt(0) || 'П'}</div>`
                            }
                        </div>
                        <div>
                            <div class="user-name">${fullName || 'Клиент'}</div>
                        </div>
                    </div>
                `;

                // Здесь будет код загрузки услуг клиента

            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }

        function showFallbackView() {
            document.getElementById('user-info').innerHTML = `
                <div class="text-muted">
                    Для использования приложения откройте его в Telegram
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>

</html>