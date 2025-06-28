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

    <script src="../js/telegram-api.js"></script>
    <script src="../js/bitrix-integration.js"></script>
    <script>
        let tg = null;
        let user = null;

        // Основная функция инициализации
        async function initApp() {
            // Проверка доступности Telegram WebApp API
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const telegramApp = Telegram.WebApp;
            tg = telegramApp;
            
            try {
                // Пытаемся получить данные пользователя из sessionStorage
                const storedUser = sessionStorage.getItem('telegramUser');
                if (storedUser) {
                    user = JSON.parse(storedUser);
                } else {
                    // Если в sessionStorage нет, получаем из Telegram API
                    user = telegramApp.initDataUnsafe?.user || {};
                }
                
                // Отображаем информацию о пользователе
                document.getElementById('greeting').textContent = 'Оформление заявки';
                
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                
                // Исправлено: добавлен crossorigin="anonymous" для корректной загрузки аватара
                document.getElementById('user-container').innerHTML = `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${fullName}" crossorigin="anonymous">` : 
                            `<div>${firstName.charAt(0) || 'К'}</div>`
                        }
                    </div>
                    <div class="user-name">${fullName || 'Клиент'}</div>
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
                username: user?.username || null
            };
            
            // Валидация
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                // Показываем индикатор загрузки
                if (tg.showProgress) tg.showProgress();
                
                // Отправляем данные в Bitrix24
                const response = await createServiceRequest(formData);
                
                if (response.ok) {
                    // Сохраняем email для последующего поиска заказов
                    localStorage.setItem('clientEmail', formData.email);
                    
                    // Переходим на страницу "Мои услуги"
                    window.location.href = 'my-services.php';
                } else {
                    console.error('Ошибка при создании заявки в Bitrix24');
                    tg.showPopup({
                        title: 'Ошибка',
                        message: 'Не удалось отправить заявку. Попробуйте позже.',
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
            const phoneRegex = /^(\+7|8)[\d\s\-()]{10,15}$/;
            if (!phoneRegex.test(formData.phone)) {
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
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Оформление заявки';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для оформления заявки откройте приложение в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>