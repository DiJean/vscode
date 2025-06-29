<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд исполнителя</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Наши стили -->
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/dashboard.css">
    
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .status-new {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-processing {
            background: rgba(0, 123, 255, 0.2);
            color: #fff;
        }
        
        .status-closed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .action-btn {
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        
        /* Новые стили */
        .deals-container {
            overflow-x: auto;
        }
        
        #deals-table a {
            color: white;
            transition: all 0.2s;
        }
        
        #deals-table a:hover {
            text-decoration: underline;
            opacity: 0.9;
        }
        
        #deals-table th, 
        #deals-table td {
            white-space: nowrap;
            min-width: 80px;
        }
        
        #deals-table td:first-child,
        #deals-table td:last-child {
            min-width: 60px;
        }
        
        /* Убедимся, что статус виден */
        .status-cell {
            min-width: 100px;
        }
        
        /* Исправление для table-responsive */
        .table-responsive {
            overflow: visible !important;
        }
        
        /* Скрываем менее важные колонки на мобильных */
        @media (max-width: 768px) {
            #deals-table th:nth-child(4),
            #deals-table td:nth-child(4), /* Создана */
            #deals-table th:nth-child(5),
            #deals-table td:nth-child(5) { /* Исполнение */
                display: none;
            }
            
            /* Увеличим ширину для статуса */
            .status-cell {
                min-width: 80px;
            }
            
            /* Фиксируем колонку действий */
            #deals-table td:last-child {
                position: sticky;
                right: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Мои заявки</h1>
            <div class="d-flex align-items-center" id="user-info">
                <div class="spinner-border spinner-border-sm text-light me-2" role="status"></div>
                Загрузка...
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <select class="form-select" id="status-filter">
                    <option value="">Все статусы</option>
                    <option value="NEW">Новый заказ</option>
                    <option value="PREPARATION">Подготовка</option>
                    <option value="PREPAYMENT_INVOICE">Оплата</option>
                    <option value="EXECUTING">В работе</option>
                    <option value="WON">Успешно завершена</option>
                    <option value="LOSE">Не нашли участок</option>
                    <option value="APOLOGY">Анализ неудачи</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" id="search" placeholder="Поиск по клиенту или услуге...">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" id="refresh-btn">Обновить</button>
            </div>
        </div>
        
        <div class="deals-container">
            <!-- УБРАН overflow-hidden ИЗ table-responsive -->
            <div class="table-responsive rounded-3">
                <table class="table table-hover align-middle mb-0" id="deals-table">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Клиент</th>
                            <th>Услуги</th>
                            <th>Создана</th>
                            <th>Исполнение</th>
                            <th>Город</th>
                            <th class="status-cell">Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="deals-list">
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="d-flex justify-content-center align-items-center mt-4" id="pagination">
            <button class="btn btn-outline-light me-2" id="prev-page" disabled>← Назад</button>
            <span class="mx-3" id="current-page">1</span>
            <button class="btn btn-outline-light ms-2" id="next-page">Вперед →</button>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
        
        let tg = null;
        let user = null;
        let currentPage = 1;
        const pageSize = 10;
        let contactId = null;
        let performerName = "";
        let performerCity = null;

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

        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            tg = Telegram.WebApp;
            
            try {
                user = tg.initDataUnsafe?.user || {};
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
                
                const firstName = user.first_name || '';
                const lastName = user.last_name || '';
                const fullName = `${firstName} ${lastName}`.trim();
                
                document.getElementById('user-info').innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="avatar me-2">
                            ${user.photo_url ? 
                                `<img src="${user.photo_url}" alt="${fullName}" class="img-fluid rounded-circle" style="width:40px;height:40px;object-fit:cover;">` : 
                                `<div class="d-flex align-items-center justify-content-center rounded-circle bg-light text-dark fw-bold" style="width:40px;height:40px;">${firstName.charAt(0) || 'И'}</div>`
                            }
                        </div>
                        <div>
                            <div class="user-name">${fullName || 'Исполнитель'}</div>
                            <div class="small text-muted">${performerCity}</div>
                        </div>
                    </div>
                `;
                
                loadDeals();
                
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
        
        async function loadDeals() {
            try {
                document.getElementById('deals-list').innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </td>
                    </tr>
                `;
                
                const status = document.getElementById('status-filter').value;
                const search = document.getElementById('search').value;
                
                const filter = {'UF_CRM_1751128612': contactId};
                if (status) filter['STAGE_ID'] = status;
                if (search) filter['%TITLE'] = search;
                
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
                renderDeals(data.result || []);
                updatePagination(data.total || 0);
                
            } catch (error) {
                console.error('Ошибка загрузки сделок:', error);
                document.getElementById('deals-list').innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">Ошибка загрузки данных</td>
                    </tr>
                `;
            }
        }
        
        function renderDeals(deals) {
            const dealsList = document.getElementById('deals-list');
            
            if (!deals || deals.length === 0) {
                dealsList.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">Заявок не найдено</td>
                    </tr>
                `;
                return;
            }
            
            dealsList.innerHTML = '';
            
            deals.forEach(deal => {
                const createdDate = new Date(deal.DATE_CREATE).toLocaleDateString();
                const serviceDate = deal.UF_CRM_685D295664A8A ? new Date(deal.UF_CRM_685D295664A8A).toLocaleDateString() : '-';
                
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
                        if (id === '75') return 'Церковная';
                        return id;
                    }).join(', ');
                }
                
                let statusClass = '';
                let statusText = deal.STAGE_ID || '';
                
                // СООТВЕТСТВИЕ СТАТУСОВ
                if (statusText === 'NEW') {
                    statusText = 'Новый заказ';
                    statusClass = 'status-new';
                } else if (statusText === 'PREPARATION') {
                    statusText = 'Подготовка';
                    statusClass = 'status-processing';
                } else if (statusText === 'PREPAYMENT_INVOICE') {
                    statusText = 'Оплата';
                    statusClass = 'status-processing';
                } else if (statusText === 'EXECUTING') {
                    statusText = 'В работе';
                    statusClass = 'status-processing';
                } else if (statusText === 'WON') {
                    statusText = 'Успешно завершена';
                    statusClass = 'status-closed';
                } else if (statusText === 'LOSE') {
                    statusText = 'Не нашли участок';
                    statusClass = 'status-closed';
                } else if (statusText === 'APOLOGY') {
                    statusText = 'Анализ неудачи';
                    statusClass = 'status-closed';
                }
                
                // СДЕЛАЕМ ID И КНОПКУ ПРОСМОТРА КЛИКАБЕЛЬНЫМИ
                dealsList.innerHTML += `
                    <tr>
                        <td>
                            <a href="deal-details.php?id=${deal.ID}" 
                               class="text-white text-decoration-none fw-bold">
                                ${deal.ID}
                            </a>
                        </td>
                        <td>${deal.TITLE.replace('Заявка от ', '')}</td>
                        <td>${serviceNames}</td>
                        <td>${createdDate}</td>
                        <td>${serviceDate}</td>
                        <td>${deal.UF_CRM_685D2956BF4C8 || '-'}</td>
                        <td class="status-cell">
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </td>
                        <td>
                            <a href="deal-details.php?id=${deal.ID}" 
                               class="btn btn-sm btn-primary action-btn">
                                Просмотр
                            </a>
                        </td>
                    </tr>
                `;
            });
        }
        
        function updatePagination(totalItems) {
            const totalPages = Math.ceil(totalItems / pageSize);
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const currentPageEl = document.getElementById('current-page');
            
            currentPageEl.textContent = currentPage;
            
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
        }
        
        function changePage(direction) {
            currentPage += direction;
            loadDeals();
        }
        
        function showFallbackView() {
            document.getElementById('user-info').innerHTML = `
                <div class="text-muted">
                    Для использования приложения откройте его в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>