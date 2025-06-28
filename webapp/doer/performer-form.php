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
    <!-- Подключаем библиотеку IMask для телефона -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Регистрация исполнителя</div>
        <div id="user-container"></div>
        
        <div class="form-container" id="form-container" style="display: none;">
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
                    <button type="button" id="get-location-btn" class="form-input" style="text-align: center; background: rgba(106, 17, 203, 0.3); cursor: pointer;">
                        Получить мои координаты
                    </button>
                    <div class="form-error" id="location-error">Не удалось получить координаты</div>
                    <input type="hidden" id="latitude">
                    <input type="hidden" id="longitude">
                </div>
            </form>
        </div>
    </div>

    <script src="../js/telegram-api.js"></script>
    <script src="../js/bitrix-integration.js"></script>
    <script>
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
                document.getElementById('user-container').innerHTML = `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${firstName} ${lastName}" crossorigin="anonymous">` : 
                            `<div>${firstName.charAt(0) || 'И'}</div>`
                        }
                    </div>
                    <div class="user-name">${firstName} ${lastName}</div>
                `;
                
                // Предзаполняем имя и фамилию
                if (firstName) document.getElementById('first-name').value = firstName;
                if (lastName) document.getElementById('last-name').value = lastName;
                
                // Инициализация маски телефона
                phoneMask = IMask(
                    document.getElementById('phone'),
                    { mask: '+{7} (000) 000-00-00' }
                );
                
                // Обработчик для кнопки получения координат
                document.getElementById('get-location-btn').addEventListener('click', getLocation);
                
                // Показываем форму
                document.getElementById('form-container').style.display = 'block';
                
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
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    document.getElementById('location-error').style.display = 'none';
                    tg.showPopup({
                        title: 'Успешно',
                        message: `Координаты получены: ${lat.toFixed(6)}, ${lng.toFixed(6)}`,
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
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
                }
            );
        }
        
        function showLocationError(message) {
            const errorEl = document.getElementById('location-error');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
        
        // Отправка формы
        async function submitForm() {
            const formData = {
                firstName: document.getElementById('first-name').value,
                lastName: document.getElementById('last-name').value,
                secondName: document.getElementById('second-name').value,
                phone: phoneMask.unmaskedValue,
                email: document.getElementById('email').value,
                city: document.getElementById('city').value,
                latitude: document.getElementById('latitude').value,
                longitude: document.getElementById('longitude').value,
                tgUserId: user.id
            };
            
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                if (tg.showProgress) tg.showProgress();
                
                // Сохраняем исполнителя в Bitrix24
                const contactId = await savePerformer(formData);
                
                if (contactId) {
                    // Сохраняем ID контакта в sessionStorage
                    sessionStorage.setItem('performerContactId', contactId);
                    
                    // Переходим в дашборд
                    window.location.href = '/webapp/doer/dashboard.php';
                } else {
                    tg.showPopup({
                        title: 'Ошибка',
                        message: 'Не удалось зарегистрироваться. Попробуйте позже.',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                }
            } catch (error) {
                console.error('Ошибка:', error);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла ошибка при отправке данных.',
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
                        UF_CRM_685D2956061DB: data.city, // Город
                        UF_CRM_1751129816: data.latitude, // Широта
                        UF_CRM_1751129854: data.longitude, // Долгота
                        UF_CRM_1751128872: data.tgUserId, // Telegram ID
                        TYPE_ID: 'EMPLOYEE' // Тип контакта - исполнитель
                    }
                };
                
                // Проверяем, есть ли уже контакт
                const existingContact = await findPerformerByTgId(data.tgUserId);
                
                let contactId;
                if (existingContact) {
                    // Обновляем существующий контакт
                    contactId = existingContact.ID;
                    const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            id: contactId,
                            fields: contactData.fields
                        })
                    });
                    const result = await response.json();
                    return result.result ? contactId : null;
                } else {
                    // Создаем новый контакт
                    const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(contactData)
                    });
                    const result = await response.json();
                    return result.result;
                }
                
            } catch (error) {
                console.error('Ошибка сохранения исполнителя:', error);
                return null;
            }
        }
        
        // Поиск исполнителя по Telegram ID
        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filter: {'UF_CRM_1751128872': tgId},
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
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Регистрация исполнителя';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для регистрации откройте приложение в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>