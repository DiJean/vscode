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

        // Получение данных лида по ID
        async function getLeadData(leadId) {
            try {
                if (isNaN(leadId)) {
                    throw new Error(`Некорректный ID лида: ${leadId}`);
                }

                const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.get.json?id=${leadId}`);
                const result = await response.json();

                if (result.error) {
                    throw new Error(`Bitrix API Error: ${result.error} - ${result.error_description}`);
                }

                return result.result;
            } catch (e) {
                console.error("Ошибка получения данных лида:", e);
                addDebugMessage(`❌ Ошибка получения данных лида #${leadId}: ${e.message}`, 'error');
                return null;
            }
        }

        // Поиск контакта по телефону или email
        async function findContactByLeadData(leadData) {
            try {
                // Пытаемся найти телефон
                let phone = null;
                if (leadData.PHONE && leadData.PHONE.length > 0) {
                    phone = leadData.PHONE[0].VALUE.replace(/[^0-9]/g, '');
                }

                // Пытаемся найти email
                let email = null;
                if (leadData.EMAIL && leadData.EMAIL.length > 0) {
                    email = leadData.EMAIL[0].VALUE;
                }

                if (!phone && !email) {
                    addDebugMessage("❌ В лиде не найдены телефон или email для поиска контакта", 'error');
                    return null;
                }

                // Формируем фильтр для поиска
                let filter = {};
                if (phone) filter['PHONE'] = phone;
                if (email) filter['EMAIL'] = email;

                const requestBody = {
                    filter: filter,
                    select: ["ID"]
                };

                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list.json`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                const result = await response.json();

                if (result.error) {
                    throw new Error(`Bitrix API Error: ${result.error} - ${result.error_description}`);
                }

                if (result.result && result.result.length > 0) {
                    return result.result[0].ID;
                }

                return null;
            } catch (e) {
                console.error("Ошибка поиска контакта:", e);
                addDebugMessage(`❌ Ошибка поиска контакта: ${e.message}`, 'error');
                return null;
            }
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

            // Обновляем блок с Lead ID
            const leadIdElement = document.getElementById('leadid-value');
            if (leadIdElement) {
                leadIdElement.textContent = leadId;
                leadIdElement.className = 'id-value success';
            }

            // Ждем 2 секунды перед началом обработки
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Получаем данные лида
            const leadData = await getLeadData(leadId);

            if (!leadData) {
                addDebugMessage(`❌ Не удалось получить данные лида #${leadId}`, 'error');
                return;
            }

            // Ищем контакт по данным лида
            const contactId = await findContactByLeadData(leadData);

            if (!contactId) {
                addDebugMessage(`❌ Не удалось найти контакт для лида #${leadId}`, 'error');
                return;
            }

            addDebugMessage(`✅ Найден контакт #${contactId} для лида #${leadId}`, 'success');

            // Обновляем блок с Contact ID
            const contactIdElement = document.getElementById('contactid-value');
            if (contactIdElement) {
                contactIdElement.textContent = contactId;
                contactIdElement.className = 'id-value success';
            }

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

    <style>
        .id-display-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .id-display {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            min-width: 150px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .id-display strong {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-size: 14px;
        }

        .id-value {
            font-size: 18px;
            font-weight: bold;
            word-break: break-all;
        }

        .id-value.success {
            color: #28a745;
        }

        .id-value.error {
            color: #dc3545;
        }

        .id-value.waiting {
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .id-display-container {
                flex-direction: column;
            }
        }
    </style>
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
                <li class="step">Лид конвертируется в контакт и сделку</li>
                <li class="step">Мы ищем контакт по данным из лида</li>
                <li class="step">Добавляем Telegram ID в поле контакта</li>
            </ol>

            <div class="id-display-container">
                <div class="id-display">
                    <strong>Telegram ID:</strong>
                    <div id="tgid-value" class="id-value waiting">Определение...</div>
                </div>

                <div class="id-display">
                    <strong>Lead ID:</strong>
                    <div id="leadid-value" class="id-value waiting">Ожидание формы...</div>
                </div>

                <div class="id-display">
                    <strong>Contact ID:</strong>
                    <div id="contactid-value" class="id-value waiting">Ожидание обработки...</div>
                </div>
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

                        // Сбросить статус перед новой отправкой
                        const leadIdElement = document.getElementById('leadid-value');
                        const contactIdElement = document.getElementById('contactid-value');

                        if (leadIdElement) {
                            leadIdElement.textContent = 'Ожидание формы...';
                            leadIdElement.className = 'id-value waiting';
                        }

                        if (contactIdElement) {
                            contactIdElement.textContent = 'Ожидание обработки...';
                            contactIdElement.className = 'id-value waiting';
                        }

                        form.onSubmit(function(result) {
                            if (result && result.result) {
                                const leadId = result.result;
                                const tgUserId = getTelegramUserId();

                                if (tgUserId) {
                                    console.log(`Обработка лида #${leadId} с Telegram ID: ${tgUserId}`);
                                    // Запускаем обработку
                                    processCreatedLead(leadId, tgUserId);
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
                tgidElement.className = 'id-value success';
                addDebugMessage(`✅ Telegram ID получен: ${tgUserId}`, 'success');
                addDebugMessage(`ID будет добавлен в поле ${TG_CONTACT_FIELD} контакта`, 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                tgidElement.textContent = 'Не обнаружен';
                tgidElement.className = 'id-value error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
            addDebugMessage(`Текущее время: ${new Date().toLocaleString()}`, 'info');
        });
    </script>
</body>

</html>