<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать запрос</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <style>
        .form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px;
            border-radius: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 1rem;
        }
        
        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff2e63 0%, #ff6b6b 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">Новый запрос</div>
        
        <div class="form-container">
            <form id="request-form">
                <!-- Поля формы -->
                <div class="form-group">
                    <label for="fullName">Имя и фамилия</label>
                    <input type="text" id="fullName" name="fullName" required placeholder="Введите ваше полное имя">
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+7 (XXX) XXX-XX-XX">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="example@mail.com">
                </div>
                
                <div class="form-group">
                    <label for="service">Услуга</label>
                    <select id="service" name="service" required>
                        <option value="" disabled selected>Выберите услугу</option>
                        <option value="Уход за могилой">Уход за могилой</option>
                        <option value="Установка памятника">Установка памятника</option>
                        <option value="Доставка цветов">Доставка цветов</option>
                        <option value="Благоустройство участка">Благоустройство участка</option>
                        <option value="Прочие услуги">Прочие услуги</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serviceDate">Желаемая дата услуги</label>
                    <input type="date" id="serviceDate" name="serviceDate" required>
                </div>
                
                <div class="form-group">
                    <label for="city">Город</label>
                    <input type="text" id="city" name="city" required placeholder="Город оказания услуги">
                </div>
                
                <div class="form-group">
                    <label for="cemetery">Кладбище</label>
                    <input type="text" id="cemetery" name="cemetery" required placeholder="Название кладбища">
                </div>
                
                <div class="form-group">
                    <label for="sector">Сектор</label>
                    <input type="text" id="sector" name="sector" required placeholder="Номер сектора">
                </div>
                
                <div class="form-group">
                    <label for="row">Ряд</label>
                    <input type="text" id="row" name="row" required placeholder="Номер ряда">
                </div>
                
                <div class="form-group">
                    <label for="plot">Участок</label>
                    <input type="text" id="plot" name="plot" required placeholder="Номер участка">
                </div>
                
                <div class="form-group">
                    <label for="comments">Дополнительная информация</label>
                    <textarea id="comments" name="comments" rows="3" placeholder="Особые пожелания или комментарии"></textarea>
                </div>
                
                <button type="submit">Отправить запрос</button>
            </form>
        </div>
    </div>

    <script src="/webapp/js/bitrix-integration.js"></script>
    <script>
        // Константа с вебхуком Bitrix24
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

        // Функция для отправки данных в Bitrix24
        async function createServiceRequest(data) {
            // Разделяем полное имя на компоненты
            const nameParts = (data.fullName || '').split(' ');
            const firstName = nameParts[0] || '';
            const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
            
            // Формируем данные для Bitrix24
            const requestData = {
                fields: {
                    // Основные поля
                    NAME: firstName,
                    LAST_NAME: lastName,
                    PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                    EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                    
                    // Пользовательские поля
                    UF_CRM_685D295664A8A: data.serviceDate, // Желаемая дата услуги
                    UF_CRM_685D2956BF4C8: data.city,        // Город
                    UF_CRM_685D2956C64E0: data.service,     // Услуга
                    UF_CRM_685D2956D0916: data.cemetery,    // Кладбище
                    UF_CRM_1751022940: data.sector,         // Сектор
                    UF_CRM_685D2956D7C70: data.row,         // Ряд
                    UF_CRM_685D2956DF40F: data.plot,        // Участок
                    
                    // Комментарий
                    COMMENTS: data.comments || 'Дополнительная информация не указана'
                }
            };
            
            return fetch(`${BITRIX_WEBHOOK}crm.lead.add`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(requestData),
            });
        }

        // Основная логика страницы
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('request-form');
            const tg = window.Telegram && Telegram.WebApp;
            
            // Если пользователь авторизован в Telegram
            if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const user = tg.initDataUnsafe.user;
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                
                // Автозаполнение имени, если доступно
                if (firstName || lastName) {
                    document.getElementById('fullName').value = `${firstName} ${lastName}`.trim();
                }
            }
            
            // Установка текущей даты как минимальной для выбора
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('serviceDate').min = today;
            
            // Обработка отправки формы
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Собираем данные формы
                const formData = {
                    fullName: document.getElementById('fullName').value,
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    service: document.getElementById('service').value,
                    serviceDate: document.getElementById('serviceDate').value,
                    city: document.getElementById('city').value,
                    cemetery: document.getElementById('cemetery').value,
                    sector: document.getElementById('sector').value,
                    row: document.getElementById('row').value,
                    plot: document.getElementById('plot').value,
                    comments: document.getElementById('comments').value
                };
                
                try {
                    // Отправляем данные в Bitrix24
                    const response = await createServiceRequest(formData);
                    const result = await response.json();
                    
                    if (result.result) {
                        // Сохраняем email для последующего использования
                        localStorage.setItem('userEmail', formData.email);
                        
                        // Показываем уведомление
                        if (tg && tg.showAlert) {
                            tg.showAlert('✅ Запрос успешно создан!');
                        } else {
                            alert('✅ Запрос успешно создан!');
                        }
                        
                        // Возвращаемся к списку сервисов
                        setTimeout(() => {
                            window.location.href = '/webapp/client/services.php';
                        }, 1500);
                    } else {
                        console.error('Bitrix24 error:', result);
                        const errorMsg = `❌ Ошибка: ${result.error_description || 'Неизвестная ошибка'}`;
                        
                        if (tg && tg.showAlert) {
                            tg.showAlert(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (error) {
                    console.error('Request failed:', error);
                    const errorMsg = '�� Ошибка сети или сервера. Попробуйте позже.';
                    
                    if (tg && tg.showAlert) {
                        tg.showAlert(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        });
    </script>
</body>
</html>
