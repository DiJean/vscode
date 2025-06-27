<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор услуги</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Выберите услугу</div>
        <div id="content-container"></div>
    </div>

    <script src="telegram-api.js"></script>
    <script src="bitrix-integration.js"></script>
    <script>
        function initApp() {
            initTelegramApp((tg) => {
                const user = getUserData(tg) || {};
                
                // Формируем контент страницы
                document.getElementById('content-container').innerHTML = `
                    <div class="service-selection">
                        <div class="role-label">Выберите услугу:</div>
                        <select class="role-select" id="service">
                            <option value="" disabled selected>Выберите услугу...</option>
                            <option value="design">Дизайн</option>
                            <option value="development">Разработка</option>
                            <option value="consulting">Консультация</option>
                        </select>
                        <div class="welcome-text">
                            Мы поможем реализовать ваш проект!
                        </div>
                    </div>
                `;
                
                // Настройка кнопки
                setupMainButton(tg, async () => {
                    const service = document.getElementById('service').value;
                    
                    // Отправляем данные в Bitrix24
                    await sendToBitrix24({
                        service: service,
                        userId: user.id,
                        firstName: user.first_name
                    }, 'crm.deal.add');
                    
                    // Отправляем данные в бота
                    tg.sendData(JSON.stringify({ service: service }));
                    
                    tg.close();
                });
                
                return true;
            }) || showFallbackView();
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Добро пожаловать!';
            document.getElementById('content-container').innerHTML = `
                <div class="welcome-text">
                    Выберите нужную услугу из списка
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>
