<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
    <style>
        /* Стили остаются без изменений */
    </style>
    <script>
        // Конфигурация
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const TG_FIELD_CODE = 'UF_CRM_1751577211';

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
            if (!window.b24form) {
                console.warn("b24form не доступен");
                return;
            }

            const originalB24form = window.b24form;

            window.b24form = function(params) {
                const originalCallback = params.callback;

                params.callback = function(result) {
                    console.log("Callback вызван с результатом:", result);

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
        <!-- HTML остается без изменений -->

        <!-- Bitrix24 Widget Loader -->
        <script>
            (function(w, d, u) {
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

                // Инициализируем перехватчик перед загрузкой виджета
                initBitrixInterceptor();

                var s = d.createElement('script');
                s.async = true;
                s.src = u + '?' + (Date.now() / 60000 | 0);

                var h = d.getElementsByTagName('script')[0];
                h.parentNode.insertBefore(s, h);
            })(window, document, 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js');
        </script>

        <!-- Остальной HTML -->
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