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
        const TG_LEAD_FIELD = 'UF_CRM_1751577211'; // Поле лида для Telegram ID

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
                <li class="step">Создается лид в Bitrix24 с Telegram ID</li>
                <li class="step">Лид автоматически конвертируется в контакт</li>
                <li class="step">Telegram ID остается в лиде и копируется в контакт</li>
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

        <!-- Стандартная интеграция Bitrix24 с модификацией -->
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

                        const tgUserId = getTelegramUserId();
                        if (tgUserId) {
                            try {
                                // Добавляем скрытое поле с Telegram ID
                                form.addField({
                                    code: TG_LEAD_FIELD,
                                    value: tgUserId,
                                    type: 'hidden'
                                });
                                addDebugMessage(`✅ Добавлено скрытое поле ${TG_LEAD_FIELD} со значением: ${tgUserId}`, "success");
                            } catch (e) {
                                console.error("Ошибка добавления поля:", e);
                                addDebugMessage(`❌ Ошибка добавления поля: ${e.message}`, "error");
                            }
                        }

                        form.onSubmit(function(result) {
                            if (result && result.result) {
                                const leadId = result.result;
                                const leadIdElement = document.getElementById('leadid-value');

                                if (leadIdElement) {
                                    leadIdElement.textContent = leadId;
                                    leadIdElement.className = 'id-value success';
                                }

                                addDebugMessage(`✅ Создан лид #${leadId} с Telegram ID`, "success");
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
                addDebugMessage(`ID будет добавлен в поле ${TG_LEAD_FIELD} лида`, 'success');
                localStorage.setItem('tgUserId', tgUserId);
            } else {
                tgidElement.textContent = 'Не обнаружен';
                tgidElement.className = 'id-value error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            addDebugMessage(`<a href="?debug_tg_id=TEST123">Протестировать с ID: TEST123</a>`, 'info');
            addDebugMessage(`Текущее время: ${new Date().toLocaleString()}`, 'info');
            addDebugMessage(`Версия скрипта: ${<?= $version ?>}`, 'info');
        });
    </script>
</body>

</html>