<?php
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
header('Content-Type: text/html; charset=utf-8');
header("Access-Control-Allow-Origin: https://web.telegram.org");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отправка отчета</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        :root {
            --bg-color: var(--tg-theme-bg-color, #ffffff);
            --text-color: var(--tg-theme-text-color, #000000);
            --button-color: var(--tg-theme-button-color, #2eaddc);
            --button-text-color: var(--tg-theme-button-text-color, #ffffff);
            --secondary-bg-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input, button {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        input {
            border: 1px solid #ddd;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        button {
            background: var(--button-color);
            color: var(--button-text-color);
            border: none;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 10px;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .photo-section {
            margin: 20px 0;
            text-align: center;
        }
        
        .photo-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .location-info {
            padding: 12px;
            background-color: var(--secondary-bg-color);
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 8px;
            background-color: var(--secondary-bg-color);
            border-radius: 8px;
            margin: 0 5px;
            font-size: 14px;
        }
        
        .step.active {
            background-color: var(--button-color);
            color: var(--button-text-color);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Отправка отчета</h2>
        
        <div class="step-indicator">
            <div class="step active">1. Задача</div>
            <div class="step">2. Фото</div>
            <div class="step">3. Геолокация</div>
        </div>
        
        <form id="reportForm">
            <div class="form-group">
                <label for="taskId">Номер задачи:</label>
                <input type="text" id="taskId" required placeholder="Введите номер задачи из Битрикс24">
            </div>
            
            <div class="form-group photo-section">
                <label>Фото отчет:</label>
                <button type="button" id="takePhotoBtn">Сделать фото</button>
                <img id="photoPreview" class="photo-preview">
            </div>
            
            <div class="form-group">
                <label>Геолокация:</label>
                <button type="button" id="getLocationBtn">Получить геолокацию</button>
                <div id="locationInfo" class="location-info">
                    Координаты: <span id="coordinates"></span>
                </div>
            </div>
            
            <button type="submit" id="submitBtn" disabled>Отправить отчет</button>
        </form>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        
        // Инициализация WebApp
        tg.expand();
        tg.enableClosingConfirmation();
        
        // Переменные для данных
        let locationData = null;
        let photoData = null;
        
        // Элементы интерфейса
        const photoPreview = document.getElementById('photoPreview');
        const locationInfo = document.getElementById('locationInfo');
        const coordinatesSpan = document.getElementById('coordinates');
        const submitBtn = document.getElementById('submitBtn');
        
        // Сделать фото
        document.getElementById('takePhotoBtn').addEventListener('click', function() {
            tg.showCamera({
                type: 'photo',
                quality: 0.8,
                callback: (photoData) => {
                    if (photoData) {
                        photoPreview.src = 'data:image/jpeg;base64,' + photoData;
                        photoPreview.style.display = 'block';
                        this.photoData = photoData;
                        updateSubmitButton();
                        updateStep(2);
                    }
                },
                error: (error) => {
                    tg.showAlert('Ошибка камеры: ' + error);
                }
            });
        });
        
        // Получить геолокацию
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            tg.requestLocation({
                callback: (location) => {
                    if (location) {
                        locationData = {
                            latitude: location.latitude,
                            longitude: location.longitude
                        };
                        coordinatesSpan.textContent = `${location.latitude}, ${location.longitude}`;
                        locationInfo.style.display = 'block';
                        updateSubmitButton();
                        updateStep(3);
                    }
                },
                error: (error) => {
                    tg.showAlert('Ошибка геолокации: ' + error);
                }
            });
        });
        
        // Обновление состояния кнопки отправки
        function updateSubmitButton() {
            submitBtn.disabled = !(photoData && locationData);
        }
        
        // Обновление индикатора шагов
        function updateStep(stepNumber) {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                step.classList.toggle('active', index < stepNumber);
            });
        }
        
        // Отправка отчета
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const taskId = document.getElementById('taskId').value;
            if (!taskId) {
                tg.showAlert('Введите номер задачи');
                return;
            }
            
            if (!photoData) {
                tg.showAlert('Сделайте фото отчет');
                return;
            }
            
            if (!locationData) {
                tg.showAlert('Получите геолокацию');
                return;
            }
            
            // Показать индикатор загрузки
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
            
            try {
                // В реальном приложении здесь нужно загрузить фото на сервер
                // Для примера используем временную ссылку
                const photoUrl = `https://api.telegram.org/file/bot<?=BOT_TOKEN?>/photos/${Date.now()}.jpg`;
                
                // Отправляем данные в Telegram
                tg.sendData(JSON.stringify({
                    action: 'submit_report',
                    task_id: taskId,
                    photo_url: photoUrl,
                    location: locationData
                }));
                
                // Закрываем WebApp
                tg.close();
            } catch (error) {
                tg.showAlert('Ошибка при отправке: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Отправить отчет';
            }
        });
    </script>
</body>
</html>
