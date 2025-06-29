<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация исполнителя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            margin-top: 20px;
        }

        .location-btn {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .location-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .coords-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .form-error {
            display: none;
            color: #ff6b6b;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .debug-info {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="performer-form">
    <div class="container py-4">
        <div class="greeting text-center mb-4" id="greeting">Регистрация исполнителя</div>
        <div id="user-container" class="text-center mb-4"></div>

        <div class="form-container" id="form-container">
            <form id="performer-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Имя</label>
                            <input type="text" id="first-name" class="form-control" required>
                            <div class="form-error" id="first-name-error">Введите имя</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Фамилия</label>
                            <input type="text" id="last-name" class="form-control" required>
                            <div class="form-error" id="last-name-error">Введите фамилию</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Отчество</label>
                    <input type="text" id="second-name" class="form-control">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Телефон</label>
                            <input type="tel" id="phone" class="form-control" placeholder="+7 (999) 999-99-99" required>
                            <div class="form-error" id="phone-error">Введите корректный номер телефона</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Email</label>
                            <input type="email" id="email" class="form-control" placeholder="ваш@email.com" required>
                            <div class="form-error" id="email-error">Введите корректный email</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Город</label>
                    <input type="text" id="city" class="form-control" required>
                    <div class="form-error" id="city-error">Введите город</div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Местоположение</label>
                    <button type="button" id="get-location-btn" class="location-btn">
                        Получить мои координаты
                    </button>
                    <div class="form-error" id="location-error">Не удалось получить координаты</div>

                    <div class="coords-container">
                        <div class="mb-2">Широта: <span id="latitude-display">не определено</span></div>
                        <div>Долгота: <span id="longitude-display">не определено</span></div>
                    </div>
                    <input type="hidden" id="latitude">
                    <input type="hidden" id="longitude">
                </div>
            </form>
        </div>

        <div class="debug-info" id="debug-info"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

        let tg = null;
        let user = null;
        let phoneMask = null;

        function debugLog(message) {
            console.log(message);
            const debugInfo = document.getElementById('debug-info');
            if (debugInfo) {
                debugInfo.innerHTML += `<div>${new Date().toLocaleTimeString()}: ${message}</div>`;
                debugInfo.scrollTop = debugInfo.scrollHeight;
            }
        }

        async function initApp() {
            debugLog('=== ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ ===');

            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                debugLog('Telegram WebApp API недоступно');
                showFallbackView();
                return;
            }

            tg = Telegram.WebApp;

            try {
                tg.ready();
                tg.enableClosingConfirmation();

                if (tg.isExpanded !== true && typeof tg.expand === 'function') {
                    tg.expand();
                }

                user = tg.initDataUnsafe?.user || {};
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';

                debugLog(`Пользователь Telegram: ${firstName} ${lastName} (ID: ${user.id || 'нет'})`);

                const userContainer = document.getElementById('user-container');
                if (userContainer) {
                    userContainer.innerHTML = `
                        <div class="d-flex flex-column align-items-center">
                            <div class="avatar mb-3">
                                ${user.photo_url ? 
                                    `<img src="${user.photo_url}" alt="${firstName} ${lastName}" class="img-fluid rounded-circle" style="width:80px;height:80px;">` : 
                                    `<div class="d-flex align-items-center justify-content-center rounded-circle bg-light text-dark fw-bold" style="width:80px;height:80px;font-size:2rem;">${firstName.charAt(0) || 'И'}</div>`
                                }
                            </div>
                            <div class="user-name fs-5">${firstName} ${lastName}</div>
                        </div>
                    `;
                }

                if (firstName) {
                    document.getElementById('first-name').value = firstName;
                }
                if (lastName) {
                    document.getElementById('last-name').value = lastName;
                }

                const phoneInput = document.getElementById('phone');
                if (phoneInput) {
                    phoneMask = new IMask(phoneInput, {
                        mask: '+{7} (000) 000-00-00'
                    });

                    phoneInput.addEventListener('focus', function() {
                        if (!this.value.trim()) {
                            phoneMask.unmaskedValue = '7';
                            phoneMask.updateValue();
                        }
                    });
                }

                document.getElementById('get-location-btn').addEventListener('click', getLocation);

                if (tg.MainButton) {
                    tg.MainButton.setText("Зарегистрироваться");
                    tg.MainButton.onClick(submitForm);
                    tg.MainButton.show();
                }

            } catch (e) {
                debugLog(`Ошибка инициализации: ${e.message}`);
                showFallbackView();
            }
        }

        function getLocation() {
            debugLog('Запрос геолокации...');

            if (!navigator.geolocation) {
                showLocationError("Геолокация не поддерживается вашим браузером");
                return;
            }

            const btn = document.getElementById('get-location-btn');
            if (!btn) return;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Определение местоположения...';
            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    debugLog(`Координаты получены: ${lat}, ${lng}`);

                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;

                    document.getElementById('latitude-display').textContent = lat.toFixed(6);
                    document.getElementById('longitude-display').textContent = lng.toFixed(6);

                    document.getElementById('location-error').style.display = 'none';

                    btn.innerHTML = '✅ Координаты получены!';
                    btn.disabled = false;
                },
                error => {
                    let message = "Не удалось получить координаты";
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            message = "Доступ к геолокации запрещен";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = "Информация о местоположении недоступна";
                            break;
                        case error.TIMEOUT:
                            message = "Время ожидания истекло";
                            break;
                    }

                    debugLog(`Ошибка геолокации: ${message}`);
                    showLocationError(message);
                    btn.innerHTML = 'Получить мои координаты';
                    btn.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function showLocationError(message) {
            const errorEl = document.getElementById('location-error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            }
        }

        async function submitForm() {
            debugLog('=== ОТПРАВКА ФОРМЫ ===');

            const formData = {
                firstName: document.getElementById('first-name').value || '',
                lastName: document.getElementById('last-name').value || '',
                secondName: document.getElementById('second-name').value || '',
                phone: phoneMask?.unmaskedValue || '',
                email: document.getElementById('email').value || '',
                city: document.getElementById('city').value || '',
                latitude: document.getElementById('latitude').value || '',
                longitude: document.getElementById('longitude').value || '',
                tgUserId: user?.id || null
            };

            debugLog('Данные формы: ' + JSON.stringify(formData));

            if (!validateForm(formData)) {
                debugLog('Валидация формы не пройдена');
                return;
            }

            try {
                debugLog('Попытка сохранения исполнителя в Bitrix24');

                if (tg.showProgress) tg.showProgress();
                if (tg.MainButton) {
                    tg.MainButton.setText("Отправка...");
                    tg.MainButton.disable();
                }

                const result = await savePerformer(formData);

                if (result.success) {
                    debugLog('Успешная регистрация! ID контакта: ' + result.contactId);

                    tg.showPopup({
                        title: 'Успех',
                        message: 'Регистрация завершена успешно!',
                        buttons: [{
                            id: 'ok',
                            type: 'ok'
                        }]
                    });

                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    debugLog(`Ошибка регистрации: ${result.errorMessage}`);
                    tg.showPopup({
                        title: 'Ошибка регистрации',
                        message: result.errorMessage || 'Не удалось зарегистрироваться',
                        buttons: [{
                            id: 'ok',
                            type: 'ok'
                        }]
                    });
                }
            } catch (error) {
                debugLog(`Непредвиденная ошибка: ${error.message}`);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла непредвиденная ошибка',
                    buttons: [{
                        id: 'ok',
                        type: 'ok'
                    }]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
                if (tg.MainButton) {
                    tg.MainButton.setText("Зарегистрироваться");
                    tg.MainButton.enable();
                }
            }
        }

        function validateForm(formData) {
            debugLog('Проверка валидации формы');

            let isValid = true;

            document.querySelectorAll('.form-error').forEach(el => {
                el.style.display = 'none';
            });

            if (!formData.firstName.trim()) {
                document.getElementById('first-name-error').style.display = 'block';
                isValid = false;
            }

            if (!formData.lastName.trim()) {
                document.getElementById('last-name-error').style.display = 'block';
                isValid = false;
            }

            const phoneValue = formData.phone;
            if (!phoneValue || phoneValue.length !== 11) {
                document.getElementById('phone-error').style.display = 'block';
                isValid = false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }

            if (!formData.city.trim()) {
                document.getElementById('city-error').style.display = 'block';
                isValid = false;
            }

            if (!formData.latitude || !formData.longitude) {
                showLocationError("Получите координаты для продолжения");
                isValid = false;
            }

            debugLog(`Валидация ${isValid ? 'пройдена' : 'не пройдена'}`);
            return isValid;
        }

        async function savePerformer(data) {
            debugLog('Подготовка данных для Bitrix24...');

            try {
                const contactData = {
                    fields: {
                        NAME: data.firstName,
                        LAST_NAME: data.lastName,
                        SECOND_NAME: data.secondName || '',
                        PHONE: [{
                            VALUE: data.phone,
                            VALUE_TYPE: 'WORK'
                        }],
                        EMAIL: [{
                            VALUE: data.email,
                            VALUE_TYPE: 'WORK'
                        }],
                        TYPE_ID: "1", // Тип контакта: контактное лицо
                        SOURCE_ID: 'REPEAT_SALE',
                        UF_CRM_685D2956061DB: data.city, // Город
                        UF_CRM_1751129816: data.latitude, // Широта
                        UF_CRM_1751129854: data.longitude, // Долгота
                        UF_CRM_1751128872: String(data.tgUserId) // Telegram ID (строка)
                    }
                };

                debugLog('Отправляемые данные: ' + JSON.stringify(contactData));

                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(contactData)
                });

                const result = await response.json();
                debugLog('Ответ от Bitrix24: ' + JSON.stringify(result));

                if (result.error) {
                    throw new Error(result.error_description || `Ошибка Bitrix: ${result.error}`);
                }

                if (!result.result) {
                    throw new Error('Не удалось создать контакт в Bitrix24');
                }

                return {
                    success: true,
                    contactId: result.result,
                    message: 'Контакт успешно создан в Bitrix24'
                };

            } catch (error) {
                debugLog(`ОШИБКА СОХРАНЕНИЯ: ${error.message}`);
                return {
                    success: false,
                    errorMessage: `Ошибка при сохранении: ${error.message}`
                };
            }
        }

        function showFallbackView() {
            const greeting = document.getElementById('greeting');
            const userContainer = document.getElementById('user-container');

            if (greeting) {
                greeting.textContent = 'Регистрация исполнителя';
            }

            if (userContainer) {
                userContainer.innerHTML = `
                    <div class="alert alert-warning">
                        Для регистрации откройте приложение в Telegram
                    </div>
                `;
            }
        }

        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>

</html>