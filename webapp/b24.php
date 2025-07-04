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

                    // Проверяем заполнение поля
                    setTimeout(() => {
                        fetch(`${BITRIX_WEBHOOK}crm.lead.get.json?id=${leadId}`)
                            .then(response => response.json())
                            .then(data => {
                                const fieldValue = data.result?.[TG_LEAD_FIELD];
                                if (fieldValue === tgUserId) {
                                    addDebugMessage(`✅ Поле ${TG_LEAD_FIELD} успешно заполнено: ${fieldValue}`, "success");
                                } else {
                                    addDebugMessage(`❌ Поле ${TG_LEAD_FIELD} не заполнено! Значение: ${fieldValue || 'пусто'}`, "error");
                                }
                            })
                            .catch(error => {
                                addDebugMessage(`❌ Ошибка проверки лида: ${error.message}`, "error");
                            });
                    }, 3000);
                } else {
                    addDebugMessage("❌ Не удалось получить ID лида", "error");
                }
            });
        }
    </script>

    
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
                <li class="step">Система проверяет заполнение поля</li>
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