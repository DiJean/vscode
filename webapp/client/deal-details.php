<?php
require_once('/var/www/config.php');
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

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="my-services.php" class="btn btn-outline-primary">← Назад к списку заявок</a>
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

    <!-- Модальное окно для просмотра фото -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Просмотр фото</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" alt="" class="modal-photo">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        const version = '<?= $version ?>';
        const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));

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

            // Фото отображаем только для статуса WON
            if (deal.STAGE_ID === 'WON') {
                if (deal.beforePhotoUrl) {
                    dealContainer.innerHTML += `
                        <div class="detail-item">
                            <div class="detail-label">Фото "До"</div>
                            <div class="detail-value">
                                <img src="${deal.beforePhotoUrl}" 
                                     alt="Фото до" 
                                     class="photo-thumbnail"
                                     data-full="${deal.beforePhotoUrl}"
                                     onclick="openPhotoModal('${deal.beforePhotoUrl}')">
                            </div>
                        </div>
                    `;
                }

                if (deal.afterPhotoUrl) {
                    dealContainer.innerHTML += `
                        <div class="detail-item">
                            <div class="detail-label">Фото "После"</div>
                            <div class="detail-value">
                                <img src="${deal.afterPhotoUrl}" 
                                     alt="Фото после" 
                                     class="photo-thumbnail"
                                     data-full="${deal.afterPhotoUrl}"
                                     onclick="openPhotoModal('${deal.afterPhotoUrl}')">
                            </div>
                        </div>
                    `;
                }
            } else {
                // Для других статусов показываем сообщение
                dealContainer.innerHTML += `
                    <div class="detail-item">
                        <div class="detail-label">Фото работ</div>
                        <div class="detail-value">Доступны после завершения заявки</div>
                    </div>
                `;
            }
        }

        function openPhotoModal(photoUrl) {
            document.getElementById('modalPhoto').src = photoUrl;
            photoModal.show();
        }

        function showError(message) {
            document.getElementById('deal-container').innerHTML = `
                <div class="alert alert-danger">
                    ${message || 'Ошибка загрузки данных'}
                </div>
            `;
        }

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof BitrixCRM !== 'undefined') {
                loadDealDetails();
            } else {
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