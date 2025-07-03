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
        const TG_FIELD_CODE = 'UF_CRM_1751128872';

        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                if (window.Telegram && Telegram.WebApp) {
                    const tg = Telegram.WebApp;
                    if (tg.initDataUnsafe?.user?.id) {
                        const userId = tg.initDataUnsafe.user.id.toString();
                        console.log("Telegram ID из WebApp:", userId);
                        localStorage.setItem('tgUserId', userId);
                        return userId;
                    }
                }

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

        // Перехватчик для виджета Bitrix24
        function initBitrixInterceptor() {
            if (!window.b24form) return;

            const originalB24form = window.b24form;

            window.b24form = function(params) {
                const originalCallback = params.callback;

                params.callback = function(result) {
                    if (result && result.result) {
                        const leadId = result.result;
                        const tgUserId = getTelegramUserId();

                        if (tgUserId) {
                            updateLeadInBitrix(leadId, tgUserId);
                        }
                    }

                    if (originalCallback) {
                        originalCallback(result);
                    }
                };

                originalB24form(params);
            };
        }

        // Обновление лида в Bitrix24
        async function updateLeadInBitrix(leadId, tgUserId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.update.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: leadId,
                        fields: {
                            [UF_CRM_1751577211]: tgUserId
                        }
                    })
                });

                const result = await response.json();
                console.log("Лид обновлен:", result);

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
            if (!debugDiv) return;

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
            (function(w, d, u) {
                // Инициализируем перехватчик перед загрузкой виджета
                initBitrixInterceptor();

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
        // Инициализация страницы
        document.addEventListener('DOMContentLoaded', function() {
            const tgUserId = getTelegramUserId();
            const tgidValue = document.getElementById('tgid-value');

            if (tgUserId) {
                tgidValue.textContent = tgUserId;
                tgidValue.className = 'success';
                addDebugMessage(`✅ Telegram ID получен: ${tgUserId}`, 'success');
                addDebugMessage(`ID будет добавлен в поле ${TG_FIELD_CODE}`, 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                tgidValue.textContent = 'Не обнаружен';
                tgidValue.className = 'error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
        });
    </script>
</body>

</html>