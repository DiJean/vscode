<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
    <link rel="stylesheet" href="/webapp/css/b24.css">
    <script>
        // Конфигурация
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const TG_FIELD_CODE = 'UF_CRM_1751577211';

        // Функция для безопасного использования Telegram WebApp API
        function useTelegramAPI(callback) {
            if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                try {
                    const tg = Telegram.WebApp;
                    callback(tg);
                } catch (e) {
                    console.error("Ошибка при работе с Telegram API:", e);
                    addDebugMessage(`❌ Ошибка Telegram API: ${e.message}`, "error");
                }
            } else {
                addDebugMessage("ℹ️ Telegram WebApp API недоступно", "info");
            }
        }

        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                let userId = null;

                useTelegramAPI(tg => {
                    if (tg.initDataUnsafe?.user?.id) {
                        userId = tg.initDataUnsafe.user.id.toString();
                        console.log("Telegram ID из WebApp:", userId);
                        localStorage.setItem('tgUserId', userId);
                    }
                });

                if (userId) return userId;

                const storedId = localStorage.getItem('tgUserId');
                if (storedId) {
                    console.log("Telegram ID из localStorage:", storedId);
                    return storedId;
                }

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

        // Обновление лида в Bitrix24
        async function updateLeadInBitrix(leadId, tgUserId) {
            try {
                if (isNaN(leadId)) {
                    throw new Error(`Некорректный ID лида: ${leadId}`);
                }

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

        // Проверка прав вебхука
        async function checkWebhookPermissions() {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}scope.json`);
                const data = await response.json();

                if (!data.result) {
                    addDebugMessage("❌ Не удалось получить права вебхука", "error");
                    return false;
                }

                // Преобразуем права в массив
                const permissions = Array.isArray(data.result) ? data.result : Object.keys(data.result);
                addDebugMessage(`Права вебхука: ${JSON.stringify(permissions)}`, 'info');

                // Проверяем наличие прав CRM
                if (permissions.includes('crm')) {
                    addDebugMessage("✅ Вебхук имеет права CRM", "success");
                    return true;
                }

                if (permissions.includes('lead')) {
                    addDebugMessage("✅ Вебхук имеет права на лиды", "success");
                    return true;
                }

                addDebugMessage("❌ У вебхука недостаточно прав для обновления лидов", "error");
                return false;

            } catch (error) {
                console.error("Ошибка проверки прав вебхука:", error);
                addDebugMessage(`❌ Ошибка проверки прав вебхука: ${error.message}`, "error");
                return false;
            }
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
            (async function() {
                // Проверка прав вебхука
                await checkWebhookPermissions();

                // Загрузка виджета
                const widgetScript = document.createElement('script');
                widgetScript.async = true;
                widgetScript.src = 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js';

                // Обработчик успешной загрузки
                widgetScript.onload = function() {
                    console.log("Виджет Bitrix24 загружен");
                    addDebugMessage("✅ Виджет Bitrix24 загружен", "success");

                    // Добавляем обработчик после загрузки виджета
                    setupFormHandler();
                };

                // Обработчик ошибок
                widgetScript.onerror = function() {
                    addDebugMessage("❌ Ошибка загрузки виджета Bitrix24", "error");
                    console.error("Ошибка загрузки виджета Bitrix24");
                };

                // Добавляем виджет на страницу
                document.head.appendChild(widgetScript);
            })();

            // Упрощенный обработчик формы
            function setupFormHandler() {
                if (!window.b24form) {
                    addDebugMessage("❌ Функция b24form не найдена", "error");
                    return;
                }

                // Сохраняем оригинальную функцию
                const originalB24form = window.b24form;

                // Переопределяем функцию
                window.b24form = function(params) {
                    // Сохраняем оригинальный callback
                    const originalCallback = params.callback;

                    // Создаем новый callback
                    params.callback = function(result) {
                        // Сначала вызываем оригинальный callback
                        if (originalCallback) {
                            originalCallback(result);
                        }

                        // Затем наш код
                        if (result && result.result) {
                            const leadId = result.result;
                            const tgUserId = getTelegramUserId();

                            if (tgUserId) {
                                console.log(`Обновление лида #${leadId} с Telegram ID: ${tgUserId}`);
                                updateLeadInBitrix(leadId, tgUserId);
                            }
                        }
                    };

                    // Вызываем оригинальную функцию
                    return originalB24form(params);
                };

                addDebugMessage("✅ Обработчик формы установлен", "success");
            }
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

            if (!tgidElement) {
                console.error("Элемент с id 'tgid-value' не найден");
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

            // Безопасная инициализация Telegram WebApp
            if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                try {
                    Telegram.WebApp.ready();

                    if (Telegram.WebApp.isExpanded !== true && typeof Telegram.WebApp.expand === 'function') {
                        Telegram.WebApp.expand();
                    }

                    Telegram.WebApp.backgroundColor = '#6a11cb';
                    if (typeof Telegram.WebApp.setHeaderColor === 'function') {
                        Telegram.WebApp.setHeaderColor('#6a11cb');
                    }

                    addDebugMessage("✅ Telegram WebApp инициализирован", "success");
                } catch (e) {
                    console.error("Ошибка инициализации Telegram WebApp:", e);
                    addDebugMessage(`❌ Ошибка инициализации Telegram: ${e.message}`, "error");
                }
            } else {
                addDebugMessage("ℹ️ Telegram WebApp API недоступно", "info");
            }
        });
    </script>
</body>

</html>