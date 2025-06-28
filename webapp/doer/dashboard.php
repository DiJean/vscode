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
    </div>

    <script type="module">
        import { initTelegramApp, getUserData } from '../js/telegram-api.js';
        import { BITRIX_WEBHOOK, findPerformerByTgId } from '../js/bitrix-integration.js';
        
        // Основной модуль приложения
        const DashboardApp = (() => {
            // Приватные переменные
            let tg = null;
            let user = null;
            let currentPage = 1;
            const pageSize = 10;
            let contactId = null;

            // Публичные методы
            return {
                async init() {
                    try {
                        // Инициализация Telegram WebApp
                        tg = await initTelegramApp();
                        user = getUserData(tg);
                        
                        // Проверка регистрации исполнителя
                        await this.checkPerformerRegistration();
                        
                        // Отображение информации о пользователе
                        this.renderUserInfo();
                        
                        // Загрузка сделок
                        await this.loadDeals();
                        
                        // Настройка обработчиков событий
                        this.setupEventListeners();
                        
                    } catch (error) {
                        console.error('Ошибка инициализации:', error);
                        this.showFallbackView();
                    }
                },
                
                async checkPerformerRegistration() {
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
                        throw new Error('Performer not registered');
                    }
                    contactId = performerContact.ID;
                },
                
                renderUserInfo() {
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
                },
                
                async loadDeals() {
                    try {
                        // Показать индикатор загрузки
                        this.showLoading();
                        
                        const status = document.getElementById('status-filter').value;
                        const search = document.getElementById('search').value;
                        
                        // Запрос данных
                        const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.list`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                filter: this.buildFilter(status, search),
                                select: this.getDealFields(),
                                order: {'DATE_CREATE': 'DESC'},
                                start: (currentPage - 1) * pageSize
                            })
                        });
                        
                        const data = await response.json();
                        this.renderDeals(data.result || []);
                        this.updatePagination(data.total || 0);
                        
                    } catch (error) {
                        console.error('Ошибка загрузки сделок:', error);
                        this.showError();
                    }
                },
                
                buildFilter(status, search) {
                    const filter = {
                        'UF_CRM_1751128612': contactId
                    };
                    
                    if (status) filter['STAGE_ID'] = status;
                    if (search) filter['%TITLE'] = search;
                    
                    return filter;
                },
                
                getDealFields() {
                    return [
                        'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID',
                        'UF_CRM_685D295664A8A',
                        'UF_CRM_685D2956BF4C8',
                        'UF_CRM_685D2956C64E0'
                    ];
                },
                
                showLoading() {
                    document.getElementById('deals-list').innerHTML = `
                        <tr>
                            <td colspan="8" class="loading">Загрузка данных...</td>
                        </tr>
                    `;
                },
                
                showError() {
                    document.getElementById('deals-list').innerHTML = `
                        <tr>
                            <td colspan="8" class="error">Ошибка загрузки данных</td>
                        </tr>
                    `;
                },
                
                renderDeals(deals) {
                    const dealsList = document.getElementById('deals-list');
                    
                    if (deals.length === 0) {
                        dealsList.innerHTML = `
                            <tr>
                                <td colspan="8" class="empty">Заявок не найдено</td>
                            </tr>
                        `;
                        return;
                    }
                    
                    dealsList.innerHTML = deals.map(deal => this.createDealRow(deal)).join('');
                    this.setupDealButtons();
                },
                
                createDealRow(deal) {
                    const createdDate = new Date(deal.DATE_CREATE).toLocaleDateString();
                    const serviceDate = deal.UF_CRM_685D295664A8A || '-';
                    const serviceNames = this.getServiceNames(deal.UF_CRM_685D2956C64E0);
                    const status = this.getStatusInfo(deal.STAGE_ID);
                    
                    return `
                        <tr>
                            <td>${deal.ID}</td>
                            <td>${deal.TITLE.replace('Заявка от ', '')}</td>
                            <td>${serviceNames}</td>
                            <td>${createdDate}</td>
                            <td>${serviceDate}</td>
                            <td>${deal.UF_CRM_685D2956BF4C8 || '-'}</td>
                            <td><span class="status ${status.class}">${status.text}</span></td>
                            <td>
                                <button class="action-btn view-btn" data-id="${deal.ID}">Просмотр</button>
                            </td>
                        </tr>
                    `;
                },
                
                getServiceNames(serviceIdsString) {
                    if (!serviceIdsString) return '';
                    
                    const serviceIds = serviceIdsString.split(',');
                    return serviceIds.map(id => {
                        switch (id) {
                            case '69': return 'Уход';
                            case '71': return 'Цветы';
                            case '73': return 'Ремонт';
                            case '75': return 'Церковная служба';
                            default: return id;
                        }
                    }).join(', ');
                },
                
                getStatusInfo(statusId) {
                    switch (statusId) {
                        case 'NEW':
                            return { text: 'Новая', class: 'status-new' };
                        case 'PROCESSING':
                            return { text: 'В работе', class: 'status-processing' };
                        case 'CLOSED':
                            return { text: 'Завершена', class: 'status-closed' };
                        default:
                            return { text: statusId, class: '' };
                    }
                },
                
                setupDealButtons() {
                    document.querySelectorAll('.view-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const dealId = btn.getAttribute('data-id');
                            this.viewDealDetails(dealId);
                        });
                    });
                },
                
                updatePagination(totalItems) {
                    const totalPages = Math.ceil(totalItems / pageSize);
                    const prevBtn = document.getElementById('prev-page');
                    const nextBtn = document.getElementById('next-page');
                    const currentPageEl = document.getElementById('current-page');
                    
                    currentPageEl.textContent = currentPage;
                    
                    prevBtn.disabled = currentPage === 1;
                    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
                },
                
                changePage(direction) {
                    currentPage += direction;
                    this.loadDeals();
                },
                
                viewDealDetails(dealId) {
                    tg.showPopup({
                        title: 'Детали заявки',
                        message: `Загрузка информации о заявке #${dealId}...`,
                        buttons: []
                    });
                },
                
                setupEventListeners() {
                    document.getElementById('refresh-btn').addEventListener('click', () => this.loadDeals());
                    document.getElementById('prev-page').addEventListener('click', () => this.changePage(-1));
                    document.getElementById('next-page').addEventListener('click', () => this.changePage(1));
                    document.getElementById('status-filter').addEventListener('change', () => this.loadDeals());
                    document.getElementById('search').addEventListener('input', () => this.loadDeals());
                },
                
                showFallbackView() {
                    document.getElementById('user-info').innerHTML = `
                        <div class="welcome-text">
                            Для использования приложения откройте его в Telegram
                        </div>
                    `;
                }
            };
        })();

        // Инициализация приложения после загрузки DOM
        document.addEventListener('DOMContentLoaded', () => DashboardApp.init());
    </script>
</body>
</html>