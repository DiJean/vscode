<?php
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
    <link rel="stylesheet" href="/webapp/css/b24.css?v=<?= $version ?>">

    <!-- Загрузка Telegram WebApp API -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <script>
        // Конфигурация
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const TG_CONTACT_FIELD = 'UF_CRM_6866F376B4A80'; // Поле контакта для Telegram ID

        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                let userId = null;

                // Попытка получить ID из WebApp
                if (window.Telegram && Telegram.WebApp && Telegram.WebApp.initDataUnsafe?.user?.id) {
                    userId = Telegram.WebApp.initDataUnsafe.user.id.toString();
                    console.log("Telegram ID из WebApp:", userId);
                    localStorage.setItem('tgUserId', userId);
                }

                if (userId) return userId;

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

        // Получение CONTACT_ID из лида с повторными попытками
        async function getContactIdFromLead(leadId) {
            const MAX_ATTEMPTS = 5;
            const RETRY_DELAY = 1000; // 1 секунда

            for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
                try {
                    addDebugMessage(`🔄 Попытка ${attempt}: получение CONTACT_ID для лида #${leadId}`, 'info');

                    const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.get.json?id=${leadId}`);
                    const result = await response.json();

                    if (result.error) {
                        throw new Error(`Bitrix API Error: ${result.error} - ${result.error_description}`);
                    }

                    if (result.result.CONTACT_ID) {
                        return result.result.CONTACT_ID;
                    }

                    // Если CONTACT_ID еще не создан, ждем и повторяем
                    await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
                } catch (e) {
                    console.error(`Ошибка получения CONTACT_ID (попытка ${attempt}):`, e);
                    addDebugMessage(`❌ Ошибка получения CONTACT_ID (попытка ${attempt}): ${e.message}`, 'error');
                }
            }

            addDebugMessage(`❌ Не удалось получить CONTACT_ID для лида #${leadId} после ${MAX_ATTEMPTS} попыток`, 'error');
            return null;
        }

        // Обновление контакта в Bitrix24
        async function updateContactInBitrix(contactId, tgUserId) {
            try {
                if (!contactId) {
                    throw new Error("CONTACT_ID не получен");
                }

                const requestBody = {
                    id: parseInt(contactId),
                    fields: {
                        [TG_CONTACT_FIELD]: tgUserId
                    }
                };

                console.log("Отправка запроса на обновление контакта:", requestBody);

                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();
                console.log("Ответ от Bitrix24 (контакт):", result);

                if (result.error) {
                    throw new Error(`Bitrix API Error: ${result.error} - ${result.error_description}`);
                }

                return result.result;
            } catch (error) {
                console.error("Ошибка обновления контакта:", error);
                addDebugMessage(`❌ Ошибка обновления контакта #${contactId}: ${error.message}`, 'error');
                return false;
            }
        }

        // Обработка созданного лида
        async function processCreatedLead(leadId, tgUserId) {
            addDebugMessage(`🔄 Обработка лида #${leadId}`, 'info');

            // Получаем CONTACT_ID из лида с повторными попытками
            const contactId = await getContactIdFromLead(leadId);

            if (!contactId) {
                addDebugMessage(`❌ Не удалось получить CONTACT_ID для лида #${leadId}`, 'error');
                return;
            }

            addDebugMessage(`✅ Получен CONTACT_ID: ${contactId}`, 'success');

            // Обновляем контакт
            const updateSuccess = await updateContactInBitrix(contactId, tgUserId);

            if (updateSuccess) {
                addDebugMessage(`✅ Telegram ID добавлен в контакт #${contactId}`, 'success');
            } else {
                addDebugMessage(`❌ Ошибка обновления контакта #${contactId}`, 'error');
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

        // Инициализация Telegram WebApp
        function initTelegramWebApp() {
            if (window.Telegram && Telegram.WebApp) {
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
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Bitrix24 + Telegram ID</h1>
            <p>Поле для Telegram ID: <code>UF_CRM_6866F376B4A80</code></p>
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
                <li class="step">Ожидаем конвертации лида в контакт (до 5 сек)</li>
                <li class="step">Получаем ID созданного контакта</li>
                <li class="step">Добавляем Telegram ID в поле контакта</li>
            </ol>

            <div class="tgid-display">
                <strong>Ваш Telegram ID:</strong>
                <div id="tgid-value">Определение...</div>
            </div>
        </div>

        <!-- Стандартная интеграция Bitrix24 -->
        <script>
            (function(w, d, u) {
                var s = d.createElement('script');
                s.async = true;
                s.src = u + '?' + (Date.now() / 60000 | 0);
                var h = d.getElementsByTagName('script')[0];
                h.parentNode.insertBefore(s, h);

                // Глобальный обработчик для форм
                w.b24form = {
                    onload: function(form) {
                        console.log("Bitrix24 Form loaded");
                        addDebugMessage("✅ Форма Bitrix24 загружена", "success");

                        form.onSubmit(function(result) {
                            if (result && result.result) {
                                const leadId = result.result;
                                const tgUserId = getTelegramUserId();

                                if (tgUserId) {
                                    console.log(`Обработка лида #${leadId} с Telegram ID: ${tgUserId}`);
                                    // Запускаем обработку с задержкой для конвертации
                                    setTimeout(() => {
                                        processCreatedLead(leadId, tgUserId);
                                    }, 1000);
                                }
                            }
                        });
                    }
                };
            })(window, document, 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js');
        </script>
        <div data-b24-form="1" data-skip-moving="true"></div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="back-btn">
                <span class="btn-icon">←</span> На главную
            </a>
        </div>
    </div>

    <script>
        // Инициализация страницы
        document.addEventListener('DOMContentLoaded', function() {
            initTelegramWebApp();

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
                addDebugMessage(`ID будет добавлен в поле ${TG_CONTACT_FIELD} контакта`, 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                tgidElement.textContent = 'Не обнаружен';
                tgidElement.className = 'error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
            addDebugMessage(`Текущее время: ${new Date().toLocaleString()}`, 'info');
        });
    </script>
</body>

</html>