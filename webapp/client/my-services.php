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
    <style>
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .requests-table th {
            background-color: rgba(106, 17, 203, 0.3);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .requests-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .requests-table tr:last-child td {
            border-bottom: none;
        }

        .requests-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .request-num {
            font-weight: bold;
            color: #6a11cb;
        }

        .request-service {
            font-weight: 500;
        }

        .request-date {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .request-performer {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .requests-table {
                display: block;
                overflow-x: auto;
            }

            .requests-table th,
            .requests-table td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
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

            // Загружаем BitrixCRM и затем запрашиваем данные
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
                    // Собираем ID всех исполнителей
                    const performerIds = deals
                        .map(deal => deal.UF_CRM_1751128612)
                        .filter(id => id && id > 0);

                    // Если есть исполнители - получаем их данные
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
                        </tr>
                    </thead>
                    <tbody>
            `;

            deals.forEach(deal => {
                const date = new Date(deal.DATE_CREATE).toLocaleDateString('ru-RU');

                // Преобразуем услуги в читаемый вид
                let serviceNames = 'Услуга не указана';
                if (deal.UF_CRM_685D2956C64E0) {
                    const serviceIds = Array.isArray(deal.UF_CRM_685D2956C64E0) ?
                        deal.UF_CRM_685D2956C64E0 : [deal.UF_CRM_685D2956C64E0];

                    serviceNames = serviceIds.map(id => {
                        if (id === '69') return 'Уход';
                        if (id === '71') return 'Цветы';
                        if (id === '73') return 'Ремонт';
                        if (id === '75') return 'Церковная служба';
                        return id;
                    }).join(', ');
                }

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

                // Исполнитель
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
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

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