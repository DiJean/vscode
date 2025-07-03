<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 + Telegram ID</title>
    <script>
        // Глобальные переменные для отслеживания состояния
        window.bitrixIntegration = {
            tgUserId: null,
            widgetLoaded: false,
            formsInitialized: false
        };

        // Функция получения Telegram User ID
        function getTelegramUserId() {
            try {
                // Проверка через Telegram WebApp API
                if (window.Telegram && Telegram.WebApp) {
                    const tg = Telegram.WebApp;
                    if (tg.initDataUnsafe?.user?.id) {
                        const userId = tg.initDataUnsafe.user.id.toString();
                        console.log("Telegram ID из WebApp:", userId);
                        localStorage.setItem('tgUserId', userId);
                        return userId;
                    }
                }

                // Проверка localStorage
                const storedId = localStorage.getItem('tgUserId');
                if (storedId) {
                    console.log("Telegram ID из localStorage:", storedId);
                    return storedId;
                }

                // Проверка URL параметров (для тестирования)
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

        // Основная функция для интеграции с Bitrix24
        function integrateWithBitrix() {
            const tgUserId = window.bitrixIntegration.tgUserId;
            if (!tgUserId) return;

            // Метод 1: Передача через параметры данных формы
            if (window.b24form) {
                const originalB24form = window.b24form;
                window.b24form = function(params) {
                    params.data = params.data || {};
                    params.data.UF_CRM_1751128872 = tgUserId;
                    console.log("TG ID передан через params.data", params.data);
                    return originalB24form(params);
                };
            }

            // Метод 2: Добавление скрытого поля в формы
            const processForms = () => {
                document.querySelectorAll('form.b24-form').forEach(form => {
                    // Удаляем старое поле если есть
                    const existingField = form.querySelector('[name="UF_CRM_1751128872"]');
                    if (existingField) existingField.remove();

                    // Создаем новое скрытое поле
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'UF_CRM_1751128872';
                    hiddenField.value = tgUserId;
                    form.appendChild(hiddenField);
                    console.log("Добавлено скрытое поле в форму", hiddenField);
                });
            };

            // Вызываем сразу и каждые 2 секунды (на случай динамической загрузки форм)
            processForms();
            setInterval(processForms, 2000);

            // Метод 3: Перехват отправки форм
            document.body.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.classList.contains('b24-form')) {
                    // Добавляем поле непосредственно перед отправкой
                    let hiddenField = form.querySelector('[name="UF_CRM_1751128872"]');
                    if (!hiddenField) {
                        hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = 'UF_CRM_1751128872';
                        form.appendChild(hiddenField);
                    }
                    hiddenField.value = tgUserId;
                    console.log("Форма отправлена с TG ID:", tgUserId);
                }
            }, true);
        }

        // Инициализация
        function initIntegration() {
            window.bitrixIntegration.tgUserId = getTelegramUserId();

            // Если виджет уже загружен
            if (window.bitrixIntegration.widgetLoaded) {
                integrateWithBitrix();
            }

            // Отслеживаем загрузку виджета
            const checkWidget = setInterval(() => {
                if (window.BX) {
                    window.bitrixIntegration.widgetLoaded = true;
                    clearInterval(checkWidget);
                    integrateWithBitrix();
                    addDebugMessage("✅ Виджет Bitrix24 загружен");
                }
            }, 500);
        }
    </script>
    <style>
        /* Стили остаются без изменений */
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Bitrix24 + Telegram ID</h1>
            <p>Гарантированная передача Telegram User ID</p>
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
            <h3>Для проверки:</h3>
            <ol class="steps">
                <li class="step">Заполните форму через виджет</li>
                <li class="step">В Bitrix24 откройте созданный контакт</li>
                <li class="step">Проверьте значение поля <code>UF_CRM_1751128872</code></li>
                <li class="step">Должно совпадать с Telegram ID ниже</li>
            </ol>

            <div class="tgid-display">
                <strong>Ваш Telegram ID:</strong>
                <div id="tgid-value">Определение...</div>
            </div>
        </div>

        <!-- Bitrix24 Widget Loader -->
        <script>
            (function(w, d, u) {
                // Сохраняем оригинальную функцию
                w.b24formCallback = w.b24form;

                var s = d.createElement('script');
                s.async = true;
                s.src = u + '?' + (Date.now() / 60000 | 0);
                s.onload = function() {
                    window.bitrixIntegration.widgetLoaded = true;
                    if (window.bitrixIntegration.tgUserId) {
                        integrateWithBitrix();
                    }
                };

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
            // Инициализируем интеграцию
            initIntegration();

            // Отображаем Telegram ID
            const tgUserId = window.bitrixIntegration.tgUserId;
            const tgidValue = document.getElementById('tgid-value');

            if (tgUserId) {
                tgidValue.textContent = tgUserId;
                tgidValue.className = 'success';
                addDebugMessage(`✅ Telegram ID получен: ${tgUserId}`, 'success');
                addDebugMessage('ID будет передан в поле UF_CRM_1751128872', 'success');
            } else {
                tgidValue.textContent = 'Не обнаружен';
                tgidValue.className = 'error';
                addDebugMessage('❌ Telegram ID не обнаружен', 'error');
                addDebugMessage('Формы будут работать без передачи Telegram ID', 'warning');
            }

            // Добавляем ссылку для тестирования
            addDebugMessage(`<a href="?debug_tg_id=TEST123456" target="_blank">Протестировать с ID: TEST123456</a>`, 'info');
        });
    </script>
</body>

</html>