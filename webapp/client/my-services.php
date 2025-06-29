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

        // Получаем Telegram User ID
        let tgUserId = null;
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            const tg = Telegram.WebApp;
            tgUserId = tg.initDataUnsafe?.user?.id;
        }

        // Если не получили из WebApp, пробуем получить из localStorage
        if (!tgUserId) {
            tgUserId = localStorage.getItem('tgUserId');
        }

        if (tgUserId) {
            // Сохраняем для будущего использования
            localStorage.setItem('tgUserId', tgUserId);

            if (typeof BitrixCRM !== 'undefined' && BitrixCRM.getUserRequests) {
                loadRequests(tgUserId);
            } else {
                showError();
            }
        } else {
            showNoRequests();
        }

        function loadRequests(tgUserId) {
            BitrixCRM.getUserRequests(tgUserId)
                .then(deals => {
                    renderRequests(deals);
                })
                .catch(error => {
                    console.error('Ошибка загрузки заявок:', error);
                    showError();
                });
        }

        function renderRequests(deals) {
            let html = '';

            if (deals.length === 0) {
                showNoRequests();
                return;
            }

            deals.forEach(deal => {
                const date = new Date(deal.DATE_CREATE).toLocaleDateString('ru-RU');
                const service = deal.UF_CRM_685D2956C64E0 || 'Услуга не указана';

                let statusClass = '';
                let statusText = deal.STAGE_ID || 'Новый';

                if (statusText === 'NEW') {
                    statusText = 'Новая';
                    statusClass = 'status-new';
                } else if (statusText === 'PREPARATION' ||
                    statusText === 'PREPAYMENT_INVOICE' ||
                    statusText === 'EXECUTING') {
                    statusText = 'В работе';
                    statusClass = 'status-processing';
                } else if (statusText === 'WON') {
                    statusText = 'Завершена';
                    statusClass = 'status-completed';
                } else if (statusText === 'LOSE' || statusText === 'APOLOGY') {
                    statusText = 'Отменена';
                    statusClass = 'status-canceled';
                }

                html += `
                    <div class="request-item">
                        <div class="request-service">${service}</div>
                        <div class="request-date">Создано: ${date}</div>
                        <div class="request-status ${statusClass}">${statusText}</div>
                    </div>
                `;
            });

            document.getElementById('requests-list').innerHTML = html;
        }

        function showNoRequests() {
            document.getElementById('requests-list').innerHTML = `
                <div class="no-requests">
                    <div class="no-requests-icon">📭</div>
                    <h3>У вас пока нет заявок</h3>
                    <p>Нажмите кнопку "Создать новую заявку", чтобы оставить первый запрос</p>
                </div>
            `;
        }

        function showError() {
            document.getElementById('requests-list').innerHTML = `
                <div class="request-item">
                    <div class="request-service">Ошибка загрузки данных</div>
                    <div class="request-date">Попробуйте обновить страницу</div>
                </div>
            `;
        }
    </script>

    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>
</body>

</html>