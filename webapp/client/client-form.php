<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/client-form.css?<?= $version ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="my-services.php" class="back-btn">← Назад к моим заявкам</a>
        </div>

        <div class="greeting text-center mb-4" id="greeting">Оформление заявки</div>
        <div id="user-container" class="text-center mb-4"></div>

        <div class="form-container" id="form-container">
            <form id="service-form">
                <div class="mb-3">
                    <label class="form-label required">Имя и фамилия</label>
                    <input type="text" id="full-name" class="form-control" readonly>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Телефон</label>
                        <input type="tel" id="phone" class="form-control" placeholder="+7 (999) 999-99-99" required>
                        <div class="form-error" id="phone-error">Введите 10 цифр номера телефона</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Email</label>
                        <input type="email" id="email" class="form-control" placeholder="ваш@email.com" required>
                        <div class="form-error" id="email-error">Введите корректный email</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Услуги</label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="service-checkbox">
                                <input type="checkbox" name="services" value="69" id="service1">
                                <label for="service1">Уход</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-checkbox">
                                <input type="checkbox" name="services" value="71" id="service2">
                                <label for="service2">Цветы</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-checkbox">
                                <input type="checkbox" name="services" value="73" id="service3">
                                <label for="service3">Ремонт</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-checkbox">
                                <input type="checkbox" name="services" value="75" id="service4">
                                <label for="service4">Церковная служба</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-error" id="services-error">Выберите хотя бы одну услугу</div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Город</label>
                    <input type="text" id="city" class="form-control" required>
                    <div class="form-error" id="city-error">Введите город</div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Желаемая дата услуги</label>
                    <div class="input-group">
                        <input type="text" id="service-date" class="form-control date-picker" readonly required>
                        <button type="button" class="btn btn-outline-light" id="date-picker-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z" />
                                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" />
                            </svg>
                        </button>
                    </div>
                    <div class="form-error" id="date-error">Выберите дату</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Кладбище</label>
                    <input type="text" id="cemetery" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Сектор</label>
                    <input type="text" id="sector" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Ряд</label>
                    <input type="text" id="row" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Участок</label>
                    <input type="text" id="plot" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Дополнительная информация</label>
                    <textarea id="additional-info" class="form-control" rows="3"></textarea>
                </div>
            </form>
        </div>

        <div class="debug-info" id="debug-info"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const version = '<?= $version ?>';

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

        function isBitrixReady() {
            if (typeof BitrixCRM === 'undefined') {
                debugLog('BitrixCRM не загружен!');
                return false;
            }

            if (typeof BitrixCRM.processServiceRequest !== 'function') {
                debugLog('Функция processServiceRequest недоступна!');
                return false;
            }

            return true;
        }

        async function loadBitrixModule() {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = `/webapp/js/bitrix-integration.js?${Date.now()}`;
                script.onload = () => resolve(true);
                script.onerror = () => resolve(false);
                document.head.appendChild(script);
            });
        }

        function initDatePicker() {
            const dateInput = document.getElementById('service-date');
            const dateBtn = document.getElementById('date-picker-btn');

            // Проверка поддержки input type="date"
            const isDateInputSupported = () => {
                const input = document.createElement('input');
                input.setAttribute('type', 'date');
                const notADateValue = 'not-a-date';
                input.setAttribute('value', notADateValue);
                return input.value !== notADateValue;
            };

            if (isDateInputSupported()) {
                // Используем нативный date picker
                const nativeInput = document.createElement('input');
                nativeInput.type = 'date';
                nativeInput.className = 'form-control';
                nativeInput.id = 'service-date';
                nativeInput.min = new Date().toISOString().split('T')[0];
                nativeInput.required = true;

                dateInput.replaceWith(nativeInput);
            } else {
                // Кастомный date picker для мобильных устройств
                dateBtn.addEventListener('click', () => {
                    if (window.Telegram && Telegram.WebApp && Telegram.WebApp.showDatePicker) {
                        const today = new Date();
                        Telegram.WebApp.showDatePicker({
                            min_date: Math.floor(today.getTime() / 1000),
                            initial_date: Math.floor(today.getTime() / 1000),
                        }, (selected) => {
                            if (selected) {
                                const date = new Date(selected * 1000);
                                const formattedDate = date.toISOString().split('T')[0];
                                dateInput.value = formattedDate;
                            }
                        });
                    } else {
                        // Fallback для браузеров
                        const today = new Date().toISOString().split('T')[0];
                        dateInput.type = 'date';
                        dateInput.min = today;
                        dateInput.focus();
                    }
                });
            }
        }

        async function initApp() {
            debugLog('=== ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ ===');
            debugLog(`Версия: ${version}`);

            // Проверка загрузки BitrixCRM
            if (!isBitrixReady()) {
                debugLog('Попытка перезагрузки модуля BitrixCRM...');
                const reloadSuccess = await loadBitrixModule();

                if (!reloadSuccess || !isBitrixReady()) {
                    debugLog('КРИТИЧЕСКАЯ ОШИБКА: Не удалось загрузить BitrixCRM');
                    alert('Системная ошибка. Пожалуйста, перезагрузите страницу.');
                    return;
                }
            }

            // Основной способ получения данных - из главной страницы
            const storedUser = sessionStorage.getItem('telegramUser') || localStorage.getItem('telegramUser');
            if (storedUser) {
                user = JSON.parse(storedUser);
                debugLog('Пользователь восстановлен из хранилища');
            }

            // Если в хранилище нет данных, пробуем получить из WebApp
            if (!user && typeof Telegram !== 'undefined' && Telegram.WebApp) {
                tg = Telegram.WebApp;
                try {
                    tg.ready();
                    user = tg.initDataUnsafe?.user || {};
                    debugLog('Пользователь получен из WebApp');

                    // Сохраняем для будущего использования
                    sessionStorage.setItem('telegramUser', JSON.stringify(user));
                    localStorage.setItem('telegramUser', JSON.stringify(user));

                    // Сохраняем Telegram User ID
                    if (user.id) {
                        localStorage.setItem('tgUserId', user.id);
                    }
                } catch (e) {
                    debugLog('Ошибка получения данных из WebApp: ' + e.message);
                }
            }

            // Если данные все еще не получены - создаем пустой объект
            if (!user) {
                user = {};
                debugLog('Данные пользователя не найдены');
            }

            const firstName = user.first_name || '';
            const lastName = user.last_name || '';
            const fullName = `${firstName} ${lastName}`.trim() || 'Клиент';

            debugLog(`Имя пользователя: ${fullName}`);
            debugLog(`Telegram ID: ${user.id || 'не задан'}`);

            document.getElementById('greeting').textContent = 'Оформление заявки';

            const userContainer = document.getElementById('user-container');
            if (userContainer) {
                userContainer.innerHTML = `
                    <div class="d-flex flex-column align-items-center">
                        <div class="avatar mb-3">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle" style="width:80px;height:80px;">` : 
                                `<div class="d-flex align-items-center justify-content-center rounded-circle bg-light text-dark fw-bold" style="width:80px;height:80px;font-size:2rem;">${firstName.charAt(0) || 'К'}</div>`
                            }
                        </div>
                        <div class="user-name fs-5">${fullName}</div>
                    </div>
                `;
            }

            // Устанавливаем имя пользователя в поле (только для чтения)
            document.getElementById('full-name').value = fullName;

            // Инициализация маски телефона с предзаполненным +7
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneMask = new IMask(phoneInput, {
                    mask: '+{7} (000) 000-00-00',
                    lazy: false,
                    placeholderChar: ' ',
                    blocks: {
                        '0': {
                            mask: /[0-9]/,
                            placeholderChar: '_'
                        }
                    }
                });

                // Устанавливаем начальное значение: +7
                phoneMask.unmaskedValue = '7';
                phoneMask.updateValue();

                // При фокусе сохраняем +7, если поле пустое
                phoneInput.addEventListener('focus', function() {
                    if (phoneMask.unmaskedValue === '') {
                        phoneMask.unmaskedValue = '7';
                        phoneMask.updateValue();
                    }
                });
            }

            // Инициализация date picker
            initDatePicker();

            // Инициализация кнопки Telegram
            if (typeof Telegram !== 'undefined' && Telegram.WebApp && Telegram.WebApp.MainButton) {
                tg = Telegram.WebApp;
                tg.MainButton.setText("Отправить заявку");
                tg.MainButton.onClick(submitForm);
                tg.MainButton.show();
            }
        }

        async function submitForm() {
            debugLog('=== ОТПРАВКА ФОРМЫ ===');

            if (!isBitrixReady()) {
                debugLog('ОШИБКА: BitrixCRM недоступен при отправке');
                alert('Системная ошибка. Попробуйте перезагрузить страницу.');
                return;
            }

            const formData = {
                fullName: document.getElementById('full-name').value,
                phone: phoneMask?.unmaskedValue || '',
                email: document.getElementById('email').value,
                services: Array.from(document.querySelectorAll('input[name="services"]:checked'))
                    .map(checkbox => checkbox.value),
                city: document.getElementById('city').value,
                serviceDate: document.getElementById('service-date').value,
                cemetery: document.getElementById('cemetery').value,
                sector: document.getElementById('sector').value,
                row: document.getElementById('row').value,
                plot: document.getElementById('plot').value,
                additionalInfo: document.getElementById('additional-info').value,
                username: user?.username || null,
                tgUserId: user?.id || null
            };

            debugLog('Данные формы: ' + JSON.stringify(formData));

            if (!validateForm(formData)) {
                debugLog('Валидация формы не пройдена');
                return;
            }

            try {
                debugLog('Попытка отправки данных в Bitrix24');

                if (tg && tg.MainButton) {
                    tg.MainButton.setText("Отправка...");
                    tg.MainButton.disable();
                }
                if (tg && tg.showProgress) tg.showProgress();

                const response = await BitrixCRM.processServiceRequest(formData);
                debugLog('Ответ от Bitrix24: ' + JSON.stringify(response));

                if (response.success) {
                    // Сохраняем Telegram User ID для будущих запросов
                    if (formData.tgUserId) {
                        localStorage.setItem('tgUserId', formData.tgUserId);
                    }

                    if (tg && tg.showPopup) {
                        tg.showPopup({
                            title: 'Успешно!',
                            message: 'Заявка успешно создана',
                            buttons: [{
                                id: 'ok',
                                type: 'ok'
                            }]
                        });
                    } else {
                        alert('Заявка успешно создана');
                    }

                    setTimeout(() => {
                        window.location.href = 'my-services.php';
                    }, 1500);
                } else {
                    const errorMsg = response.error || 'Не удалось отправить заявку. Попробуйте позже.';
                    debugLog(`Ошибка создания заявки: ${errorMsg}`);

                    if (tg && tg.showPopup) {
                        tg.showPopup({
                            title: 'Ошибка',
                            message: errorMsg,
                            buttons: [{
                                id: 'ok',
                                type: 'ok'
                            }]
                        });
                    } else {
                        alert('Ошибка: ' + errorMsg);
                    }
                }
            } catch (error) {
                debugLog(`Критическая ошибка: ${error.message}`);
                if (tg && tg.showPopup) {
                    tg.showPopup({
                        title: 'Ошибка',
                        message: 'Произошла ошибка при отправке данных: ' + error.message,
                        buttons: [{
                            id: 'ok',
                            type: 'ok'
                        }]
                    });
                } else {
                    alert('Ошибка: ' + error.message);
                }
            } finally {
                if (tg && tg.hideProgress) tg.hideProgress();
                if (tg && tg.MainButton) {
                    tg.MainButton.setText("Отправить заявку");
                    tg.MainButton.enable();
                }
            }
        }

        function validateForm(formData) {
            debugLog('Проверка валидации формы');

            let isValid = true;

            // Скрываем все ошибки
            document.querySelectorAll('.form-error').forEach(el => {
                el.style.display = 'none';
            });

            const phoneRegex = /^7\d{10}$/;
            if (!phoneRegex.test(formData.phone)) {
                document.getElementById('phone-error').textContent = 'Введите 10 цифр номера телефона после +7 (например: 79650035577)';
                document.getElementById('phone-error').style.display = 'block';
                isValid = false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }

            if (formData.services.length === 0) {
                document.getElementById('services-error').style.display = 'block';
                isValid = false;
            }

            if (!formData.city.trim()) {
                document.getElementById('city-error').style.display = 'block';
                isValid = false;
            }

            if (!formData.serviceDate) {
                document.getElementById('date-error').textContent = 'Выберите дату';
                document.getElementById('date-error').style.display = 'block';
                isValid = false;
            } else {
                const selectedDate = new Date(formData.serviceDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    document.getElementById('date-error').textContent = 'Выберите дату в будущем';
                    document.getElementById('date-error').style.display = 'block';
                    isValid = false;
                }
            }

            debugLog(`Валидация ${isValid ? 'пройдена' : 'не пройдена'}`);
            return isValid;
        }

        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Оформление заявки';
            document.getElementById('user-container').innerHTML = `
                <div class="alert alert-warning">
                    Для оформления заявки откройте приложение в Telegram
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>

</html>