<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
    <script>
        // Улучшенная функция получения Telegram ID
        function getTelegramUserId() {
            try {
                // Проверка Telegram WebApp
                if (window.Telegram && Telegram.WebApp) {
                    const tg = Telegram.WebApp;

                    // Расширенная проверка данных пользователя
                    if (tg.initDataUnsafe && tg.initDataUnsafe.user && tg.initDataUnsafe.user.id) {
                        const tgUserId = tg.initDataUnsafe.user.id.toString();
                        console.log("Telegram ID из WebApp:", tgUserId);
                        localStorage.setItem('tgUserId', tgUserId);
                        return tgUserId;
                    }

                    // Проверка query-параметров (для дебага)
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('tg_user_id')) {
                        const tgUserId = urlParams.get('tg_user_id');
                        console.log("Telegram ID из URL параметра:", tgUserId);
                        localStorage.setItem('tgUserId', tgUserId);
                        return tgUserId;
                    }
                }

                // Проверка localStorage
                const storedId = localStorage.getItem('tgUserId');
                if (storedId) {
                    console.log("Telegram ID из localStorage:", storedId);
                    return storedId;
                }

                console.warn("Telegram ID не найден");
                return null;
            } catch (e) {
                console.error("Ошибка получения Telegram ID:", e);
                return null;
            }
        }

        // Инициализация виджета с гарантированной передачей TG ID
        function initBitrixWidget() {
            const tgUserId = getTelegramUserId();

            // Переопределяем функцию виджета
            window.b24form = function(params) {
                // Добавляем TG ID в параметры формы
                if (tgUserId) {
                    // Основной метод передачи
                    params.data = params.data || {};
                    params.data.UF_CRM_1751128872 = tgUserId;

                    // Дополнительный метод через скрытые поля
                    setTimeout(() => {
                        try {
                            const formSelector = `.${params.form_class}`;
                            const form = document.querySelector(formSelector);

                            if (form) {
                                // Удаляем старое поле если есть
                                const existingField = form.querySelector('[name="UF_CRM_1751128872"]');
                                if (existingField) existingField.remove();

                                // Создаем новое скрытое поле
                                const hiddenField = document.createElement('input');
                                hiddenField.type = 'hidden';
                                hiddenField.name = 'UF_CRM_1751128872';
                                hiddenField.value = tgUserId;
                                form.appendChild(hiddenField);

                                console.log("Добавлено скрытое поле в форму:", hiddenField);
                            }
                        } catch (e) {
                            console.error("Ошибка добавления скрытого поля:", e);
                        }
                    }, 1500);
                }

                // Вызываем оригинальную функцию
                if (window.b24formCallback) {
                    window.b24formCallback(params);
                }
            };
        }
    </script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: white;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .debug-panel {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            font-family: monospace;
            overflow-x: auto;
            max-height: 300px;
        }

        .panel-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .refresh-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .status-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .success {
            color: #4ade80;
        }

        .warning {
            color: #fbbf24;
        }

        .error {
            color: #f87171;
        }

        .instructions {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .steps {
            padding-left: 25px;
            margin-bottom: 20px;
        }

        .step {
            margin-bottom: 15px;
            position: relative;
            padding-left: 30px;
        }

        .step:before {
            content: "•";
            position: absolute;
            left: 0;
            top: 0;
            font-size: 24px;
            color: #818cf8;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .btn-icon {
            margin-right: 8px;
        }

        .bitrix-logo {
            width: 120px;
            margin: 0 auto 20px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Bitrix24 + Telegram ID</h1>
            <p>Интеграция Telegram User ID с CRM</p>
        </div>

        <div class="debug-panel">
            <div class="panel-title">
                <h3>Статус интеграции</h3>
                <button class="refresh-btn" onclick="location.reload()">Обновить</button>
            </div>
            <div id="debug-content">
                <div class="status-item">Инициализация...</div>
            </div>
        </div>

        <div class="instructions">
            <h3>Как проверить работоспособность:</h3>
            <ol class="steps">
                <li class="step">Заполните тестовую форму через виджет Bitrix24</li>
                <li class="step">В Bitrix24 откройте созданный контакт</li>
                <li class="step">Проверьте поле <code>UF_CRM_1751128872</code></li>
                <li class="step">Значение должно совпадать с вашим Telegram ID</li>
            </ol>

            <div class="note">
                <p><strong>Важно:</strong> В Bitrix24 должно быть создано пользовательское поле:</p>
                <ul>
                    <li>Код: <code>UF_CRM_1751128872</code></li>
                    <li>Название: Telegram User ID</li>
                    <li>Тип: Строка</li>
                    <li>Привязка: Контакты</li>
                </ul>
            </div>
        </div>

        <!-- Bitrix24 Widget Loader -->
        <script>
            (function(w, d, u) {
                // Сохраняем оригинальную функцию
                w.b24formCallback = w.b24form;

                // Инициализируем интеграцию
                initBitrixWidget();

                var s = d.createElement('script');
                s.async = true;
                s.src = u + '?' + (Date.now() / 60000 | 0);
                var h = d.getElementsByTagName('script')[0];
                h.parentNode.insertBefore(s, h);
            })(window, document, 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js');
        </script>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="back-btn">
                <span class="btn-icon">←</span> На главную
            </a>
        </div>
    </div>

    <script>
        // Функция для добавления статуса в debug-панель
        function addDebugMessage(message, type = 'info') {
            const debugDiv = document.getElementById('debug-content');
            const messageDiv = document.createElement('div');
            messageDiv.className = `status-item ${type}`;

            const timestamp = new Date().toLocaleTimeString();
            messageDiv.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;

            debugDiv.appendChild(messageDiv);
            debugDiv.scrollTop = debugDiv.scrollHeight;
        }

        // Инициализация страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем начальные сообщения
            addDebugMessage('Страница загружена');

            // Получаем и отображаем Telegram ID
            const tgUserId = getTelegramUserId();

            if (tgUserId) {
                addDebugMessage(`✅ Telegram ID получен: <strong>${tgUserId}</strong>`, 'success');
                addDebugMessage('ID будет передан в поле UF_CRM_1751128872', 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            // Мониторинг событий Bitrix24
            let widgetLoaded = false;

            // Проверка загрузки виджета
            const checkWidget = setInterval(() => {
                if (window.BX) {
                    addDebugMessage('✅ Виджет Bitrix24 загружен', 'success');
                    widgetLoaded = true;
                    clearInterval(checkWidget);
                }
            }, 500);

            // Таймаут проверки виджета
            setTimeout(() => {
                if (!widgetLoaded) {
                    addDebugMessage('❌ Виджет Bitrix24 не загрузился', 'error');
                    addDebugMessage('Проверьте подключение к интернету и URL виджета', 'warning');
                    clearInterval(checkWidget);
                }
            }, 10000);
        });
    </script>
</body>

</html>