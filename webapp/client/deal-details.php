<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заявки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/deal-details.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="my-services.php" class="back-btn">← Назад к списку заявок</a>
        </div>

        <h1 class="text-center mb-4">Детали заявки</h1>

        <div class="detail-card" id="deal-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const version = '<?= $version ?>';

        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        async function loadDealDetails() {
            const dealId = getUrlParameter('id');
            if (!dealId) {
                showError('ID заявки не указан');
                return;
            }

            try {
                const deal = await BitrixCRM.getDealDetails(dealId);
                if (!deal) {
                    throw new Error('Заявка не найдена');
                }

                renderDealDetails(deal);
            } catch (error) {
                console.error('Ошибка загрузки деталей:', error);
                showError(error.message || 'Ошибка загрузки данных');
            }
        }

        function renderDealDetails(deal) {
            const createdDate = new Date(deal.DATE_CREATE).toLocaleDateString('ru-RU');
            const serviceDate = deal.UF_CRM_685D295664A8A ?
                new Date(deal.UF_CRM_685D295664A8A).toLocaleDateString('ru-RU') : '-';

            let statusText = deal.STAGE_ID || 'Неизвестно';
            if (statusText === 'NEW') statusText = 'Новый заказ';
            else if (statusText === 'PREPARATION') statusText = 'Подготовка';
            else if (statusText === 'PREPAYMENT_INVOICE') statusText = 'Оплата';
            else if (statusText === 'EXECUTING') statusText = 'В работе';
            else if (statusText === 'WON') statusText = 'Успешно завершена';
            else if (statusText === 'LOSE') statusText = 'Не нашли участок';
            else if (statusText === 'APOLOGY') statusText = 'Анализ неудачи';

            const dealContainer = document.getElementById('deal-container');
            dealContainer.innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Номер заявки</div>
                    <div class="detail-value">#${deal.ID}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Статус</div>
                    <div class="detail-value">${statusText}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Заказ</div>
                    <div class="detail-value">${deal.TITLE.replace('Заявка от ', '')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Дата создания</div>
                    <div class="detail-value">${createdDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Желаемая дата услуги</div>
                    <div class="detail-value">${serviceDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Услуги</div>
                    <div class="detail-value">${deal.services}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Город</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956BF4C8 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Кладбище</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956D0916 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Сектор</div>
                    <div class="detail-value">${deal.UF_CRM_1751022940 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ряд</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956D7C70 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Участок</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956DF40F || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Комментарий</div>
                    <div class="detail-value">${deal.COMMENTS || '-'}</div>
                </div>
            `;
        }

        function showError(message) {
            document.getElementById('deal-container').innerHTML = `
                <div class="alert alert-danger">
                    ${message || 'Ошибка загрузки данных'}
                </div>
            `;
        }
        console.log('Deal ID:', dealId);
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            // Проверяем загружен ли BitrixCRM
            if (typeof BitrixCRM !== 'undefined') {
                loadDealDetails();
            } else {
                // Динамически загружаем скрипт
                const script = document.createElement('script');
                script.src = '/webapp/js/bitrix-integration.js?' + version;
                script.onload = loadDealDetails;
                script.onerror = () => showError('Ошибка загрузки модуля');
                document.body.appendChild(script);
            }
        });
    </script>
</body>

</html>