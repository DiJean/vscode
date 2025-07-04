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
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <script>
        // Конфигурация
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
        const TG_LEAD_FIELD = 'UF_CRM_1751577211'; // Поле лида для Telegram ID
        const TG_CONTACT_FIELD = 'UF_CRM_6866F376B4A80'; // Поле контакта для Telegram ID

        // Получение Telegram User ID
        function getTelegramUserId() {
            try {
                let userId = null;
                if (window.Telegram?.WebApp?.initDataUnsafe?.user?.id) {
                    userId = Telegram.WebApp.initDataUnsafe.user.id.toString();
                    console.log("Telegram ID из WebApp:", userId);
                    localStorage.setItem('tgUserId', userId);
                }
                if (userId) return userId;

                const storedId = localStorage.getItem('tgUserId');
                if (storedId) return storedId;

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

        // Функция для добавления статуса в debug-панель
        function addDebugMessage(message, type = 'info') {
            const debugDiv = document.getElementById('debug-content');
            if (!debugDiv) return;

            const messageDiv = document.createElement('div');
            messageDiv.className = `status-item ${type}`;
            messageDiv.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> ${message}`;
            debugDiv.appendChild(messageDiv);
            debugDiv.scrollTop = debugDiv.scrollHeight;
        }

        // Инициализация Telegram WebApp
        function initTelegramWebApp() {
            if (window.Telegram?.WebApp) {
                try {
                    Telegram.WebApp.ready();
                    if (Telegram.WebApp.isExpanded !== true) {
                        Telegram.WebApp.expand();
                    }
                    Telegram.WebApp.setHeaderColor('#6a11cb');
                    Telegram.WebApp.backgroundColor = '#6a11cb';
                    addDebugMessage("✅ Telegram WebApp инициализирован", "success");
                } catch (e) {
                    addDebugMessage(`❌ Ошибка инициализации Telegram: ${e.message}`, "error");
                }
            } else {
                addDebugMessage("ℹ️ Telegram WebApp API недоступно", "info");
            }
        }

        // Основная функция инициализации Bitrix24 формы
        function initBitrixForm() {
            const tgUserId = getTelegramUserId();
            if (!tgUserId) {
                addDebugMessage("⚠️ Telegram ID не найден, форма будет работать без него", "warning");
            }

            // Создаем скрытое поле для Telegram ID
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = TG_LEAD_FIELD;
            hiddenField.value = tgUserId || '';
            hiddenField.id = 'b24-tg-field';

            // Находим контейнер формы и добавляем скрытое поле
            const formContainer = document.querySelector('[data-b24-form]');
            if (formContainer) {
                formContainer.appendChild(hiddenField);
                addDebugMessage(`✅ Скрытое поле ${TG_LEAD_FIELD} добавлено в форму`);
            } else {
                addDebugMessage("❌ Контейнер формы не найден", "error");
            }

            // Перехват события отправки формы
            document.addEventListener('b24formSubmit', function(event) {
                const leadId = event.detail?.result;
                if (leadId) {
                    document.getElementById('leadid-value').textContent = leadId;
                    addDebugMessage(`✅ Создан лид #${leadId}`, "success");

                    // Проверяем заполнение поля в лиде
                    setTimeout(() => {
                        fetch(`${BITRIX_WEBHOOK}crm.lead.get.json?id=${leadId}`)
                            .then(response => response.json())
                            .then(leadData => {
                                const fieldValue = leadData.result?.[TG_LEAD_FIELD];
                                if (fieldValue === tgUserId) {
                                    addDebugMessage(`✅ Поле ${TG_LEAD_FIELD} в лиде заполнено: ${fieldValue}`, "success");
                                } else {
                                    addDebugMessage(`❌ Поле ${TG_LEAD_FIELD} в лиде не заполнено! Значение: ${fieldValue || 'пусто'}`, "error");
                                }

                                // Получаем Contact ID с повторными попытками
                                getContactInfo(leadId, tgUserId);
                            })
                            .catch(error => {
                                console.error("Ошибка проверки лида:", error);
                                addDebugMessage(`❌ Ошибка проверки лида: ${error.message}`, "error");
                            });
                    }, 3000);
                } else {
                    addDebugMessage("❌ Не удалось получить ID лида", "error");
                }
            });
        }

        // Функция для получения информации о контакте
        function getContactInfo(leadId, tgUserId, attempt = 1) {
            const MAX_ATTEMPTS = 5;
            const RETRY_DELAY = 3000; // 3 секунды

            console.log(`Попытка #${attempt} получения контакта для лида ${leadId}`);
            addDebugMessage(`🔄 Попытка #${attempt}: получение контакта для лида ${leadId}`, "info");

            fetch(`${BITRIX_WEBHOOK}crm.lead.get.json?id=${leadId}`)
                .then(response => response.json())
                .then(leadData => {
                    const contactId = leadData.result?.CONTACT_ID;

                    if (contactId) {
                        console.log("Contact ID:", contactId);
                        addDebugMessage(`✅ Получен Contact ID: ${contactId}`, "success");

                        // Проверяем поле в контакте
                        checkContactField(contactId, tgUserId);

                    } else {
                        console.log("CONTACT_ID не найден в лиде");
                        addDebugMessage(`ℹ️ CONTACT_ID в лиде не найден`, "info");

                        // Повторная попытка если не превышен лимит
                        if (attempt < MAX_ATTEMPTS) {
                            setTimeout(() => getContactInfo(leadId, tgUserId, attempt + 1), RETRY_DELAY);
                        } else {
                            addDebugMessage(`❌ Не удалось получить Contact ID после ${MAX_ATTEMPTS} попыток`, "error");
                        }
                    }
                })
                .catch(error => {
                    console.error("Ошибка получения лида:", error);
                    addDebugMessage(`❌ Ошибка получения лида: ${error.message}`, "error");

                    // Повторная попытка при ошибке
                    if (attempt < MAX_ATTEMPTS) {
                        setTimeout(() => getContactInfo(leadId, tgUserId, attempt + 1), RETRY_DELAY);
                    }
                });
        }

        // Функция проверки поля контакта
        function checkContactField(contactId, tgUserId) {
            console.log(`Проверка поля контакта #${contactId}`);
            addDebugMessage(`🔄 Проверка поля контакта #${contactId}`, "info");

            fetch(`${BITRIX_WEBHOOK}crm.contact.get.json?id=${contactId}`)
                .then(response => response.json())
                .then(contactData => {
                    const contactFieldValue = contactData.result?.[TG_CONTACT_FIELD];
                    console.log("Contact field value:", contactFieldValue);

                    if (contactFieldValue === tgUserId) {
                        addDebugMessage(`✅ Поле ${TG_CONTACT_FIELD} в контакте заполнено: ${contactFieldValue}`, "success");
                    } else {
                        addDebugMessage(`❌ Поле ${TG_CONTACT_FIELD} в контакте не заполнено! Значение: ${contactFieldValue || 'пусто'}`, "error");
                    }
                })
                .catch(error => {
                    console.error("Ошибка получения контакта:", error);
                    addDebugMessage(`❌ Ошибка получения контакта: ${error.message}`, "error");
                });
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
            <p>Поле для Telegram ID: <code>UF_CRM_1751577211</code> (в лиде)</p>
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
                <li class="step">Telegram ID передается как скрытое поле</li>
                <li class="step">Создается лид с заполненным полем Telegram ID</li>
                <li class="step">Лид конвертируется в контакт</li>
                <li class="step">Система проверяет заполнение поля в лиде и контакте</li>
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
            </div>
        </div>

        <!-- Стандартная интеграция Bitrix24 -->
        <script>
            (function(w, d, u) {
                // Создаем обработчик события для отправки формы
                w.b24formResult = function(result) {
                    const event = new CustomEvent('b24formSubmit', {
                        detail: result
                    });
                    document.dispatchEvent(event);
                };

                var s = d.createElement('script');
                s.async = true;
                s.src = u + '?' + (Date.now() / 60000 | 0);
                var h = d.getElementsByTagName('script')[0];
                h.parentNode.insertBefore(s, h);
            })(window, document, 'https://cdn-ru.bitrix24.ru/b34052738/crm/site_button/loader_1_wugrzo.js');

            // Инициализация формы
            document.addEventListener('DOMContentLoaded', function() {
                // Добавляем обработчик для формы Bitrix24
                window.b24form = {
                    onload: function(form) {
                        console.log("Bitrix24 Form loaded");
                        addDebugMessage("✅ Форма Bitrix24 загружена", "success");

                        // Перехват события отправки
                        form.onSubmit = function(callback) {
                            this._callback = callback;
                        };

                        // Перехват метода отправки
                        const originalSubmit = form.submit;
                        form.submit = function() {
                            const result = originalSubmit.apply(this, arguments);

                            if (result && result.then) {
                                result.then(data => {
                                    if (this._callback) this._callback(data);
                                    window.b24formResult(data);
                                }).catch(error => {
                                    console.error("Form submit error:", error);
                                    addDebugMessage(`❌ Ошибка при создании лида: ${error.message}`, "error");
                                });
                            }
                            return result;
                        };
                    }
                };
            });
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
            initBitrixForm();

            const tgUserId = getTelegramUserId();
            const tgidElement = document.getElementById('tgid-value');

            if (tgidElement) {
                if (tgUserId) {
                    tgidElement.textContent = tgUserId;
                    tgidElement.className = 'id-value success';
                    addDebugMessage(`✅ Telegram ID получен: ${tgUserId}`, 'success');
                    localStorage.setItem('tgUserId', tgUserId);
                } else {
                    tgidElement.textContent = 'Не обнаружен';
                    tgidElement.className = 'id-value error';
                    addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                }
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
            addDebugMessage(`Текущее время: ${new Date().toLocaleString()}`, 'info');
            addDebugMessage(`Версия скрипта: ${<?= $version ?>}`, 'info');
        });
    </script>
</body>

</html>