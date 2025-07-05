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

        .back-btn {
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

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateY(-2px);
        }

        .detail-card {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .completion-section {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
        }

        .photo-upload-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .photo-upload {
            flex: 1;
            min-width: 250px;
            text-align: center;
        }

        .photo-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.5);
        }

        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6a11cb;
            color: white !important;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .upload-btn:hover {
            background: #4d0d99;
        }

        .complete-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .complete-btn:hover {
            background: #218838;
        }

        .complete-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .detail-label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #6a11cb !important;
        }

        .detail-value {
            font-size: 1.05rem;
        }
    </style>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="/webapp/doer/dashboard.php" class="back-btn">← Назад к списку заявок</a>
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
                            <span>Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="before_photo" accept="image/*" hidden>
                        </label>
                    </div>
                    <div class="photo-upload">
                        <div class="detail-label">Фото после работы</div>
                        <div class="photo-preview" id="after-preview">
                            <span>Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="after_photo" accept="image/*" hidden>
                        </label>
                    </div>
                </div>

                <button type="submit" class="complete-btn" id="complete-btn">Завершить заявку</button>
            </form>
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

            // Показываем секцию завершения для исполнителя
            if (statusText === 'В работе') {
                document.getElementById('completion-section').style.display = 'block';
                document.getElementById('deal-id-hidden').value = deal.ID;

                // Получаем Telegram ID исполнителя
                const tgUserId = Telegram.WebApp.initDataUnsafe?.user?.id;
                document.getElementById('tg-user-id-hidden').value = tgUserId || '';
            }

            // Инициализация обработчиков
            initEventHandlers();
        }

        function initEventHandlers() {
            // Обработчики загрузки фото
            document.querySelector('input[name="before_photo"]').addEventListener('change', function(e) {
                handleImageUpload(e.target, 'before-preview');
            });

            document.querySelector('input[name="after_photo"]').addEventListener('change', function(e) {
                handleImageUpload(e.target, 'after-preview');
            });

            // Отправка формы
            document.getElementById('complete-deal-form').addEventListener('submit', function(e) {
                e.preventDefault();
                completeDeal();
            });
        }

        function handleImageUpload(input, previewId) {
            const file = input.files[0];
            if (!file) return;

            const preview = document.getElementById(previewId);
            preview.innerHTML = '';

            // Проверка типа файла
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                preview.innerHTML = '<div class="text-danger">Недопустимый формат</div>';
                input.value = '';
                return;
            }

            // Проверка размера файла (5MB)
            if (file.size > 5 * 1024 * 1024) {
                preview.innerHTML = '<div class="text-danger">Файл слишком большой</div>';
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-fluid');
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }

        async function completeDeal() {
            const form = document.getElementById('complete-deal-form');
            const formData = new FormData(form);
            const completeBtn = document.getElementById('complete-btn');

            // Проверка что оба фото загружены
            const beforePhoto = formData.get('before_photo');
            const afterPhoto = formData.get('after_photo');

            if (!beforePhoto || !afterPhoto || beforePhoto.size === 0 || afterPhoto.size === 0) {
                Telegram.WebApp.showAlert('Загрузите оба фото (до и после работы)');
                return;
            }

            completeBtn.disabled = true;
            completeBtn.textContent = 'Отправка...';

            try {
                // Показать индикатор загрузки в Telegram
                if (Telegram.WebApp.showProgress) Telegram.WebApp.showProgress();

                // Добавляем временные метки для обхода кеширования
                const url = `/webapp/complete_deal.php?t=${Date.now()}`;

                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                // Проверка HTTP статуса
                if (!response.ok) {
                    let errorText = '';
                    try {
                        const errorData = await response.json();
                        errorText = errorData.error || 'Неизвестная ошибка сервера';
                    } catch (e) {
                        errorText = await response.text();
                    }

                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }

                const result = await response.json();

                if (result.success) {
                    Telegram.WebApp.showAlert('✅ Заявка успешно завершена!', () => {
                        // Обновляем страницу без перезагрузки
                        loadDealDetails();

                        // Скрываем секцию завершения
                        document.getElementById('completion-section').style.display = 'none';

                        // Сбрасываем форму
                        form.reset();
                        document.getElementById('before-preview').innerHTML = '<span>Изображение не выбрано</span>';
                        document.getElementById('after-preview').innerHTML = '<span>Изображение не выбрано</span>';
                    });
                } else {
                    throw new Error(result.error || 'Не удалось завершить сделку');
                }
            } catch (error) {
                console.error('Ошибка:', error);

                let errorMessage = error.message;
                // Упрощаем сообщение для пользователя
                if (errorMessage.includes('Failed to fetch')) {
                    errorMessage = 'Ошибка соединения с сервером. Проверьте интернет-соединение';
                } else if (errorMessage.includes('HTTP error')) {
                    errorMessage = 'Ошибка сервера: ' + errorMessage.split(':')[1];
                }

                Telegram.WebApp.showAlert(`❌ ${errorMessage}`);
            } finally {
                completeBtn.disabled = false;
                completeBtn.textContent = 'Завершить заявку';
                if (Telegram.WebApp.hideProgress) Telegram.WebApp.hideProgress();
            }
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