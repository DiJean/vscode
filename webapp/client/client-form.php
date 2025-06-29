<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        .service-checkbox {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: all 0.3s;
        }

        .service-checkbox:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .service-checkbox input {
            margin-right: 10px;
        }

        .back-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
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

        .form-input:read-only {
            background: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
        }
    </style>
</head>

<body>
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
                    <input type="date" id="service-date" class="form-control" required min="<?= date('Y-m-d') ?>">
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
    <script src="/webapp/js/bitrix-integration.js"></script>

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

                // Получаем данные пользователя из WebApp
                user = tg.initDataUnsafe?.user || {};

                // Если в WebApp нет данных, пробуем получить из localStorage
                if (!user.id) {
                    const storedUser = localStorage.getItem('telegramUser');
                    if (storedUser) {
                        user = JSON.parse(storedUser);
                        debugLog('Пользователь восстановлен из localStorage');
                    }
                } else {
                    // Сохраняем пользователя в localStorage
                    localStorage.setItem('telegramUser', JSON.stringify(user));
                }

                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim() || 'Клиент';

                debugLog(`Пользователь Telegram: ${fullName} (ID: ${user.id || 'нет'})`);

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

                // Установка минимальной даты
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('service-date').min = today;

                if (tg.MainButton) {
                    tg.MainButton.setText("Отправить заявку");
                    tg.MainButton.onClick(submitForm);
                    tg.MainButton.show();
                }

            } catch (e) {
                debugLog(`Ошибка инициализации: ${e.message}`);
                showFallbackView();
            }
        }

        async function submitForm() {
            debugLog('=== ОТПРАВКА ФОРМЫ ===');

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

                if (tg.MainButton) {
                    tg.MainButton.setText("Отправка...");
                    tg.MainButton.disable();
                }
                if (tg.showProgress) tg.showProgress();

                const response = await processServiceRequest(formData);
                debugLog('Ответ от Bitrix24: ' + JSON.stringify(response));

                if (response.success) {
                    localStorage.setItem('userEmail', formData.email);

                    tg.showPopup({
                        title: 'Успешно!',
                        message: 'Заявка успешно создана',
                        buttons: [{
                            id: 'ok',
                            type: 'ok'
                        }]
                    });

                    setTimeout(() => {
                        window.location.href = 'my-services.php';
                    }, 1500);
                } else {
                    const errorMsg = response.error || 'Не удалось отправить заявку. Попробуйте позже.';
                    debugLog(`Ошибка создания заявки: ${errorMsg}`);

                    tg.showPopup({
                        title: 'Ошибка',
                        message: errorMsg,
                        buttons: [{
                            id: 'ok',
                            type: 'ok'
                        }]
                    });
                }
            } catch (error) {
                debugLog(`Критическая ошибка: ${error.message}`);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла ошибка при отправке данных: ' + error.message,
                    buttons: [{
                        id: 'ok',
                        type: 'ok'
                    }]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
                if (tg.MainButton) {
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

            // Проверка телефона (10 цифр после +7)
            if (!formData.phone || formData.phone.length !== 11 || formData.phone[0] !== '7') {
                document.getElementById('phone-error').textContent = 'Введите 10 цифр номера телефона после +7';
                document.getElementById('phone-error').style.display = 'block';
                isValid = false;
            }

            // Проверка email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }

            // Проверка услуг
            if (formData.services.length === 0) {
                document.getElementById('services-error').style.display = 'block';
                isValid = false;
            }

            // Проверка города
            if (!formData.city.trim()) {
                document.getElementById('city-error').style.display = 'block';
                isValid = false;
            }

            // Проверка даты
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