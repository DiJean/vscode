<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заявки</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/dashboard.css">
    <style>
        .deal-details {
            text-align: left;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 1.1rem;
        }
        .back-btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            text-align: center;
            border-radius: 16px;
            margin-top: 20px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Детали заявки</h1>
            <div class="user-info" id="user-info"></div>
        </div>
        
        <div id="deal-container" class="deal-details">
            Загрузка деталей заявки...
        </div>
        
        <a href="dashboard.php" class="back-btn">← Назад к списку заявок</a>
    </div>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
        
        let tg = null;
        let user = null;
        let contactId = null;
        let performerName = "";
        
        // Функция для получения параметра из URL
        function getUrlParameter(name) {
            name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
            const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            const results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
        
        // Поиск исполнителя по Telegram ID
        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filter: {'UF_CRM_1751128872': String(tgId)},
                        select: ['ID', 'NAME', 'LAST_NAME', 'UF_CRM_685D2956061DB']
                    })
                });
                
                const data = await response.json();
                return data.result && data.result.length > 0 ? data.result[0] : null;
            } catch (error) {
                console.error('Ошибка поиска исполнителя:', error);
                return null;
            }
        }
        
        // Загрузка деталей сделки
        async function loadDealDetails(dealId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.get`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: dealId,
                        select: [
                            'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID', 'COMMENTS',
                            'UF_CRM_685D295664A8A', 
                            'UF_CRM_685D2956BF4C8', 
                            'UF_CRM_685D2956C64E0',
                            'UF_CRM_1751128612',
                            'UF_CRM_685D2956D0916',
                            'UF_CRM_1751022940',
                            'UF_CRM_685D2956D7C70',
                            'UF_CRM_685D2956DF40F'
                        ]
                    })
                });
                
                const data = await response.json();
                return data.result || null;
            } catch (error) {
                console.error('Ошибка загрузки деталей сделки:', error);
                return null;
            }
        }
        
        // Основная функция инициализации
        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            tg = Telegram.WebApp;
            
            try {
                user = tg.initDataUnsafe?.user || {};
                
                // Проверяем регистрацию исполнителя
                const performerContact = await findPerformerByTgId(user.id);
                
                if (!performerContact) {
                    tg.showPopup({
                        title: 'Требуется регистрация',
                        message: 'Пройдите регистрацию для доступа к деталям заявки',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'performer-form.php';
                    }, 2000);
                    return;
                }
                
                contactId = performerContact.ID;
                performerName = `${performerContact.NAME || ''} ${performerContact.LAST_NAME || ''}`.trim();
                
                // Отображаем информацию о пользователе
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                
                document.getElementById('user-info').innerHTML = `
                    <div class="avatar">
                        ${user.photo_url ? 
                            `<img src="${user.photo_url}" alt="${fullName}" crossorigin="anonymous">` : 
                            `<div>${firstName.charAt(0) || 'И'}</div>`
                        }
                    </div>
                    <div class="user-name">${fullName || 'Исполнитель'}</div>
                `;
                
                // Загружаем детали сделки
                const dealId = getUrlParameter('id');
                if (!dealId) {
                    throw new Error('Не указан ID заявки');
                }
                
                const deal = await loadDealDetails(dealId);
                if (!deal) {
                    throw new Error('Заявка не найдена');
                }
                
                renderDealDetails(deal);
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                document.getElementById('deal-container').innerHTML = `<div class="error">${e.message || 'Ошибка загрузки'}</div>`;
            }
        }
        
        // Отображение деталей сделки
        function renderDealDetails(deal) {
            const createdDate = new Date(deal.DATE_CREATE).toLocaleString();
            const serviceDate = deal.UF_CRM_685D295664A8A ? 
                new Date(deal.UF_CRM_685D295664A8A).toLocaleDateString() : '-';
            
            // Обработка услуг
            let serviceNames = '-';
            const serviceField = deal.UF_CRM_685D2956C64E0;
            if (serviceField) {
                let serviceIds = [];
                if (Array.isArray(serviceField)) {
                    serviceIds = serviceField.map(id => String(id));
                } else if (typeof serviceField === 'string') {
                    serviceIds = serviceField.split(',');
                } else {
                    serviceIds = [String(serviceField)];
                }
                serviceNames = serviceIds.map(id => {
                    if (id === '69') return 'Уход';
                    if (id === '71') return 'Цветы';
                    if (id === '73') return 'Ремонт';
                    if (id === '75') return 'Церковная служба';
                    return id;
                }).join(', ');
            }
            
            // Статус
            let statusText = deal.STAGE_ID || 'Неизвестно';
            if (statusText === 'NEW') statusText = 'Новая';
            else if (statusText === 'PROCESSING') statusText = 'В работе';
            else if (statusText === 'CLOSED') statusText = 'Завершена';
            
            const dealContainer = document.getElementById('deal-container');
            dealContainer.innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Номер заявки</div>
                    <div class="detail-value">#${deal.ID}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Клиент</div>
                    <div class="detail-value">${deal.TITLE.replace('Заявка от ', '')}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Дата создания</div>
                    <div class="detail-value">${createdDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Желаемая дата услуги</div>
                    <div class="detail-value">${serviceDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Услуги</div>
                    <div class="detail-value">${serviceNames}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Город</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956BF4C8 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Статус</div>
                    <div class="detail-value">${statusText}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Исполнитель</div>
                    <div class="detail-value">${performerName}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Кладбище</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956D0916 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Сектор</div>
                    <div class="detail-value">${deal.UF_CRM_1751022940 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ряд</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956D7C70 || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Участок</div>
                    <div class="detail-value">${deal.UF_CRM_685D2956DF40F || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Комментарий</div>
                    <div class="detail-value">${deal.COMMENTS || '-'}</div>
                </div>
            `;
        }
        
        function showFallbackView() {
            document.getElementById('user-info').innerHTML = `
                <div class="welcome-text">
                    Для просмотра деталей откройте приложение в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>