<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Оформление заявки</div>
        <div id="user-container"></div>
        
        <div class="form-container" id="form-container">
            <form id="service-form">
                <div class="form-group">
                    <label class="form-label required">Имя и фамилия</label>
                    <input type="text" id="full-name" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Телефон</label>
                    <input type="tel" id="phone" class="form-input" placeholder="+7 (999) 999-99-99" required>
                    <div class="form-error" id="phone-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Email</label>
                    <input type="email" id="email" class="form-input" placeholder="ваш@email.com" required>
                    <div class="form-error" id="email-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Услуги</label>
                    <div class="services-container">
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="Уборка"> Уборка
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="Ремонт"> Ремонт
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="Цветы"> Цветы
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="Церковная служба"> Церковная служба
                        </label>
                    </div>
                    <div class="form-error" id="services-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Город</label>
                    <input type="text" id="city" class="form-input" required>
                    <div class="form-error" id="city-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Желаемая дата услуги</label>
                    <input type="date" id="service-date" class="form-input" required>
                    <div class="form-error" id="date-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Кладбище</label>
                    <input type="text" id="cemetery" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Участок</label>
                    <input type="text" id="plot" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ряд</label>
                    <input type="text" id="row" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Номер участка</label>
                    <input type="text" id="plot-number" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Дополнительная информация</label>
                    <textarea id="additional-info" class="form-textarea" rows="3"></textarea>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Telegram API функции
        function initTelegramApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                return null;
            }
            
            const tg = Telegram.WebApp;
            
            try {
                tg.ready();
                if (tg.isExpanded !== true) {
                    tg.expand();
                }
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) {
                    tg.setHeaderColor('#6a11cb');
                }
                return tg;
            } catch (e) {
                console.error('Telegram init error:', e);
                return null;
            }
        }

        function getUserData() {
            try {
                if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                    return Telegram.WebApp.initDataUnsafe?.user || null;
                }
            } catch (e) {
                console.error('Error getting user data:', e);
            }
            return null;
        }

        const MainButton = {
            show: function(text, color = '#6a11cb', textColor = '#ffffff') {
                try {
                    if (typeof Telegram !== 'undefined' && Telegram.WebApp?.MainButton) {
                        const mainButton = Telegram.WebApp.MainButton;
                        mainButton.setText(text);
                        mainButton.color = color;
                        mainButton.textColor = textColor;
                        mainButton.show();
                        return true;
                    }
                } catch (e) {
                    console.error('Error showing main button:', e);
                }
                return false;
            },
            hide: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.hide();
                        return true;
                    }
                } catch (e) {
                    console.error('Error hiding main button:', e);
                }
                return false;
            },
            onClick: function(handler) {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.onClick(handler);
                        return true;
                    }
                } catch (e) {
                    console.error('Error setting main button click:', e);
                }
                return false;
            },
            enable: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.enable();
                        return true;
                    }
                } catch (e) {
                    console.error('Error enabling main button:', e);
                }
                return false;
            },
            disable: function() {
                try {
                    if (Telegram.WebApp?.MainButton) {
                        Telegram.WebApp.MainButton.disable();
                        return true;
                    }
                } catch (e) {
                    console.error('Error disabling main button:', e);
                }
                return false;
            },
            showProgress: function() {
                try {
                    const tg = Telegram.WebApp;
                    if (tg?.MainButton) {
                        tg.MainButton.setText("Отправка...");
                        tg.MainButton.disable();
                        if (tg.showProgress) {
                            tg.showProgress();
                        }
                        return true;
                    }
                } catch (e) {
                    console.error('Error showing progress:', e);
                }
                return false;
            },
            hideProgress: function() {
                try {
                    const tg = Telegram.WebApp;
                    if (tg?.MainButton) {
                        tg.MainButton.setText("Отправить заявку");
                        tg.MainButton.enable();
                        if (tg.hideProgress) {
                            tg.hideProgress();
                        }
                        return true;
                    }
                } catch (e) {
                    console.error('Error hiding progress:', e);
                }
                return false;
            }
        };

        // Bitrix API функции
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

        async function bitrixRequest(method, params = {}) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}${method}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(params)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('Bitrix API error:', error);
                throw error;
            }
        }

        async function createLead(data) {
            const nameParts = data.fullName.split(' ');
            const firstName = nameParts[0] || '';
            const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
            
            const leadData = {
                fields: {
                    TITLE: `Заявка от ${data.fullName}`,
                    NAME: firstName,
                    LAST_NAME: lastName,
                    PHONE: [{ VALUE: data.phone, VALUE_TYPE: 'WORK' }],
                    EMAIL: [{ VALUE: data.email, VALUE_TYPE: 'WORK' }],
                    UF_CRM_1749802456: data.serviceDate,
                    UF_CRM_1749802469: data.city,
                    UF_CRM_1749802574: data.services.join(', '),
                    UF_CRM_1749802612: data.cemetery,
                    UF_CRM_1749802619: data.row,
                    UF_CRM_1749802630: data.plot,
                    UF_CRM_1749802631: data.plotNumber,
                    COMMENTS: `Telegram: @${data.username || 'отсутствует'}\nДополнительно: ${data.additionalInfo || 'не указано'}`
                }
            };
            
            return bitrixRequest('crm.lead.add', leadData);
        }

        // Логика страницы
        let tg = null;
        let user = null;
        let phoneMask = null;
        
        function showError(fieldId, message) {
            const errorEl = document.getElementById(fieldId);
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
        
        function hideError(fieldId) {
            const errorEl = document.getElementById(fieldId);
            errorEl.style.display = 'none';
        }
        
        function hideAllErrors() {
            const errors = document.querySelectorAll('.form-error');
            errors.forEach(el => el.style.display = 'none');
        }
        
        function validateForm() {
            let isValid = true;
            hideAllErrors();
            
            // Phone validation
            const phoneValue = phoneMask.unmaskedValue;
            if (!phoneValue || phoneValue.length !== 11) {
                showError('phone-error', 'Введите корректный номер телефона');
                isValid = false;
            }
            
            // Email validation
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('email-error', 'Введите корректный email');
                isValid = false;
            }
            
            // Services validation
            const services = document.querySelectorAll('input[name="services"]:checked');
            if (services.length === 0) {
                showError('services-error', 'Выберите хотя бы одну услугу');
                isValid = false;
            }
            
            // City validation
            const city = document.getElementById('city').value.trim();
            if (!city) {
                showError('city-error', 'Введите город');
                isValid = false;
            }
            
            // Date validation
            const serviceDate = document.getElementById('service-date').value;
            if (!serviceDate) {
                showError('date-error', 'Выберите дату');
                isValid = false;
            } else {
                const selectedDate = new Date(serviceDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    showError('date-error', 'Выберите дату в будущем');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        async function submitForm() {
            if (!validateForm()) return;
            
            const formData = {
                fullName: document.getElementById('full-name').value,
                phone: phoneMask.unmaskedValue,
                email: document.getElementById('email').value,
                services: Array.from(document.querySelectorAll('input[name="services"]:checked'))
                    .map(checkbox => checkbox.value),
                city: document.getElementById('city').value.trim(),
                serviceDate: document.getElementById('service-date').value,
                cemetery: document.getElementById('cemetery').value.trim(),
                plot: document.getElementById('plot').value.trim(),
                row: document.getElementById('row').value.trim(),
                plotNumber: document.getElementById('plot-number').value.trim(),
                additionalInfo: document.getElementById('additional-info').value.trim(),
                username: user?.username || null
            };
            
            MainButton.showProgress();
            
            try {
                const response = await createLead(formData);
                
                if (response && response.result) {
                    localStorage.setItem('clientEmail', formData.email);
                    window.location.href = 'my-services.php';
                } else {
                    tg.showPopup({
                        title: 'Ошибка',
                        message: 'Не удалось отправить заявку. Попробуйте позже.',
                        buttons: [{ id: 'ok', type: 'ok' }]
                    });
                }
            } catch (error) {
                console.error('Ошибка при отправке:', error);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла ошибка при отправке данных.',
                    buttons: [{ id: 'ok', type: 'ok' }]
                });
            } finally {
                MainButton.hideProgress();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Оформление заявки';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для оформления заявки откройте приложение в Telegram
                </div>
            `;
            document.getElementById('form-container').style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            tg = initTelegramApp();
            
            // Получаем данные пользователя из sessionStorage
            const storedUser = sessionStorage.getItem('telegramUser');
            if (storedUser) {
                user = JSON.parse(storedUser);
            } else if (tg) {
                // Если нет в sessionStorage, пробуем получить из Telegram API
                user = tg.initDataUnsafe?.user || null;
            }
            
            if (!user) {
                showFallbackView();
                return;
            }
            
            const fullName = [user.first_name, user.last_name].filter(Boolean).join(' ') || 'Клиент';
            document.getElementById('full-name').value = fullName;
            
            // Рендерим информацию о пользователе
            document.getElementById('greeting').textContent = 'Оформление заявки';
            
            // Исправлено: добавлен crossorigin="anonymous" для корректной загрузки аватара
            document.getElementById('user-container').innerHTML = `
                <div class="avatar">
                    ${user.photo_url ? 
                        `<img src="${user.photo_url}" alt="${fullName}" crossorigin="anonymous">` : 
                        `<div class="avatar-letter">${user.first_name?.charAt(0) || 'К'}</div>`
                    }
                </div>
                <div class="user-name">${fullName}</div>
            `;
            
            MainButton.show("Отправить заявку");
            MainButton.onClick(submitForm);
            
            phoneMask = new IMask(document.getElementById('phone'), {
                mask: '+{7} (000) 000-00-00'
            });
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch(`https://geocode.xyz/${position.coords.latitude},${position.coords.longitude}?json=1`)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.city) {
                                document.getElementById('city').value = data.city;
                            }
                        });
                }, function() {});
            }
        });
    </script>
</body>
</html>