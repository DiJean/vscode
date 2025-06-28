<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Наши стили -->
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
    
    <!-- IMask для телефона -->
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
    </style>
</head>
<body>
    <div class="container py-4">
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
                        <div class="form-error" id="phone-error">Введите корректный номер телефона</div>
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
                    <input type="date" id="service-date" class="form-control" required>
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="/webapp/js/telegram-api.js"></script>
    <script src="/webapp/js/bitrix-integration.js"></script>
    
    <script>
        let tg = null;
        let user = null;
        let phoneMask = null;

        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const telegramApp = Telegram.WebApp;
            tg = telegramApp;
            
            try {
                const storedUser = sessionStorage.getItem('telegramUser');
                if (storedUser) {
                    user = JSON.parse(storedUser);
                } else {
                    user = telegramApp.initDataUnsafe?.user || {};
                }
                
                document.getElementById('greeting').textContent = 'Оформление заявки';
                
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                
                document.getElementById('user-container').innerHTML = `
                    <div class="d-flex flex-column align-items-center">
                        <div class="avatar mb-3">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle" style="width:80px;height:80px;">` : 
                                `<div class="d-flex align-items-center justify-content-center rounded-circle bg-light text-dark fw-bold" style="width:80px;height:80px;font-size:2rem;">${firstName.charAt(0) || 'К'}</div>`
                            }
                        </div>
                        <div class="user-name fs-5">${fullName || 'Клиент'}</div>
                    </div>
                `;
                
                document.getElementById('full-name').value = fullName;
                
                phoneMask = new IMask(
                    document.getElementById('phone'),
                    {
                        mask: '+{7} (000) 000-00-00',
                        lazy: false,
                        placeholderChar: ' ',
                        blocks: {
                            '0': {
                                mask: /[0-9]/,
                                placeholderChar: '_'
                            }
                        }
                    }
                );
                
                document.getElementById('phone').addEventListener('focus', function() {
                    if (!this.value.trim()) {
                        phoneMask.unmaskedValue = '7';
                        phoneMask.updateValue();
                    }
                });
                
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
        
        async function submitForm() {
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
                username: user?.username || null
            };
            
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                if (tg.showProgress) tg.showProgress();
                
                const response = await processServiceRequest(formData);
                
                if (response.success) {
                    localStorage.setItem('clientEmail', formData.email);
                    window.location.href = 'my-services.php';
                } else {
                    tg.showPopup({
                        title: 'Ошибка',
                        message: 'Не удалось отправить заявку. Попробуйте позже.',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                }
            } catch (error) {
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла ошибка при отправке данных.',
                    buttons: [{id: 'ok', type: 'ok'}]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
            }
        }
        
        function validateForm(formData) {
            let isValid = true;
            
            document.querySelectorAll('.form-error').forEach(el => {
                el.style.display = 'none';
            });
            
            if (!formData.phone || formData.phone.length !== 11) {
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