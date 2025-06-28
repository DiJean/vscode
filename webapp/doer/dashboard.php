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
                        <th>Клиент</th>
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
        let performerCity = null;

        // Поиск исполнителя по Telegram ID
        async function findPerformerByTgId(tgId) {
            try {
                const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filter: {'UF_CRM_1751128872': String(tgId)},
                        select: ['ID', 'UF_CRM_685D2956061DB']
                    })
                });
                
                const data = await response.json();
                return data.result && data.result.length > 0 ? data.result[0] : null;
            } catch (error) {
                console.error('Ошибка поиска исполнителя:', error);
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
                console.log('Данные пользователя Telegram:', user);
                
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
                            'UF_CRM_685D2956C64E0',
                            'UF_CRM_1751128612'
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
                
                // Информация о назначенном исполнителе
                const performerInfo = deal.UF_CRM_1751128612 ? 
                    `Исполнитель: ${deal.UF_CRM_1751128612}` : 
                    'Исполнитель не назначен';
                
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
                            <div class="debug-info">${performerInfo}</div>
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
        
        // Просмотр деталей сделки
        function viewDealDetails(dealId) {
            tg.showPopup({
                title: 'Детали заявки',
                message: `Загрузка информации о заявке #${dealId}...`,
                buttons: []
            });
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