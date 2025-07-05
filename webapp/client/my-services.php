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

<body class="theme-beige">
    <div class="container">
        <a href="/" class="btn-change-rol me-3">← Сменить роль</a>
        <img src="/webapp/css/icons/bot-avatar.jpg" alt="Бот" class="role-icon-small">
        <div class="greeting">Мои заявки</div>
        <a href="/webapp/client/client-form.php?v=<?= $version ?>" class="btn-create">+ Создать новую заявку</a>
        <div class="requests-list" id="requests-list">
            <div class="request-item">
                <div class="request-service">Загрузка заявок...</div>
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
                document.querySelector('.greeting').textContent = `Здравствуйте, ${userData.firstName}!`;
            }
        }

        let tgUserId = null;
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            const tg = Telegram.WebApp;
            tgUserId = tg.initDataUnsafe?.user?.id;
        }

        if (!tgUserId) {
            tgUserId = localStorage.getItem('tgUserId');
        }

        if (tgUserId) {
            localStorage.setItem('tgUserId', tgUserId);

            loadBitrixIntegration().then(() => {
                if (typeof BitrixCRM !== 'undefined' && BitrixCRM.getUserRequests) {
                    loadRequests(tgUserId);
                } else {
                    showError();
                }
            }).catch(error => {
                console.error('Ошибка загрузки BitrixCRM:', error);
                showError();
            });
        } else {
            showNoRequests();
        }

        function loadBitrixIntegration() {
            return new Promise((resolve, reject) => {
                if (typeof BitrixCRM !== 'undefined') {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = '/webapp/js/bitrix-integration.js?<?= $version ?>';
                script.onload = resolve;
                script.onerror = reject;
                document.body.appendChild(script);
            });
        }

        function loadRequests(tgUserId) {
            BitrixCRM.getUserRequests(tgUserId)
                .then(deals => {
                    const performerIds = deals
                        .map(deal => deal.UF_CRM_1751128612)
                        .filter(id => id && id > 0);

                    if (performerIds.length > 0) {
                        return BitrixCRM.getPerformersInfo(performerIds)
                            .then(performers => {
                                return {
                                    deals,
                                    performers
                                };
                            });
                    }

                    return {
                        deals,
                        performers: []
                    };
                })
                .then(({
                    deals,
                    performers
                }) => {
                    renderRequests(deals, performers);
                })
                .catch(error => {
                    console.error('Ошибка загрузки заявок:', error);
                    showError();
                });
        }

        function renderRequests(deals, performers = []) {
            if (!deals || deals.length === 0) {
                showNoRequests();
                return;
            }

            let html = `
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>№ заявки</th>
                            <th>Услуги</th>
                            <th>Дата создания</th>
                            <th>Статус</th>
                            <th>Исполнитель</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            deals.forEach(deal => {
                const date = new Date(deal.DATE_CREATE).toLocaleDateString('ru-RU');
                const serviceNames = deal.services || 'Услуга не указана';

                let statusClass = '';
                let statusText = deal.STAGE_ID || '';

                if (statusText === 'NEW') {
                    statusText = 'Новая заявка';
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

                let performerInfo = 'Не назначен';
                if (deal.UF_CRM_1751128612) {
                    const performer = performers.find(p => p.ID == deal.UF_CRM_1751128612);
                    if (performer) {
                        performerInfo = `${performer.NAME || ''} ${performer.LAST_NAME || ''}`.trim() || 'Исполнитель';
                    } else {
                        performerInfo = `ID: ${deal.UF_CRM_1751128612}`;
                    }
                }

                html += `
                    <tr>
                        <td class="request-num">#${deal.ID}</td>
                        <td class="request-service">${serviceNames}</td>
                        <td class="request-date">${date}</td>
                        <td><span class="request-status ${statusClass}">${statusText}</span></td>
                        <td class="request-performer">${performerInfo}</td>
                        <td>
                            <button class="details-btn" data-id="${deal.ID}">Детали</button>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            document.getElementById('requests-list').innerHTML = html;

            document.querySelectorAll('.details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const dealId = this.getAttribute('data-id');
                    window.location.href = `deal-details.php?id=${dealId}&v=${version}`;
                });
            });
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
                <div class="no-requests">
                    <div class="no-requests-icon">⚠️</div>
                    <h3>Ошибка загрузки данных</h3>
                    <p>Попробуйте обновить страницу или зайти позже</p>
                </div>
            `;
        }
    </script>
</body>

</html>