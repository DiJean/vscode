<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор роли</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Привет!</div>
        <div id="user-container"></div>
    </div>
    
    <div class="desktop-warning" id="desktop-warning" style="display: none;">
        ⚠️ Для лучшего опыта используйте это приложение в мобильном клиенте Telegram
    </div>

    <script src="js/telegram-api.js"></script>
    <script src="js/bitrix-integration.js"></script>
    <script>
        // Основная функция инициализации приложения
        function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            const tg = Telegram.WebApp;
            
            try {
                tg.ready();
                
                if (tg.isExpanded !== true && tg.expand) {
                    tg.expand();
                }
                
                tg.backgroundColor = '#6a11cb';
                if (tg.setHeaderColor) {
                    tg.setHeaderColor('#6a11cb');
                }
                
                // Получаем данные пользователя
                let user = null;
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                    user = tg.initDataUnsafe.user;
                }
                
                // Генерировать HTML для пользователя
                let userHtml = '';
                
                if (user) {
                    const firstName = user.first_name || '';
                    const lastName = user.last_name || '';
                    const username = user.username ? `@${user.username}` : 'без username';
                    const fullName = `${firstName} ${lastName}`.trim();
                    
                    const greeting = fullName ? `Привет, ${fullName}!` : 'Привет!';
                    document.getElementById('greeting').textContent = greeting;
                    
                    userHtml += `
                        <div class="avatar">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}">` : 
                                `<div>${firstName.charAt(0) || 'Г'}</div>`
                            }
                        </div>
                        <div class="user-name">${fullName || 'Анонимный пользователь'}</div>
                        <div class="username">${username}</div>
                    `;
                } else {
                    userHtml = `
                        <div class="avatar">Г</div>
                        <div class="user-name">Гость</div>
                    `;
                }
                
                // Добавляем блок выбора роли
                userHtml += `
                    <div class="role-selection">
                        <div class="role-label">Выберите роль:</div>
                        <select class="role-select" id="role">
                            <option value="" disabled selected>Выберите роль...</option>
                            <option value="client">Клиент</option>
                            <option value="performer">Исполнитель</option>
                        </select>
                        <div class="role-error" id="role-error">Выберите роль!</div>
                    </div>
                    <div class="welcome-text">
                        Мы рады видеть вас здесь! <span class="heart">❤️</span>
                    </div>
                `;
                
                document.getElementById('user-container').innerHTML = userHtml;
                
                // Настройка кнопки
                if (tg.MainButton) {
                    tg.MainButton.setText("Продолжить");
                    
                    // Обработчик для кнопки
                    tg.MainButton.onClick(async function() {
                        const role = document.getElementById('role').value;
                        if (!role) {
                            document.getElementById('role-error').style.display = 'block';
                            return;
                        }
                        
                        try {
                            // Для клиента создаем контакт и сделку
                            if (role === 'client') {
                                if (!user) {
                                    throw new Error('Данные пользователя недоступны');
                                }
                                
                                // Показываем индикатор загрузки
                                if (tg.showProgress) tg.showProgress();
                                
                                // Создаем контакт
                                const contactResponse = await createBitrixContact(user);
                                if (!contactResponse.result) {
                                    throw new Error('Ошибка создания контакта');
                                }
                                
                                // Создаем сделку
                                const title = `Заявка от ${user.first_name || 'клиента'}`;
                                const dealResponse = await createBitrixDeal(contactResponse.result, title);
                                if (!dealResponse.result) {
                                    throw new Error('Ошибка создания сделки');
                                }
                                
                                // Сохраняем ID
                                localStorage.setItem('bitrixContactId', contactResponse.result);
                                localStorage.setItem('bitrixDealId', dealResponse.result);
                                
                                // Скрываем индикатор
                                if (tg.hideProgress) tg.hideProgress();
                            }
                            
                            // Сохраняем роль и перенаправляем
                            localStorage.setItem('selectedRole', role);
                            
                            if (role === 'client') {
                                window.location.href = 'client/client-form.php';
                            } else {
                                window.location.href = 'performer/dashboard.php';
                            }
                            
                        } catch (error) {
                            console.error('Ошибка:', error);
                            
                            // Скрываем индикатор
                            if (tg.hideProgress) tg.hideProgress();
                            
                            tg.showPopup({
                                title: 'Ошибка',
                                message: 'Не удалось создать заявку. Попробуйте позже.',
                                buttons: [{id: 'ok', type: 'ok'}]
                            });
                        }
                    });
                    
                    tg.MainButton.show();
                }
                
                // Показываем предупреждение для десктопной версии
                if (tg.isDesktop) {
                    document.getElementById('desktop-warning').style.display = 'block';
                }
                
            } catch (e) {
                console.error('Ошибка инициализации Telegram WebApp:', e);
                showFallbackView();
            }
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Привет, Гость!';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Добро пожаловать в наше приложение!
                </div>
            `;
        }
        
        if (window.Telegram && window.Telegram.WebApp) {
            initApp();
        } else {
            document.addEventListener('DOMContentLoaded', initApp);
        }
    </script>
</body>
</html>
