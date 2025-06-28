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
                const locationBtn = document.getElementB