<?php
header("HTTP/1.1 301 Moved Permanently");
header("Location: /webapp/client/services.php");
exit();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Оформление заявки</div>
        <div id="user-container"></div>
        
        <div class="form-container" id="form-container" style="display: none;">
            <form id="service-form">
                <div class="form-group">
                    <label class="form-label required">Имя и фамилия</label>
                    <input type="text" id="full-name" class="form-input" readonly>
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
                    <label class="form-label required">Услуги</label>
                    <div class="services-container">
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="69" data-name="Уход"> Уход
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="71" data-name="Цветы"> Цветы
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="73" data-name="Ремонт"> Ремонт
                        </label>
                        <label class="service-checkbox">
                            <input type="checkbox" name="services" value="75" data-name="Церковная служба"> Церковная служба
                        </label>
                    </div>
                    <div class="form-error" id="services-error">Выберите хотя бы одну услугу</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Город</label>
                    <input type="text" id="city" class="form-input" required>
                    <div class="form-error" id="city-error">Введите город</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Желаемая дата услуги</label>
                    <input type="date" id="service-date" class="form-input" required>
                    <div class="form-error" id="date-error">Выберите дату</div>
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

    <!-- Загрузка скриптов с обработкой ошибок -->
    <script src="/webapp/js/telegram-api.js"></script>
    <script src="/webapp/js/bitrix-integration.js" 
            onload="console.log('Bitrix integration loaded')"
            onerror="console.error('Failed to load Bitrix integration')"></script>
    
    <script>
        let tg = null;
        let user = null;
        let bitrixLoaded = false;

        // Проверка загрузки модуля Bitrix
        function checkBitrixIntegration() {
            if (window.bitrixFunctions && typeof window.bitrixFunctions.createContact === 'function') {
                bitrixLoaded = true;
                return true;
            }
            return false;
        }

        // Основная функция инициализации
        async function initApp() {
            try {
                // Проверка доступности Telegram WebApp API
                if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                    showFallbackView();
                    return;
                }
                
                tg = Telegram.WebApp;
                
                // Получаем данные пользователя
                user = tg.initDataUnsafe?.user || {};
                
                // Отображаем информацию о пользователе
                document.getElementById('greeting').textContent = 'Оформление заявки';
                
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = firstName || lastName ? `${firstName} ${lastName}`.trim() : 'Клиент';
                
                document.getElementById('user-container').innerHTML = `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${fullName}">` : 
                            `<div>${firstName.charAt(0) || 'К'}</div>`
                        }
                    </div>
                    <div class="user-name">${fullName}</div>
                `;
                
                // Предзаполняем имя
                document.getElementById('full-name').value = fullName;
                
                // Показываем форму
                document.getElementById('form-container').style.display = 'block';
                
                // Настраиваем кнопку
                if (tg.MainButton) {
                    tg.MainButton.setText("Отправить заявку");
                    tg.MainButton.onClick(submitForm);
                    tg.MainButton.show();
                }
                
                // Проверяем интеграцию с Bitrix
                bitrixLoaded = checkBitrixIntegration();
                if (!bitrixLoaded) {
                    console.warn('Bitrix integration module not loaded');
                }
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        // Отправка формы
        async function submitForm() {
            // Собираем данные формы
            const formData = {
                firstName: document.getElementById('full-name').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                services: Array.from(document.querySelectorAll('input[name="services"]:checked'))
                    .map(checkbox => checkbox.value),
                city: document.getElementById('city').value,
                serviceDate: document.getElementById('service-date').value,
                cemetery: document.getElementById('cemetery').value,
                plot: document.getElementById('plot').value,
                row: document.getElementById('row').value,
                plotNumber: document.getElementById('plot-number').value,
                additionalInfo: document.getElementById('additional-info').value,
                username: user?.username || 'отсутствует'
            };
            
            // Валидация
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                // Проверка загрузки модуля Bitrix
                if (!bitrixLoaded) {
                    throw new Error('Модуль интеграции с CRM не загружен');
                }
                
                // Показываем индикатор загрузки
                if (tg.showProgress) tg.showProgress();
                
                // 1. Создаем контакт
                const contactResponse = await window.bitrixFunctions.createContact({
                    first_name: formData.firstName.split(' ')[0] || 'Клиент',
                    last_name: formData.firstName.split(' ').slice(1).join(' ') || 'Без фамилии',
                    phone: formData.phone,
                    email: formData.email
                });
                
                // Проверяем результат создания контакта
                if (contactResponse.error || !contactResponse.result) {
                    const errorMsg = contactResponse.error_description || 'Ошибка создания контакта';
                    throw new Error(errorMsg);
                }
                
                // 2. Создаем сделку
                const title = `Заявка от ${formData.firstName}`;
                const dealResponse = await window.bitrixFunctions.createDeal(contactResponse.result, title);
                
                // Проверяем результат создания сделки
                if (dealResponse.error || !dealResponse.result) {
                    const errorMsg = dealResponse.error_description || 'Ошибка создания сделки';
                    throw new Error(errorMsg);
                }
                
                // 3. Обновляем сделку деталями заказа
                const updateResponse = await window.bitrixFunctions.updateDeal(dealResponse.result, formData);
                
                // Проверяем результат обновления сделки
                if (updateResponse.error || !updateResponse.result) {
                    const errorMsg = updateResponse.error_description || 'Ошибка обновления сделки';
                    throw new Error(errorMsg);
                }
                
                // Сохраняем email для последующего поиска заказов
                localStorage.setItem('clientEmail', formData.email);
                
                // Переходим на страницу "Мои услуги"
                window.location.href = 'my-services.php';
                
            } catch (error) {
                console.error('Ошибка при создании заявки:', error);
                
                // Формируем понятное сообщение об ошибке
                let errorMessage = 'Не удалось создать заявку. Попробуйте позже.';
                
                if (error.message.includes('CONTACT_ID')) {
                    errorMessage = 'Ошибка привязки к контакту. Повторите попытку.';
                } else if (error.message.includes('UF_CRM')) {
                    errorMessage = 'Ошибка в данных заявки. Проверьте введенные значения.';
                }
                
                if (tg.showPopup) {
                    tg.showPopup({
                        title: 'Ошибка',
                        message: error.message || errorMessage,
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                } else {
                    alert(error.message || errorMessage);
                }
            } finally {
                // Скрываем индикатор загрузки
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
            
            // Валидация телефона
            const phoneRegex = /^(\+7|8)\d{10}$/;
            if (!phoneRegex.test(formData.phone.replace(/[\s\-()]/g, ''))) {
                document.getElementById('phone-error').style.display = 'block';
                isValid = false;
            }
            
            // Валидация email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }
            
            // Проверка выбора услуг
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
            }
            // Проверка на будущую дату
            else {
                const selectedDate = new Date(formData.serviceDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    document.getElementById('date-error').textContent = 'Выберите дату в будущем';
                    document.getElementById('date-error').style.display = 'block';
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        // Показать запасной вид
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Оформление заявки';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для оформления заявки откройте приложение в Telegram
                </div>
            `;
        }
        
        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>
