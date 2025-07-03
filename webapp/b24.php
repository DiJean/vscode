<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
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

        .tgid-display {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
    <script>
        // Конфигурация
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const TG_FIELD_CODE = 'UF_CRM_1751577211';

        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                // Попытка получить ID из WebApp
                if (window.Telegram && Telegram.WebApp) {
                    const tg = Telegram.WebApp;
                    if (tg.initDataUnsafe?.user?.id) {
                        const userId = tg.initDataUnsafe.user.id.toString();
                        console.log("Telegram ID из WebApp:", userId);
                        localStorage.setItem('tgUserId', userId);
                        return userId;
                    }
                }

                // Попытка получить ID из localStorage
                const storedId = localStorage.getItem('tgUserId');
                if (storedId) {
                    console.log("Telegram ID из localStorage:", storedId);
                    return storedId;
                }

                // Попытка получить ID из параметров URL
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('debug_tg_id')) {
                    return urlParams.get('debug_tg_id');
                }

                console.warn("Telegram ID не найден");
                return null;
            } catch (e) {
                console.error("Ошибка получения Telegram ID:", e);
                return null;
            }
        }

        // Перехватчик для виджета Bitrix24
        function initBitrixInterceptor() {
            if (!window.b24form) {
                console.warn("b24form не доступен");
                addDebugMessage("❌ Виджет Bitrix24 не загружен", "error");
                return false;
            }

            const originalB24form = window.b24form;

            window.b24form = function(params) {
                // Сохраняем оригинальный callback
                const originalCallback = params.callback;

                // Создаем новый callback
                params.callback = function(result) {
                    console.log("Callback виджета вызван с результатом:", result);

                    // Если лид успешно создан
                    if (result && result.result) {
                        const leadId = result.result;
                        const tgUserId = getTelegramUserId();

                        if (tgUserId) {
                            console.log(`Обновление лида #${leadId} с Telegram ID: ${tgUserId}`);
                            updateLeadInBitrix(leadId, tgUserId);
                        } else {
                            console.warn("Telegram ID не найден для обновления лида");
                            addDebugMessage("❌ Telegram ID не найден для обновления лида", "warning");
                        }
                    }

                    // Вызываем оригинальный callback
                    if (originalCallback) {
                        originalCallback(result);
                    }
                };

                // Вызываем оригинальную функцию
                return originalB24form(params);
            };

            console.log("Перехватчик виджета Bitrix24 успешно установлен");
            return true;
        }

        // Обновление лида в Bitrix24
        async function updateLeadInBitrix(leadId, tgUserId) {
            try {
                // Проверка формата ID лида
                if (isNaN(leadId)) {
                    throw new Error(`Некорректный ID лида: ${leadId}`);
                }

                // Формируем тело запроса
                const requestBody = {
                    id: parseInt(leadId),
                    fields: {
                        [TG_FIELD_CODE]: tgUserId
                    }
                };

                console.log("Отправка запроса на обновление лида:", requestBody);

                const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.update.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();
                console.log("Ответ от Bitrix24:", result);

                if (result.error) {
                    throw new Error(`Bitrix API Error: ${result.error} - ${result.error_description}`);
                }

                if (result.result) {
                    addDebugMessage(`✅ Telegram ID добавлен в лид #${leadId}`, 'success');
                } else {
                    addDebugMessage(`❌ Ошибка обновления лида #${leadId}: ${JSON.stringify(result)}`, 'error');
                }
            } catch (error) {
                console.error("Ошибка обновления лида:", error);
                addDebugMessage(`❌ Ошибка обновления лида #${leadId}: ${error.message}`, 'error');
            }
        }

        // Функция для добавления статуса в debug-панель
        function addDebugMessage(message, type = 'info') {
            const debugDiv = document.getElementById('debug-content');
            if (!debugDiv) {
                console.error("Debug panel not found");
                return;
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `status-item ${type}`;

            const timestamp = new Date().toLocaleTimeString();
            messageDiv.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;

            debugDiv.appendChild(messageDiv);
            debugDiv.scrollTop = debugDiv.scrollHeight;
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Bitrix24 + Telegram ID</h1>
            <p>Поле для Telegram ID: <code>UF_CRM_1751577211</code></p>
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
            <h3>Как работает:</h3>
            <ol class="steps">
                <li class="step">Вы заполняете форму через виджет</li>
                <li class="step">Создается лид в Bitrix24</li>
                <li class="step">Мы получаем ID созданного лида</li>
                <li class="step">Отдельным запросом добавляем Telegram ID в поле <code>UF_CRM_1751577211</code></li>
            </ol>

            <div class="tgid-display">
                <strong>Ваш Telegram ID:</strong>
                <div id="tgid-value">Определение...</div>
            </div>
        </div>

        <!-- Bitrix24 Widget Loader -->
        <script>
            (function() {
                // Проверка прав доступа вебхука
                fetch(`${BITRIX_WEBHOOK}scope.json`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.result || !data.result.lead) {
                            addDebugMessage("❌ У вебхука недостаточно прав для обновления лидов", "error");
                        }
                    })
                    .catch(error => {
                        console.error("Ошибка проверки прав вебхука:", error);
                        addDebugMessage("❌ Ошибка проверки прав вебхука", "error");
                    });

                // Создаем элемент для виджета
                const widgetScript = document.createElement('script');
                widgetScript.async = true;
                widgetScript.src = 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js';

                // Обработчик успешной загрузки
                widgetScript.onload = function() {
                    console.log("Виджет Bitrix24 загружен");

                    // Периодическая проверка доступности b24form
                    const checkInterval = setInterval(() => {
                        if (window.b24form) {
                            clearInterval(checkInterval);
                            if (initBitrixInterceptor()) {
                                addDebugMessage("✅ Перехватчик виджета установлен", "success");
                            }
                        }
                    }, 300);

                    // Таймаут для остановки проверки
                    setTimeout(() => {
                        clearInterval(checkInterval);
                        if (!window.b24form) {
                            addDebugMessage("❌ Функция b24form не появилась после загрузки виджета", "error");
                        }
                    }, 5000);
                };

                // Обработчик ошибок
                widgetScript.onerror = function() {
                    addDebugMessage("❌ Ошибка загрузки виджета Bitrix24", "error");
                    console.error("Ошибка загрузки виджета Bitrix24");
                };

                // Добавляем виджет на страницу
                document.head.appendChild(widgetScript);
            })();
        </script>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="back-btn">
                <span class="btn-icon">←</span> На главную
            </a>
        </div>
    </div>

    <script>
        // Инициализация страницы
        document.addEventListener('DOMContentLoaded', function() {
            const tgUserId = getTelegramUserId();
            const tgidElement = document.getElementById('tgid-value');

            // Проверка существования элемента
            if (!tgidElement) {
                console.error("Элемент с id 'tgid-value' не найден");
                addDebugMessage("❌ Элемент для отображения Telegram ID не найден", "error");
                return;
            }

            if (tgUserId) {
                tgidElement.textContent = tgUserId;
                tgidElement.className = 'success';
                addDebugMessage(`✅ Telegram ID получен: ${tgUserId}`, 'success');
                addDebugMessage(`ID будет добавлен в поле ${TG_FIELD_CODE}`, 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                tgidElement.textContent = 'Не обнаружен';
                tgidElement.className = 'error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
        });
    </script>
</body>

</html>