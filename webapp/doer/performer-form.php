<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация исполнителя</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/6.4.3/imask.min.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/client-form.css">
    <style>
        .location-btn {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 5px;
        }
        
        .location-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.7);
        }
        
        .coords-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .coord-input {
            flex: 1;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .debug-panel {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Регистрация исполнителя</div>
        <div id="user-container"></div>
        
        <div class="form-container" id="form-container" style="display: none;">
            <form id="performer-form">
                <!-- ... остальные поля формы ... -->
            </form>
        </div>
        
        <!-- Панель диагностики -->
        <div class="debug-panel" id="debug-panel" style="display: none;">
            <h3>Диагностическая информация</h3>
            <pre id="debug-data"></pre>
        </div>
    </div>

    <script src="../js/telegram-api.js"></script>
    <script src="../js/bitrix-integration.js"></script>
    <script>
        let tg = null;
        let user = null;
        let phoneMask = null;

        // Основная функция инициализации
        async function initApp() {
            // ... инициализация (без изменений) ...
        }
        
        // Получение геолокации
        function getLocation() {
            // ... без изменений ...
        }
        
        // Отправка формы
        async function submitForm() {
            const formData = {
                // ... сбор данных формы ...
            };
            
            if (!validateForm(formData)) {
                return;
            }
            
            try {
                if (tg.showProgress) tg.showProgress();
                
                // Сохраняем исполнителя в Bitrix24
                const result = await savePerformer(formData);
                
                if (result.success) {
                    // Переходим в дашборд
                    window.location.href = 'dashboard.php';
                } else {
                    // Показываем подробную ошибку
                    showDebugPanel(result);
                    tg.showPopup({
                        title: 'Ошибка регистрации',
                        message: result.errorMessage || 'Не удалось зарегистрироваться',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                }
            } catch (error) {
                console.error('Ошибка:', error);
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Произошла непредвиденная ошибка',
                    buttons: [{id: 'ok', type: 'ok'}]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
            }
        }
        
        // Валидация формы
        function validateForm(formData) {
            // ... без изменений ...
        }
        
        // Сохранение исполнителя в Bitrix24 (с детальной диагностикой)
        async function savePerformer(data) {
            try {
                const contactData = {
                    fields: {
                        NAME: data.firstName,
                        LAST_NAME: data.lastName,
                        SECOND_NAME: data.secondName || '',
                        PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                        EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                        UF_CRM_685D2956061DB: data.city, 
                        UF_CRM_1751129816: data.latitude, 
                        UF_CRM_1751129854: data.longitude, 
                        UF_CRM_1751128872: String(data.tgUserId),
                        TYPE_ID: 'EMPLOYEE'
                    }
                };
                
                // Логируем данные перед отправкой
                console.log("Данные для создания контакта:", contactData);
                
                // Проверяем, есть ли уже контакт
                const existingContact = await findPerformerByTgId(data.tgUserId);
                let response, result;
                
                if (existingContact) {
                    // Обновляем существующий контакт
                    response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            id: existingContact.ID,
                            fields: contactData.fields
                        })
                    });
                    result = await response.json();
                    
                    console.log("Результат обновления контакта:", result);
                    
                    if (result.result) {
                        return {
                            success: true,
                            contactId: existingContact.ID
                        };
                    } else {
                        return {
                            success: false,
                            error: result.error,
                            errorMessage: `Ошибка обновления: ${result.error_description}`,
                            requestData: contactData,
                            response: result
                        };
                    }
                } else {
                    // Создаем новый контакт
                    response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(contactData)
                    });
                    result = await response.json();
                    
                    console.log("Результат создания контакта:", result);
                    
                    if (result.result) {
                        return {
                            success: true,
                            contactId: result.result
                        };
                    } else {
                        return {
                            success: false,
                            error: result.error,
                            errorMessage: `Ошибка создания: ${result.error_description}`,
                            requestData: contactData,
                            response: result
                        };
                    }
                }
                
            } catch (error) {
                console.error('Ошибка сохранения исполнителя:', error);
                return {
                    success: false,
                    error: error.message,
                    errorMessage: `Сетевая ошибка: ${error.message}`
                };
            }
        }
        
        // Поиск исполнителя по Telegram ID
        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filter: {'UF_CRM_1751128872': String(tgId)},
                        select: ['ID', 'NAME', 'LAST_NAME']
                    })
                });
                
                const data = await response.json();
                console.log("Результат поиска контакта:", data);
                
                if (data.result && data.result.length > 0) {
                    return data.result[0];
                }
                return null;
            } catch (error) {
                console.error('Ошибка поиска исполнителя:', error);
                return null;
            }
        }
        
        // Показать панель диагностики
        function showDebugPanel(data) {
            document.getElementById('debug-panel').style.display = 'block';
            document.getElementById('debug-data').textContent = JSON.stringify(data, null, 2);
        }
        
        function showFallbackView() {
            // ... без изменений ...
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>