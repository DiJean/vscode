<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд исполнителя</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/dashboard.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Мои заявки</h1>
            <div class="user-info" id="user-info"></div>
        </div>
        
        <div class="controls">
            <div class="filters">
                <select id="status-filter">
                    <option value="">Все статусы</option>
                    <option value="NEW">Новые</option>
                    <option value="PROCESSING">В работе</option>
                    <option value="CLOSED">Завершённые</option>
                </select>
                <input type="text" id="search" placeholder="Поиск по клиенту или услуге...">
            </div>
            <button id="refresh-btn">Обновить</button>
        </div>
        
        <div class="deals-container">
            <table id="deals-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заказ</th>
                        <th>Услуги</th>
                        <th>Дата заявки</th>
                        <th>Желаемая дата</th>
                        <th>Город</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="deals-list">
                    <tr>
                        <td colspan="8" class="loading">Загрузка данных...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination">
            <button id="prev-page" disabled>← Назад</button>
            <span id="current-page">1</span>
            <button id="next-page">Вперед →</button>
        </div>

        <!-- Блок диагностики -->
        <div id="debug-info" style="display: none; margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 12px; font-size: 0.9rem;">
            <h3>Диагностическая информация</h3>
            <pre id="debug-data"></pre>
        </div>
    </div>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
        
        let tg = null;
        let user = null;
        let currentPage = 1;
        const pageSize = 10;
        let contactId = null;
        let performerName = "";
        let performerCity = null;

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
                        message: 'Пройдите регистрацию для доступа к дашборду',
                        buttons: [{id: 'ok', type: 'ok'}]
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'performer-form.php';
                    }, 2000);
                    return;
                }
                
                contactId = performerContact.ID;
                performerName = `${performerContact.NAME || ''} ${performerContact.LAST_NAME || ''}`.trim();
                performerCity = performerContact.UF_CRM_685D2956061DB || '';
                
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
                    <div class="user-debug">ID: ${contactId}, Город: ${performerCity}</div>
                `;
                
                // Загружаем сделки
                loadDeals();
                
                // Настраиваем обработчики
                document.getElementById('refresh-btn').addEventListener('click', loadDeals);
                document.getElementById('prev-page').addEventListener('click', () => changePage(-1));
                document.getElementById('next-page').addEventListener('click', () => changePage(1));
                document.getElementById('status-filter').addEventListener('change', loadDeals);
                document.getElementById('search').addEventListener('input', loadDeals);
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        // Загрузка сделок исполнителя
        async function loadDeals() {
            try {
                document.getElementById('deals-list').innerHTML = `
                    <tr>
                        <td colspan="8" class="loading">Загрузка данных...</td>
                    </tr>
                `;
                
                const status = document.getElementById('status-filter').value;
                const search = document.getElementById('search').value;
                
                // Формируем фильтр по пользовательскому полю UF_CRM_1751128612
                const filter = {
                    'UF_CRM_1751128612': contactId
                };
                
                if (status) filter['STAGE_ID'] = status;
                if (search) filter['%TITLE'] = search;
                
                console.log('Фильтр для сделок:', filter);
                
                const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.list`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filter: filter,
                        select: [
                            'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID',
                            'UF_CRM_685D295664A8A', 
                            'UF_CRM_685D2956BF4C8', 
                            'UF_CRM_685D2956C64E0'
                        ],
                        order: {'DATE_CREATE': 'DESC'},
                        start: (currentPage - 1) * pageSize
                    })
                });
                
                const data = await response.json();
                console.log('Ответ от Bitrix24:', data);
                
                // Показываем диагностическую информацию
                document.getElementById('debug-info').style.display = 'block';
                document.getElementById('debug-data').textContent = JSON.stringify({
                    contactId: contactId,
                    tgUserId: user.id,
                    filter: filter,
                    response: data
                }, null, 2);
                
                renderDeals(data.result || []);
                updatePagination(data.total || 0);
                
            } catch (error) {
                console.error('Ошибка загрузки сделок:', error);
                document.getElementById('deals-list').innerHTML = `
                    <tr>
                        <td colspan="8" class="error">Ошибка загрузки данных</td>
                    </tr>
                `;
                
                document.getElementById('debug-info').style.display = 'block';
                document.getElementById('debug-data').textContent = `Ошибка: ${error.message}`;
            }
        }
        
        // Отображение сделок в таблице
        function renderDeals(deals) {
            const dealsList = document.getElementById('deals-list');
            
            if (!deals || deals.length === 0) {
                dealsList.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty">Заявок не найдено</td>
                    </tr>
                `;
                return;
            }
            
            dealsList.innerHTML = '';
            
            deals.forEach(deal => {
                const createdDate = new Date(deal.DATE_CREATE).toLocaleDateString();
                const serviceDate = deal.UF_CRM_685D295664A8A ? new Date(deal.UF_CRM_685D295664A8A).toLocaleDateString() : '-';
                
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
                
                // Определяем статус
                let statusClass = '';
                let statusText = deal.STAGE_ID || '';
                
                if (statusText === 'NEW') {
                    statusText = 'Новая';
                    statusClass = 'status-new';
                } else if (statusText === 'PROCESSING') {
                    statusText = 'В работе';
                    statusClass = 'status-processing';
                } else if (statusText === 'CLOSED') {
                    statusText = 'Завершена';
                    statusClass = 'status-closed';
                }
                
                dealsList.innerHTML += `
                    <tr>
                        <td>${deal.ID}</td>
                        <td>${deal.TITLE.replace('Заявка от ', '')}</td>
                        <td>${serviceNames}</td>
                        <td>${createdDate}</td>
                        <td>${serviceDate}</td>
                        <td>${deal.UF_CRM_685D2956BF4C8 || '-'}</td>
                        <td><span class="status ${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="action-btn view-btn" data-id="${deal.ID}">Просмотр</button>
                        </td>
                    </tr>
                `;
            });
            
            // Добавляем обработчики для кнопок
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const dealId = this.getAttribute('data-id');
                    viewDealDetails(dealId);
                });
            });
        }
        
        // Обновление пагинации
        function updatePagination(totalItems) {
            const totalPages = Math.ceil(totalItems / pageSize);
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const currentPageEl = document.getElementById('current-page');
            
            currentPageEl.textContent = currentPage;
            
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
        }
        
        // Смена страницы
        function changePage(direction) {
            currentPage += direction;
            loadDeals();
        }
        
        // Просмотр деталей сделки - ИСПРАВЛЕННАЯ ВЕРСИЯ
        async function viewDealDetails(dealId) {
            try {
                // Показываем индикатор загрузки
                if (tg.showProgress) tg.showProgress();
                
                // Загружаем детали сделки
                const deal = await loadDealDetails(dealId);
                
                if (!deal) {
                    throw new Error('Не удалось загрузить детали сделки');
                }
                
                // Форматируем данные
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
                let statusText = '';
                if (deal.STAGE_ID === 'NEW') statusText = 'Новая';
                else if (deal.STAGE_ID === 'PROCESSING') statusText = 'В работе';
                else if (deal.STAGE_ID === 'CLOSED') statusText = 'Завершена';
                else statusText = deal.STAGE_ID;
                
                // Формируем сообщение для popup
                let message = `<b>Заявка #${deal.ID}</b>\n\n`;
                message += `<b>Клиент:</b> ${deal.TITLE.replace('Заявка от ', '')}\n`;
                message += `<b>Услуги:</b> ${serviceNames}\n`;
                message += `<b>Дата заявки:</b> ${createdDate}\n`;
                message += `<b>Желаемая дата:</b> ${serviceDate}\n`;
                message += `<b>Город:</b> ${deal.UF_CRM_685D2956BF4C8 || '-'}\n`;
                message += `<b>Статус:</b> ${statusText}\n`;
                message += `<b>Исполнитель:</b> ${performerName}\n`;
                
                // Дополнительные поля
                if (deal.UF_CRM_685D2956D0916) message += `<b>Кладбище:</b> ${deal.UF_CRM_685D2956D0916}\n`;
                if (deal.UF_CRM_1751022940) message += `<b>Сектор:</b> ${deal.UF_CRM_1751022940}\n`;
                if (deal.UF_CRM_685D2956D7C70) message += `<b>Ряд:</b> ${deal.UF_CRM_685D2956D7C70}\n`;
                if (deal.UF_CRM_685D2956DF40F) message += `<b>Участок:</b> ${deal.UF_CRM_685D2956DF40F}\n`;
                
                // Комментарии (обрабатываем отдельно)
                if (deal.COMMENTS) {
                    const comments = deal.COMMENTS.length > 200 ? 
                        deal.COMMENTS.substring(0, 200) + '...' : 
                        deal.COMMENTS;
                    message += `\n<b>Комментарии:</b>\n${comments}`;
                }
                
                // Убираем HTML-теги и ограничиваем длину
                const plainMessage = message.replace(/<[^>]*>/g, '');
                const finalMessage = plainMessage.length > 1000 ? 
                    plainMessage.substring(0, 1000) + '...' : 
                    plainMessage;
                
                // Показываем детали в popup
                tg.showPopup({
                    title: `Заявка #${deal.ID}`,
                    message: finalMessage,
                    buttons: [{id: 'close', type: 'close'}]
                });
                
            } catch (error) {
                tg.showPopup({
                    title: 'Ошибка',
                    message: 'Не удалось загрузить детали заявки',
                    buttons: [{id: 'ok', type: 'ok'}]
                });
            } finally {
                if (tg.hideProgress) tg.hideProgress();
            }
        }
        
        function showFallbackView() {
            document.getElementById('user-info').innerHTML = `
                <div class="welcome-text">
                    Для использования приложения откройте его в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>