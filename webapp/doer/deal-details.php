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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Детали заявки</h1>
            <div id="user-info"></div>
        </div>

        <div class="detail-card" id="deal-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>

        <div class="completion-section" id="completion-section" style="display: none;">
            <h3 class="mb-4">Завершение заказа</h3>
            <div class="photo-upload-container">
                <div class="photo-upload">
                    <div class="photo-preview" id="before-preview">
                        <span>Фото до работ</span>
                    </div>
                    <button type="button" class="upload-btn" id="before-btn">
                        Загрузить фото "До"
                    </button>
                    <input type="file" id="before-photo" accept="image/*" style="display: none;">
                </div>

                <div class="photo-upload">
                    <div class="photo-preview" id="after-preview">
                        <span>Фото после работ</span>
                    </div>
                    <button type="button" class="upload-btn" id="after-btn">
                        Загрузить фото "После"
                    </button>
                    <input type="file" id="after-photo" accept="image/*" style="display: none;">
                </div>
            </div>

            <button id="complete-deal-btn" class="complete-btn" disabled>
                Завершить заказ
            </button>
        </div>

        <a href="dashboard.php" class="back-btn">← Назад к списку заявок</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
        const version = '<?= $version ?>';

        let tg = null;
        let user = null;
        let contactId = null;
        let performerName = "";
        let beforePhotoFile = null;
        let afterPhotoFile = null;
        let isAndroid = /Android/i.test(navigator.userAgent);

        function getUrlParameter(name) {
            name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
            const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            const results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }

        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        filter: {
                            'UF_CRM_1751128872': String(tgId)
                        },
                        select: ['ID', 'NAME', 'LAST_NAME']
                    })
                });

                const data = await response.json();
                return data.result && data.result.length > 0 ? data.result[0] : null;
            } catch (error) {
                console.error('Ошибка поиска исполнителя:', error);
                return null;
            }
        }

        async function loadDealDetails(dealId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.get`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: dealId,
                        select: [
                            'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID', 'COMMENTS',
                            'UF_CRM_685D295664A8A',
                            'UF_CRM_685D2956BF4C8',
                            'UF_CRM_685D2956C64E0',
                            'UF_CRM_685D2956D0916',
                            'UF_CRM_1751022940',
                            'UF_CRM_685D2956D7C70',
                            'UF_CRM_685D2956DF40F'
                        ]
                    })
                });

                const data = await response.json();
                return data.result || null;
            } catch (error) {
                console.error('Ошибка загрузки деталей сделки:', error);
                return null;
            }
        }

        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                return;
            }

            tg = Telegram.WebApp;

            try {
                user = tg.initDataUnsafe?.user || {};
                const performerContact = await findPerformerByTgId(user.id);

                if (performerContact) {
                    contactId = performerContact.ID;
                    performerName = `${performerContact.NAME || ''} ${performerContact.LAST_NAME || ''}`.trim();
                }

                const dealId = getUrlParameter('id');
                if (!dealId) {
                    throw new Error('Не указан ID заявки');
                }

                const deal = await loadDealDetails(dealId);
                if (!deal) {
                    throw new Error('Заявка не найдена');
                }

                renderDealDetails(deal);

                if (performerContact && deal.STAGE_ID === 'EXECUTING') {
                    document.getElementById('completion-section').style.display = 'block';
                }

                // Инициализация кнопок загрузки фото
                initPhotoButtons();

            } catch (e) {
                console.error('Ошибка инициализации:', e);
                document.getElementById('deal-container').innerHTML = `
                    <div class="alert alert-danger">
                        ${e.message || 'Ошибка загрузки данных'}
                    </div>
                `;
            }
        }

        function renderDealDetails(deal) {
            const createdDate = new Date(deal.DATE_CREATE).toLocaleString();
            const serviceDate = deal.UF_CRM_685D295664A8A ?
                new Date(deal.UF_CRM_685D295664A8A).toLocaleDateString() : '-';

            let serviceNames = '-';
            const serviceField = deal.UF_CRM_685D2956C64E0;
            if (serviceField) {
                let serviceIds = [];
                if (Array.isArray(serviceField)) {
                    serviceIds = serviceField.map(id => String(id));
                } else if (typeof serviceField === 'string') {
                    serviceIds = serviceField.split(',');
                } else {
                    serviceIds = [String(serviceField)];
                }
                serviceNames = serviceIds.map(id => {
                    if (id === '69') return 'Уход';
                    if (id === '71') return 'Цветы';
                    if (id === '73') return 'Ремонт';
                    if (id === '75') return 'Церковная служба';
                    return id;
                }).join(', ');
            }

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
                    <div class="detail-value">${serviceNames}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Город</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956BF4C8 || '-'}</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Исполнитель</div>
                    <div class="detail-value">${performerName || '-'}</div>
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

        function initPhotoButtons() {
            // Обработчики для кнопок загрузки фото
            document.getElementById('before-btn').addEventListener('click', function() {
                if (isAndroid && tg && tg.showCamera) {
                    openTelegramCamera('before');
                } else {
                    document.getElementById('before-photo').click();
                }
            });

            document.getElementById('after-btn').addEventListener('click', function() {
                if (isAndroid && tg && tg.showCamera) {
                    openTelegramCamera('after');
                } else {
                    document.getElementById('after-photo').click();
                }
            });

            // Обработчики для скрытых input-элементов
            document.getElementById('before-photo').addEventListener('change', function(e) {
                handlePhotoUpload(e.target.files[0], 'before');
            });

            document.getElementById('after-photo').addEventListener('change', function(e) {
                handlePhotoUpload(e.target.files[0], 'after');
            });
        }

        function openTelegramCamera(type) {
            if (tg && tg.showCamera) {
                const options = {
                    source: 'camera',
                    fileType: 'photo',
                    cameraType: 'back'
                };

                tg.showCamera(options, (result) => {
                    if (result && result.data) {
                        // Преобразуем base64 в Blob
                        const byteString = atob(result.data.split(',')[1]);
                        const mimeString = result.data.split(',')[0].split(':')[1].split(';')[0];
                        const ab = new ArrayBuffer(byteString.length);
                        const ia = new Uint8Array(ab);

                        for (let i = 0; i < byteString.length; i++) {
                            ia[i] = byteString.charCodeAt(i);
                        }

                        const blob = new Blob([ab], {
                            type: mimeString
                        });
                        const file = new File([blob], `${type}-photo.jpg`, {
                            type: mimeString
                        });

                        handlePhotoUpload(file, type);
                    }
                });
            } else {
                // Fallback для Android без WebApp API
                document.getElementById(`${type}-photo`).click();
            }
        }

        function handlePhotoUpload(file, type) {
            const previewId = `${type}-preview`;
            const previewElement = document.getElementById(previewId);

            if (!file || !file.type.match('image.*')) {
                showError('Пожалуйста, выберите изображение!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewElement.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                previewElement.appendChild(img);

                if (type === 'before') {
                    beforePhotoFile = file;
                } else {
                    afterPhotoFile = file;
                }

                checkCompletionReady();
            };
            reader.readAsDataURL(file);
        }

        function showError(message) {
            if (tg && tg.showPopup) {
                tg.showPopup({
                    title: 'Ошибка',
                    message: message,
                    buttons: [{
                        id: 'ok',
                        type: 'ok'
                    }]
                });
            } else {
                alert(message);
            }
        }

        function checkCompletionReady() {
            const completeBtn = document.getElementById('complete-deal-btn');
            completeBtn.disabled = !(beforePhotoFile && afterPhotoFile);
        }

        document.getElementById('complete-deal-btn').addEventListener('click', async function() {
            const dealId = getUrlParameter('id');
            const btn = this;

            if (!beforePhotoFile || !afterPhotoFile) {
                showError('Загрузите оба фото!');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Отправка...';

            try {
                const formData = new FormData();
                formData.append('deal_id', dealId);
                formData.append('before_photo', beforePhotoFile);
                formData.append('after_photo', afterPhotoFile);

                const response = await fetch('complete_deal.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Заказ успешно завершен!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.error || 'Не удалось завершить заказ');
                }
            } catch (error) {
                showError('Ошибка: ' + error.message);
                btn.disabled = false;
                btn.textContent = 'Завершить заказ';
            }
        });

        function showSuccess(message) {
            if (tg && tg.showPopup) {
                tg.showPopup({
                    title: 'Успех',
                    message: message,
                    buttons: [{
                        id: 'ok',
                        type: 'ok'
                    }]
                });
            } else {
                alert(message);
            }
        }

        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>

</html>