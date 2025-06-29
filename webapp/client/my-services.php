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
    <link rel="stylesheet" href="/webapp/css/my-services.css?<?= $version ?>">
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
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const selectedRole = localStorage.getItem('selectedRole') || sessionStorage.getItem('selectedRole');
        const version = '<?= $version ?>';

        if (!selectedRole || selectedRole !== 'client') {
            window.location.href = '/?v=' + version;
        } else {
            localStorage.setItem('selectedRole', 'client');
            sessionStorage.setItem('selectedRole', 'client');

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
        const email = localStorage.getItem('userEmail');
        if (email) {
            if (typeof BitrixCRM !== 'undefined' && BitrixCRM.getUserRequests) {
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
            } else {
                document.getElementById('requests-list').innerHTML = `
                    <div class="request-item">
                        <div class="request-service">Системная ошибка</div>
                        <div class="request-date">Перезагрузите приложение</div>
                    </div>
                `;
            }
        }
    </script>
</body>

</html>