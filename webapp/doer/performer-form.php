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
    <style>
        /* Важные стили для отображения формы */
        .form-container {
            display: block !important;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .form-container.visible {
            opacity: 1;
        }
        
        .location-btn {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 5px;
        }
        
        .location-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.7);
        }
        
        .coords-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .coord-input {
            flex: 1;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        /* Индикатор загрузки */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .debug-panel {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            font-size: 0.9rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Регистрация исполнителя</div>
        <div id="user-container"></div>
        
        <div class="form-container" id="form-container">
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
        
        <!-- Панель диагностики -->
        <div class="debug-panel" id="debug-panel">
            <h3>Диагностическая информация</h3>
            <pre id="debug-data"></pre>
        </div>
    </div>

    <script type="module">
        import { BITRIX_WEBHOOK, findPerformerByTgId } from '../js/bitrix-integration.js';

        let tg = null;
        let user = null;
        let phoneMask = null;

        // Основная функция инициализации
        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            tg = Telegram.WebApp;
            
            try {
                tg.ready();
                
                // Получаем данные пользователя
                user = tg.initDataUnsafe?.user || {};
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                
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
                    phoneMask = IMask(phoneInput, { 
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
                
                // Показываем форму с анимацией
                const formContainer = document.getElementById('form-container');
                if (formContainer) {
                    setTimeout(() => {
                        formContainer.classList.add('visible');
                    }, 300);
                }
                
                // Настраиваем кнопку отправки
                if (tg.MainButton) {
                    tg.MainButton.setText("Зарегистрироваться");
                    tg.MainButton.onClick(submitForm);
                    tg.MainButton.show();
                }
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        // Получение геолокации
        function getLocation() {
            if (!navigator.geolocation) {
                showLocationError("Геолокация не поддерживается вашим браузером");
                return;
            }
            
            const btn = document.getElementById('get-location-btn');
            if (!btn) return;
            
            btn.innerHTML = '<span class="loader"></span> Определение местоположения...';
            btn.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    const latDisplay = document.getElementById('latitude-display');
                    const lngDisplay = document.getElementById('longitude-display');
                    
                    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
                    
                    const locationError = document.getElementById('location-error');
                    if (locationError) locationError.style.display = 'none';
                    
                    btn.innerHTML = 'Координаты получены!';
                    
                    setTimeout(() => {
                        btn.innerHTML = 'Получить мои координаты';
                        btn.disabled = false;
                    }, 2000);
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
            
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                if (tg.showProgress) tg.showProgress();
                
                // Сохраняем исполнителя в Bitrix24
                const result = await savePerformer(formData);
                
                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    showDebugPanel(result);
                    tg.showPopup({
                        title: 'Ошибка регистрации',
                        message: result.errorMessage || 'Не удалось зарегистрироваться',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                }
            } catch (error) {
                console.error('Ошибка:', error);
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
            
            return isValid;
        }
        
        // Сохранение исполнителя в Bitrix24
        async function savePerformer(data) {
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
                
                console.log("Saving performer data:", contactData);
                
                // Проверяем, есть ли уже контакт (используем импортированную функцию)
                const existingContact = await findPerformerByTgId(data.tgUserId);
                let response, result;
                
                if (existingContact) {
                    // Обновляем существующий контакт
                    response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            id: existingContact.ID,
                            fields: contactData.fields
                        })
                    });
                    result = await response.json();
                    
                    if (result.result) {
                        return { success: true, contactId: existingContact.ID };
                    } else {
                        return {
                            success: false,
                            errorMessage: result.error_description || "Ошибка обновления контакта",
                            response: result
                        };
                    }
                } else {
                    // Создаем новый контакт
                    response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(contactData)
                    });
                    result = await response.json();
                    
                    if (result.result) {
                        return { success: true, contactId: result.result };
                    } else {
                        return {
                            success: false,
                            errorMessage: result.error_description || "Ошибка создания контакта",
                            response: result
                        };
                    }
                }
            } catch (error) {
                console.error('Ошибка сохранения исполнителя:', error);
                return {
                    success: false,
                    errorMessage: `Сетевая ошибка: ${error.message}`
                };
            }
        }
        
        // Показать панель диагностики
        function showDebugPanel(data) {
            const panel = document.getElementById('debug-panel');
            const content = document.getElementById('debug-data');
            
            if (panel && content) {
                panel.style.display = 'block';
                content.textContent = JSON.stringify(data, null, 2);
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
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>