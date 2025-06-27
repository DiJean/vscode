<?php
header('Content-Type: text/html; charset=utf-8');
header("Access-Control-Allow-Origin: https://web.telegram.org");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
// включили лог ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Запись ошибок в файл
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/vm20c2.ru/php_errors.log');
// Временная диагностика
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка доступности
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/plain');
    echo "Web App доступен!\n";
    echo "SSL: " . (isset($_SERVER['HTTPS']) ? 'Да' : 'Нет') . "\n";
    exit;
}
// Проверка, что запрос пришел из Telegram
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'telegram') === false) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Создание заказа</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        :root {
            --bg-color: var(--tg-theme-bg-color, #ffffff);
            --text-color: var(--tg-theme-text-color, #000000);
            --button-color: var(--tg-theme-button-color, #2eaddc);
            --button-text-color: var(--tg-theme-button-text-color, #ffffff);
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
`P.`P.aO.            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        button {
            background: var(--button-color);
            color: var(--button-text-color);
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: opacity 0.3s;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Создание нового заказа</h2>
        <form id="orderForm">
            <div class="form-group">
                <label for="city">Город:</label>
                <input type="text" id="city" required>
            </div>
            
            <div class="form-group">
                <label for="cemetery">Кладбище:</label>
                <input type="text" id="cemetery" required>
            </div>
            
            <div class="form-group">
                <label for="section">Участок:</label>
                <input type="text" id="section" required>
            </div>
            
            <div class="form-group">
                <label for="row">Ряд:</label>
                <input type="text" id="row" required>
            </div>
            
            <div class="form-group">
                <label for="number">Номер:</label>
                <input type="text" id="number" required>
            </div>
            
            <div class="form-group">
                <label for="service">Услуга:</label>
                <select id="service" required>
                    <option value="">Выберите услугу</option>
                    <option value="уборка">Уборка</option>
                    <option value="цветы">Цветы</option>
                    <option value="ремонт">Ремонт</option>
                    <option value="уборка+цветы">Уборка + Цветы</option>
                    <option value="полный_уход">Полный уход</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date">Дата выполнения:</label>
                <input type="date" id="date" required>
            </div>
            
            <div class="form-group">
                <label for="notes">Дополнительные пожелания:</label>
                <textarea id="notes" rows="3"></textarea>
            </div>
            
            <button type="submit" id="submitBtn">Создать заказ</button>
        </form>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        
        // Инициализация WebApp
        tg.expand();
        tg.enableClosingConfirmation();
        
        // Устанавливаем текущую дату как минимальную
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').min = today;
        
        // Обработка отправки формы
        document.getElementById('orderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Показать индикатор загрузки
            tg.showAlert('Отправка данных...');
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
            
            try {
                // Получаем данные формы
                const orderData = {
                    action: 'create_order',
                    city: document.getElementById('city').value,
                    cemetery: document.getElementById('cemetery').value,
                    section: document.getElementById('section').value,
                    row: document.getElementById('row').value,
                    number: document.getElementById('number').value,
                    service: document.getElementById('service').value,
                    date: document.getElementById('date').value,
                    notes: document.getElementById('notes').value
                };
                
                // Отправляем данные в Telegram
                tg.sendData(JSON.stringify(orderData));
                
                // Закрываем WebApp
                tg.close();
            } catch (error) {
                tg.showAlert('Ошибка при отправке: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Создать заказ';
            }
        });
    </script>
</body>
</html>
