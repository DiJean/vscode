<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация исполнителя</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
    <link rel="stylesheet" href="/webapp/css/performer-form.css">
</head>
<body class="performer-form">
    <div class="container">
        <div class="greeting" id="greeting">Регистрация исполнителя</div>
        <div id="user-container"></div>
        
        <div class="form-container">
            <form id="performer-form">
                <div class="form-group">
                    <label class="form-label required">Имя</label>
                    <input type="text" id="first-name" class="form-input" required>
                    <div class="form-error" id="first-name-error">Введите имя</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Фамилия</label>
                    <input type="text" id="last-name" class="form-input" required>
                    <div class="form-error" id="last-name-error">Введите фамилию</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Отчество</label>
                    <input type="text" id="second-name" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Телефон</label>
                    <input type="tel" id="phone" class="form-input" placeholder="+7 (999) 999-99-99" required>
                    <div class="form-error" id="phone-error">Введите корректный номер телефона</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Email</label>
                    <input type="email" id="email" class="form-input" placeholder="ваш@email.com" required>
                    <div class="form-error" id="email-error">Введите корректный email</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Город</label>
                    <input type="text" id="city" class="form-input" required>
                    <div class="form-error" id="city-error">Введите город</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Местоположение</label>
                    <button type="button" id="get-location-btn" class="location-btn">
                        Получить мои координаты
                    </button>
                    <div class="form-error" id="location-error">Не удалось получить координаты</div>
                    
                    <div class="coords-container">
                        <div class="coord-input">Широта: <span id="latitude-display">не определено</span></div>
                        <div class="coord-input">Долгота: <span id="longitude-display">не определено</span></div>
                    </div>
                    <input type="hidden" id="latitude">
                    <input type="hidden" id="longitude">
                </div>
            </form>
        </div>
        
        <!-- Блок для отладки -->
        <div id="debug-info"></div>
    </div>

    <script>
        // Временная версия без модулей для диагностики
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
        
        // Основные переменные
        let tg = null;
        let user = null;
        let phoneMask = null;
        
        // Отладочная функция
        function debugLog(message) {
            console.log(message);
            const debugInfo = document.getElementById('debug-info');
            if (debugInfo) {
                debugInfo.innerHTML += `<div>${message}</div>`;
            }
        }
        
        // Основная функция инициализации
        async function initApp() {
            debugLog('Начало инициализации приложения');
            
            // Проверка Telegram WebApp API
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                debugLog('Telegram WebApp API недоступно');
                showFallbackView();
                return;
            }
            
            tg = Telegram.WebApp;
            
            try {
                debugLog('Инициализация Telegram WebApp');
                tg.ready();
                
                // Получаем данные пользователя
                user = tg.initDataUnsafe?.user || {};
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                
                debugLog(`Пользователь: ${firstName} ${lastName}`);
                
                // Отображаем информацию о пользователе
                const userContainer = document.getElementById('user-container');
                if (userContainer) {
                    userContainer.innerHTML = `
                        <div class="avatar">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${firstName} ${lastName}" crossorigin="anonymous">` : 
                                `<div>${firstName.charAt(0) || 'И'}</div>`
                            }
                        </div>
                        <div class="user-name">${firstName} ${lastName}</div>
                    `;
                }
                
                // Предзаполняем имя и фамилию
                if (firstName) {
                    const firstNameInput = document.getElementById('first-name');
                    if (firstNameInput) firstNameInput.value = firstName;
                }
                if (lastName) {
                    const lastNameInput = document.getElementById('last-name');
                    if (lastNameInput) lastNameInput.value = lastName;
                }
                
                // Инициализация маски телефона
                const phoneInput = document.getElementById('phone');
                if (phoneInput) {
                    debugLog('Инициализация маски телефона');
                    phoneMask = new IMask(phoneInput, { 
                        mask: '+{7} (000) 000-00-00'
                    });
                    
                    // Автозаполнение +7
                    phoneInput.addEventListener('focus', function() {
                        if (!this.value.trim()) {
                            phoneMask.unmaskedValue = '7';
                            phoneMask.updateValue();
                        }
                    });
                }
                
                // Обработчик для кнопки получения координат
                const locationBtn = document.getElementById('get-location-btn');
                if (locationBtn) {
                    locationBtn.addEventListener('click', getLocation);
                }
                
                // Настраиваем кнопку отправки
                if (tg.MainButton) {
                    tg.MainButton.setText("Зарегистрироваться");
                    tg.MainButton.onClick(submitForm);
                    tg.MainButton.show();
                    debugLog('Кнопка Telegram инициализирована');
                }
                
                debugLog('Инициализация завершена успешно');
                
            } catch (e) {
                debugLog(`Ошибка инициализации: ${e.message}`);
                console.error(e);
                showFallbackView();
            }
        }
        
        // Получение геолокации
        function getLocation() {
            debugLog('Запрос геолокации...');
            
            if (!navigator.geolocation) {
                showLocationError("Геолокация не поддерживается вашим браузером");
                return;
            }
            
            const btn = document.getElementById('get-location-btn');
            if (!btn) return;
            
            btn.innerHTML = 'Определение местоположения...';
            btn.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    debugLog(`Координаты получены: ${lat}, ${lng}`);
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    const latDisplay = document.getElementById('latitude-display');
                    const lngDisplay = document.getElementById('longitude-display');
                    
                    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
                    
                    const locationError = document.getElementById('location-error');
                    if (locationError) locationError.style.display = 'none';
                    
                    btn.innerHTML = 'Координаты получены!';
                    btn.disabled = false;
                },
                error => {
                    let message = "Не удалось получить координаты";
                    switch(error.code) {
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
                },
                { 
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
        
        // Отправка формы
        async function submitForm() {
            debugLog('Начало отправки формы');
            
            const formData = {
                firstName: document.getElementById('first-name')?.value || '',
                lastName: document.getElementById('last-name')?.value || '',
                secondName: document.getElementById('second-name')?.value || '',
                phone: phoneMask?.unmaskedValue || '',
                email: document.getElementById('email')?.value || '',
                city: document.getElementById('city')?.value || '',
                latitude: document.getElementById('latitude')?.value || '',
                longitude: document.getElementById('longitude')?.value || '',
                tgUserId: user?.id || null
            };
            
            debugLog('Данные формы: ' + JSON.stringify(formData));
            
            if (!validateForm(formData)) {
                debugLog('Валидация формы не пройдена');
                return;
            }
            
            try {
                debugLog('Попытка сохранения исполнителя');
                if (tg.showProgress) tg.showProgress();
                
                // Сохраняем исполнителя в Bitrix24
                const result = await savePerformer(formData);
                
                if (result.success) {
                    debugLog('Успешная регистрация, переход в дашборд');
                    window.location.href = 'dashboard.php';
                } else {
                    debugLog(`Ошибка регистрации: ${result.errorMessage}`);
                    tg.showPopup({
                        title: 'Ошибка регистрации',
                        message: result.errorMessage || 'Не удалось зарегистрироваться',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                }
            } catch (error) {
                debugLog(`Непредвиденная ошибка: ${error.message}`);
                console.error(error);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла непредвиденная ошибка',
                    buttons: [{id: 'ok', type: 'ok'}]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
            }
        }
        
        // Валидация формы
        function validateForm(formData) {
            debugLog('Начало валидации формы');
            
            let isValid = true;
            
            // Скрываем все ошибки
            document.querySelectorAll('.form-error').forEach(el => {
                el.style.display = 'none';
            });
            
            // Проверка обязательных полей
            if (!formData.firstName.trim()) {
                const errorEl = document.getElementById('first-name-error');
                if (errorEl) {
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            
            if (!formData.lastName.trim()) {
                const errorEl = document.getElementById('last-name-error');
                if (errorEl) {
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            
            const phoneValue = formData.phone;
            if (!phoneValue || phoneValue.length !== 11) {
                const errorEl = document.getElementById('phone-error');
                if (errorEl) {
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                const errorEl = document.getElementById('email-error');
                if (errorEl) {
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            
            if (!formData.city.trim()) {
                const errorEl = document.getElementById('city-error');
                if (errorEl) {
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            
            if (!formData.latitude || !formData.longitude) {
                showLocationError("Получите координаты для продолжения");
                isValid = false;
            }
            
            debugLog(`Валидация ${isValid ? 'пройдена' : 'не пройдена'}`);
            return isValid;
        }
        
        // Сохранение исполнителя в Bitrix24
        async function savePerformer(data) {
            debugLog(`Сохранение исполнителя: ${JSON.stringify(data)}`);
            
            try {
                const contactData = {
                    fields: {
                        NAME: data.firstName,
                        LAST_NAME: data.lastName,
                        SECOND_NAME: data.secondName || '',
                        PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                        EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                        UF_CRM_685D2956061DB: data.city,
                        UF_CRM_1751129816: data.latitude,
                        UF_CRM_1751129854: data.longitude,
                        UF_CRM_1751128872: String(data.tgUserId),
                        TYPE_ID: 'EMPLOYEE'
                    }
                };
                
                debugLog(`Данные для Bitrix24: ${JSON.stringify(contactData)}`);
                
                // Здесь будет реальный запрос к Bitrix24
                // Временно возвращаем успешный результат
                return { success: true, contactId: 12345 };
                
            } catch (error) {
                debugLog(`Ошибка сохранения: ${error.message}`);
                return {
                    success: false,
                    errorMessage: `Сетевая ошибка: ${error.message}`
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
                    <div class="welcome-text">
                        Для регистрации откройте приложение в Telegram
                    </div>
                `;
            }
        }
        
        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            debugLog('Страница загружена');
            initApp();
        });
    </script>
</body>
</html>