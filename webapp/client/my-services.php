<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
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
    <title>Мои запросы</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?= $version ?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <style>
        .btn-create {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            text-align: center;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
            transition: all 0.3s;
        }

        .btn-create:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .requests-list {
            margin-top: 30px;
        }

        .request-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            text-align: left;
            transition: all 0.3s;
        }

        .request-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }

        .request-service {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .request-date {
            opacity: 0.9;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .request-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 12px;
            display: inline-block;
            font-weight: 500;
        }

        .status-new {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-processing {
            background: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .no-requests {
            text-align: center;
            padding: 40px 20px;
            opacity: 0.7;
        }

        .no-requests-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="greeting">Мои заявки</div>
        <a href="/webapp/client/client-form.php?v=<?= $version ?>" class="btn-create">+ Создать новую заявку</a>
        <div class="requests-list" id="requests-list">
            <div class="request-item">
                <div class="request-service">Загрузка...</div>
            </div>
        </div>
    </div>

    <script>
        // Восстанавливаем данные пользователя
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const selectedRole = localStorage.getItem('selectedRole') || sessionStorage.getItem('selectedRole');

        if (!selectedRole || selectedRole !== 'client') {
            // Если роль не сохранена, возвращаем на главную
            window.location.href = '/?v=<?= $version ?>';
        } else {
            // Сохраняем роль повторно для надежности
            localStorage.setItem('selectedRole', 'client');
            sessionStorage.setItem('selectedRole', 'client');

            // Обновляем приветствие
            if (userData.firstName) {
                document.querySelector('.greeting').textContent = `Привет, ${userData.firstName}!`;
            }
        }

        const email = localStorage.getItem('userEmail');
        if (!email) {
            document.getElementById('requests-list').innerHTML = `
                <div class="no-requests">
                    <div class="no-requests-icon">📭</div>
                    <h3>Вы еще не создавали заявок</h3>
                    <p>Нажмите кнопку "Создать новую заявку", чтобы оставить первый запрос</p>
                </div>
            `;
        }
    </script>

    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>
    <script>
        if (email) {
            BitrixCRM.getUserRequests(email)
                .then(response => {
                    const leads = response.result || [];
                    let html = '';

                    if (leads.length === 0) {
                        html = `
                            <div class="no-requests">
                                <div class="no-requests-icon">📭</div>
                                <h3>У вас пока нет заявок</h3>
                                <p>Нажмите кнопку "Создать новую заявку", чтобы оставить первый запрос</p>
                            </div>
                        `;
                    } else {
                        leads.forEach(lead => {
                            const date = new Date(lead.DATE_CREATE).toLocaleDateString('ru-RU');
                            const service = lead.UF_CRM_685D2956C64E0 || 'Услуга не указана';

                            // Определяем статус
                            let statusClass = '';
                            let statusText = lead.STATUS_ID || 'Новый';

                            if (statusText === 'NEW') {
                                statusText = 'Новая';
                                statusClass = 'status-new';
                            } else if (statusText === 'PROCESSING') {
                                statusText = 'В работе';
                                statusClass = 'status-processing';
                            } else if (statusText === 'CLOSED') {
                                statusText = 'Завершена';
                                statusClass = 'status-completed';
                            }

                            html += `
                                <div class="request-item">
                                    <div class="request-service">${service}</div>
                                    <div class="request-date">Создано: ${date}</div>
                                    <div class="request-status ${statusClass}">${statusText}</div>
                                </div>
                            `;
                        });
                    }
                    document.getElementById('requests-list').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('requests-list').innerHTML = `
                        <div class="request-item">
                            <div class="request-service">Ошибка загрузки данных</div>
                            <div class="request-date">Попробуйте обновить страницу</div>
                        </div>
                    `;
                });
        }
    </script>
</body>

</html>