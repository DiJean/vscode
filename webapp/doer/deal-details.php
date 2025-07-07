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

    <script>
        window.BITRIX_WEBHOOK = '<?= BITRIX_WEBHOOK ?>';
    </script>

    <style>
        .detail-card {
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: bold;
            opacity: 0.8;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #555;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #000;
        }

        .completion-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .completion-section h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .photo-upload-container {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .photo-upload {
            flex: 1;
            min-width: 250px;
            text-align: center;
        }

        .photo-preview {
            width: 100%;
            height: 250px;
            border-radius: 12px;
            background: #f8f9fa;
            margin-bottom: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #ced4da;
            position: relative;
        }

        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .photo-placeholder {
            color: #6c757d;
            font-size: 1rem;
        }

        .upload-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: rgba(106, 17, 203, 0.1);
            border-radius: 12px;
            color: #495057;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px dashed rgba(106, 17, 203, 0.5);
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }

        .upload-btn:hover {
            background: rgba(106, 17, 203, 0.2);
        }

        .upload-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .complete-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            text-align: center;
            border-radius: 12px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .complete-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .complete-btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .photo-thumbnail {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
            border: 1px solid #ddd;
            padding: 5px;
            background: white;
            margin-top: 10px;
        }

        .photo-thumbnail:hover {
            transform: scale(1.05);
        }

        .completed-photos {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="/webapp/doer/dashboard.php" class="btn btn-outline-primary">← Назад к списку заявок</a>
        </div>

        <h1 class="text-center mb-4">Детали заявки</h1>

        <div class="detail-card" id="deal-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>

        <!-- Секция завершения заявки -->
        <div class="completion-section" id="completion-section" style="display: none;">
            <h3>Завершение заявки</h3>
            <form id="complete-deal-form" enctype="multipart/form-data">
                <input type="hidden" name="deal_id" id="deal-id-hidden">
                <input type="hidden" name="tg_user_id" id="tg-user-id-hidden">

                <div class="photo-upload-container">
                    <div class="photo-upload">
                        <div class="detail-label">Фото до работы</div>
                        <div class="photo-preview" id="before-preview">
                            <span class="photo-placeholder">Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="before_photo" accept="image/*" required>
                        </label>
                    </div>
                    <div class="photo-upload">
                        <div class="detail-label">Фото после работы</div>
                        <div class="photo-preview" id="after-preview">
                            <span class="photo-placeholder">Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="after_photo" accept="image/*" required>
                        </label>
                    </div>
                </div>

                <button type="submit" class="complete-btn" id="complete-btn">Завершить заявку</button>
            </form>

            <div class="completed-photos" id="completed-photos" style="display: none;">
                <h4>Загруженные фото</h4>
                <div class="row mt-3" id="uploaded-photos-container">
                    <!-- Здесь будут отображаться загруженные фото -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        const BITRIX_WEBHOOK = window.BITRIX_WEBHOOK;
        const version = '<?= $version ?>';

        // Словарь статусов заявок
        const stageNames = {
            'NEW': 'Новый заказ',
            'PREPARATION': 'Подготовка',
            'PREPAYMENT_INVOICE': 'Оплата',
            'EXECUTING': 'В работе',
            'FINAL_INVOICE': 'Выставлен счет',
            'WON': 'Успешно завершена',
            'LOSE': 'Не нашли участок',
            'APOLOGY': 'Анализ неудачи'
        };

        document.addEventListener('DOMContentLoaded', async function() {
            // Инициализация Telegram WebApp
            let tg = null;
            let user = null;
            try {
                if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                    tg = Telegram.WebApp;
                    tg.ready();
                    user = tg.initDataUnsafe.user;
                }
            } catch (e) {
                console.error('Telegram WebApp init error', e);
            }

            // Получаем ID заявки из URL
            const urlParams = new URLSearchParams(window.location.search);
            const dealId = urlParams.get('id');
            if (!dealId) {
                showError('Не указан ID заявки');
                return;
            }

            // Загружаем детали заявки
            const dealContainer = document.getElementById('deal-container');
            try {
                const deal = await getDealDetails(dealId);
                if (!deal) {
                    showError('Заявка не найдена');
                    return;
                }

                // Отображаем детали заявки
                renderDealDetails(deal);

                // Проверяем, является ли текущий пользователь исполнителем
                if (user) {
                    // Ищем контакт текущего пользователя (исполнителя) по Telegram ID
                    const performerContact = await findPerformerByTgId(user.id);
                    if (performerContact && performerContact.ID == deal.performerId) {
                        // Проверяем статус заявки
                        if (deal.stageId === 'EXECUTING') {
                            // Показываем секцию завершения
                            document.getElementById('completion-section').style.display = 'block';
                            // Заполняем hidden поля формы
                            document.getElementById('deal-id-hidden').value = dealId;
                            document.getElementById('tg-user-id-hidden').value = user.id;

                            // Инициализация загрузки фото
                            initPhotoUpload();
                        }

                        // Если заявка завершена, показываем загруженные фото
                        if (deal.stageId === 'WON') {
                            showUploadedPhotos(deal);
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки заявки', error);
                showError('Ошибка загрузки данных заявки');
            }
        });

        async function getDealDetails(dealId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.get.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: dealId,
                        select: [
                            'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID', 'COMMENTS',
                            'UF_CRM_685D295664A8A', // Желаемая дата услуги
                            'UF_CRM_685D2956BF4C8', // Город
                            'UF_CRM_685D2956C64E0', // Услуги
                            'UF_CRM_685D2956D0916', // Кладбище
                            'UF_CRM_1751022940', // Сектор
                            'UF_CRM_685D2956D7C70', // Ряд
                            'UF_CRM_685D2956DF40F', // Участок
                            'UF_CRM_1751128612', // Исполнитель (ID контакта)
                            'UF_CRM_1751200529', // Фото до
                            'UF_CRM_1751200549' // Фото после
                        ]
                    })
                });

                const data = await response.json();
                if (data.result) {
                    return {
                        id: data.result.ID,
                        title: data.result.TITLE,
                        dateCreate: data.result.DATE_CREATE,
                        stageId: data.result.STAGE_ID,
                        comments: data.result.COMMENTS,
                        serviceDate: data.result.UF_CRM_685D295664A8A,
                        city: data.result.UF_CRM_685D2956BF4C8,
                        services: data.result.UF_CRM_685D2956C64E0,
                        cemetery: data.result.UF_CRM_685D2956D0916,
                        sector: data.result.UF_CRM_1751022940,
                        row: data.result.UF_CRM_685D2956D7C70,
                        plot: data.result.UF_CRM_685D2956DF40F,
                        performerId: data.result.UF_CRM_1751128612,
                        beforePhoto: data.result.UF_CRM_1751200529,
                        afterPhoto: data.result.UF_CRM_1751200549
                    };
                }
                return null;
            } catch (error) {
                console.error('Ошибка получения деталей заявки:', error);
                return null;
            }
        }

        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        filter: {
                            'UF_CRM_1751128872': String(tgId)
                        },
                        select: ['ID']
                    })
                });

                const data = await response.json();
                return data.result && data.result.length > 0 ? data.result[0] : null;
            } catch (error) {
                console.error('Ошибка поиска исполнителя:', error);
                return null;
            }
        }

        function renderDealDetails(deal) {
            const dealContainer = document.getElementById('deal-container');
            // Форматируем дату
            const createdDate = new Date(deal.dateCreate).toLocaleDateString();
            const serviceDate = deal.serviceDate ? new Date(deal.serviceDate).toLocaleDateString() : 'не указана';

            // Преобразуем ID услуг в названия
            let services = 'не указаны';
            if (deal.services) {
                let serviceIds = [];
                if (Array.isArray(deal.services)) {
                    serviceIds = deal.services;
                } else if (typeof deal.services === 'string') {
                    serviceIds = deal.services.split(',');
                } else {
                    serviceIds = [String(deal.services)];
                }

                services = serviceIds.map(id => {
                    if (id === '69') return 'Уход';
                    if (id === '71') return 'Цветы';
                    if (id === '73') return 'Ремонт';
                    if (id === '75') return 'Церковная служба';
                    return `Услуга #${id}`;
                }).join(', ');
            }

            // Создаем HTML
            dealContainer.innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Номер заявки</div>
                    <div class="detail-value">${deal.id}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Статус</div>
                    <div class="detail-value">${stageNames[deal.stageId] || deal.stageId}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Дата создания</div>
                    <div class="detail-value">${createdDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Желаемая дата исполнения</div>
                    <div class="detail-value">${serviceDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Услуги</div>
                    <div class="detail-value">${services}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Город</div>
                    <div class="detail-value">${deal.city || 'не указан'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Кладбище</div>
                    <div class="detail-value">${deal.cemetery || 'не указано'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Сектор</div>
                    <div class="detail-value">${deal.sector || 'не указан'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ряд</div>
                    <div class="detail-value">${deal.row || 'не указан'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Участок</div>
                    <div class="detail-value">${deal.plot || 'не указан'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Комментарий</div>
                    <div class="detail-value">${deal.comments || 'нет'}</div>
                </div>
            `;
        }

        function initPhotoUpload() {
            // Обработчики для загрузки фото и предпросмотра
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const previewId = this.name === 'before_photo' ? 'before-preview' : 'after-preview';
                    const preview = document.getElementById(previewId);

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            preview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = '<span class="photo-placeholder">Изображение не выбрано</span>';
                    }
                });
            });

            // Обработчик отправки формы
            document.getElementById('complete-deal-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                // Проверка наличия файлов
                const beforeFile = this.elements.before_photo.files[0];
                const afterFile = this.elements.after_photo.files[0];

                if (!beforeFile || !afterFile) {
                    alert('Пожалуйста, загрузите оба фото!');
                    return;
                }

                // Проверка, что файлы не пустые
                if (beforeFile.size === 0 || afterFile.size === 0) {
                    alert('Файлы не должны быть пустыми!');
                    return;
                }

                const formData = new FormData(this);
                const completeBtn = document.getElementById('complete-btn');
                completeBtn.disabled = true;
                completeBtn.textContent = 'Отправка...';

                try {
                    const response = await fetch('/webapp/doer/complete_deal.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Показываем сообщение об успехе
                        if (typeof Telegram !== 'undefined' && Telegram.WebApp && Telegram.WebApp.showPopup) {
                            Telegram.WebApp.showPopup({
                                title: 'Успех!',
                                message: 'Заявка успешно завершена',
                                buttons: [{
                                    id: 'ok',
                                    type: 'ok'
                                }]
                            });
                        } else {
                            alert('Заявка успешно завершена!');
                        }

                        // Обновляем страницу через 2 секунды
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert(result.error || 'Ошибка при завершении заявки');
                        completeBtn.disabled = false;
                        completeBtn.textContent = 'Завершить заявку';
                    }
                } catch (error) {
                    console.error('Ошибка отправки формы', error);
                    alert('Сетевая ошибка');
                    completeBtn.disabled = false;
                    completeBtn.textContent = 'Завершить заявку';
                }
            });
        }

        function showUploadedPhotos(deal) {
            // Показываем секцию с загруженными фото
            document.getElementById('completed-photos').style.display = 'block';
            const container = document.getElementById('uploaded-photos-container');

            // Формируем URL для фото
            const baseUrl = BITRIX_WEBHOOK.replace('/rest/', '');

            let photosHTML = '';

            // Фото "до"
            if (deal.beforePhoto && deal.beforePhoto.length > 0) {
                const photoId = deal.beforePhoto[0];
                const photoUrl = `${baseUrl}download.php?auth=1&fileId=${photoId}`;

                photosHTML += `
                    <div class="col-md-6 mb-4">
                        <div class="detail-label">Фото до работы</div>
                        <img src="${photoUrl}" alt="Фото до работы" class="photo-thumbnail">
                    </div>
                `;
            }

            // Фото "после"
            if (deal.afterPhoto && deal.afterPhoto.length > 0) {
                const photoId = deal.afterPhoto[0];
                const photoUrl = `${baseUrl}download.php?auth=1&fileId=${photoId}`;

                photosHTML += `
                    <div class="col-md-6 mb-4">
                        <div class="detail-label">Фото после работы</div>
                        <img src="${photoUrl}" alt="Фото после работы" class="photo-thumbnail">
                    </div>
                `;
            }

            container.innerHTML = photosHTML || '<div class="col-12 text-center">Фото не загружены</div>';
        }

        function showError(message) {
            const dealContainer = document.getElementById('deal-container');
            dealContainer.innerHTML = `<div class="alert alert-danger">${message}</div>`;
        }
    </script>
</body>

</html>