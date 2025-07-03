<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID Integration</title>
    <script>
        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                // Попытка получения из Telegram WebApp
                if (window.Telegram && Telegram.WebApp) {
                    const user = Telegram.WebApp.initDataUnsafe?.user;
                    if (user && user.id) {
                        localStorage.setItem('tgUserId', user.id);
                        return user.id;
                    }
                }

                // Попытка получения из localStorage
                return localStorage.getItem('tgUserId');
            } catch (e) {
                console.error('Error getting Telegram ID:', e);
                return null;
            }
        }

        // Инициализация виджета Bitrix24 с передачей Telegram ID
        function initBitrixWidget() {
            const tgUserId = getTelegramUserId();

            window.b24form = function(params) {
                // Добавляем Telegram ID в параметры формы
                if (tgUserId) {
                    params.data = params.data || {};
                    params.data.UF_CRM_1751128872 = tgUserId;

                    // Добавляем скрытое поле в форму
                    setTimeout(() => {
                        const form = document.querySelector(`.${params.form_class}`);
                        if (form) {
                            const hiddenField = document.createElement('input');
                            hiddenField.type = 'hidden';
                            hiddenField.name = 'UF_CRM_1751128872';
                            hiddenField.value = tgUserId;
                            form.appendChild(hiddenField);
                        }
                    }, 500);
                }

                // Вызов оригинальной функции Bitrix24
                window.b24formCallback(params);
            };
        }
    </script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .tg-id {
            font-weight: bold;
            color: #2c3e50;
            word-break: break-all;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 10px;
        }

        .instructions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .instructions h3 {
            color: #3498db;
            margin-top: 0;
        }

        .step {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .step:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .note {
            background: #fff8e1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Bitrix24 + Telegram ID Integration</h1>
        <p>Передача Telegram User ID в формы Bitrix24</p>
    </div>

    <div class="status-card">
        <h2>Текущий статус</h2>
        <div id="status">
            <p>Определение Telegram User ID...</p>
        </div>
    </div>

    <div class="instructions">
        <h3>Как это работает:</h3>
        <div class="step">
            <strong>1. Получение Telegram ID</strong>
            <p>Скрипт автоматически определяет ID пользователя Telegram через WebApp API</p>
        </div>

        <div class="step">
            <strong>2. Передача в Bitrix24</strong>
            <p>ID передается в поле <code>UF_CRM_1751128872</code> всех форм Bitrix24</p>
        </div>

        <div class="step">
            <strong>3. Сохранение в Bitrix24</strong>
            <p>Значение сохраняется в указанном пользовательском поле контакта</p>
        </div>

        <div class="note">
            <p><strong>Важно:</strong> В Bitrix24 должно быть создано пользовательское поле с кодом <code>UF_CRM_1751128872</code> для контактов</p>
        </div>
    </div>

    <!-- Bitrix24 Widget Loader -->
    <script>
        (function(w, d, u) {
            // Сохраняем оригинальную функцию
            w.b24formCallback = w.b24form;

            // Инициализируем нашу кастомную функцию
            initBitrixWidget();

            var s = d.createElement('script');
            s.async = true;
            s.src = u + '?' + (Date.now() / 60000 | 0);
            var h = d.getElementsByTagName('script')[0];
            h.parentNode.insertBefore(s, h);
        })(window, document, 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js');
    </script>

    <script>
        // Обновление статусной информации
        document.addEventListener('DOMContentLoaded', function() {
            const tgUserId = getTelegramUserId();
            const statusDiv = document.getElementById('status');

            if (tgUserId) {
                statusDiv.innerHTML = `
                    <p>✅ Telegram User ID успешно получен</p>
                    <p>Значение: <span class="tg-id">${tgUserId}</span></p>
                    <p>Это значение будет автоматически передаваться во все формы Bitrix24 в поле <code>UF_CRM_1751128872</code></p>
                `;
            } else {
                statusDiv.innerHTML = `
                    <p>❌ Telegram User ID не обнаружен</p>
                    <p>Формы Bitrix24 будут работать без передачи идентификатора</p>
                    <p>Примечание: ID доступен только при открытии через Telegram</p>
                `;
            }
        });
    </script>
</body>

</html>