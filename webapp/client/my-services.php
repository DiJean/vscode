<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои услуги</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/webapp/css/style.css">
    <link rel="stylesheet" href="/webapp/css/my-services.css">
    <style>
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .services-table th {
            background: rgba(106, 17, 203, 0.5);
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .services-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .services-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        .status-new { background: rgba(106, 17, 203, 0.5); }
        .status-in-progress { background: rgba(37, 117, 252, 0.5); }
        .status-completed { background: rgba(40, 167, 69, 0.5); }
        .status-cancelled { background: rgba(220, 53, 69, 0.5); }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting" id="greeting">Мои услуги</div>
        <div id="user-container"></div>
        
        <div class="services-list" id="services-list">
            <div class="no-services">Загрузка ваших заказов...</div>
        </div>
        
        <button class="new-request-btn" onclick="location.href='client-form.php'">
            Создать новый заказ
        </button>
    </div>

    <script src="../js/telegram-api.js"></script>
    <script src="../js/bitrix-integration.js"></script>
    <script>
        // Соответствие ID услуг и их названий
        const SERVICE_NAMES = {
            "69": "Уход",
            "71": "Цветы",
            "73": "Ремонт",
            "75": "Церковная служба"
        };
        
        let tg = null;

        async function initApp() {
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                showFallbackView();
                return;
            }
            
            tg = Telegram.WebApp;
            
            try {
                if (tg.BackButton) {
                    tg.BackButton.show();
                    tg.BackButton.onClick(() => {
                        localStorage.removeItem('selectedRole');
                        window.location.href = '../index.php';
                    });
                }
                
                const clientEmail = localStorage.getItem('clientEmail');
                if (!clientEmail) {
                    showNoServicesView('Для просмотра заказов оформите первый заказ');
                    return;
                }
                
                loadUserDeals(clientEmail);
                
            } catch (e) {
                console.error('Ошибка инициализации:', e);
                showFallbackView();
            }
        }
        
        async function loadUserDeals(email) {
            try {
                if (tg?.showProgress) tg.showProgress();
                
                const response = await getUserDeals(email);
                
                if (tg?.hideProgress) tg.hideProgress();
                
                if (response.result && response.result.length > 0) {
                    renderDealsTable(response.result);
                } else {
                    showNoServicesView('У вас пока нет активных заказов');
                }
            } catch (error) {
                console.error('Ошибка при загрузке заказов:', error);
                showNoServicesView('Ошибка при загрузке заказов');
            }
        }
        
        function renderDealsTable(deals) {
            const servicesList = document.getElementById('services-list');
            servicesList.innerHTML = '';
            
            const table = document.createElement('table');
            table.className = 'services-table';
            
            // Заголовки таблицы
            const headerRow = table.insertRow();
            const headers = ['ID', 'Название', 'Дата создания', 'Дата услуги', 'Статус', 'Город', 'Услуги'];
            
            headers.forEach(headerText => {
                const th = document.createElement('th');
                th.textContent = headerText;
                headerRow.appendChild(th);
            });
            
            // Строки с данными
            deals.forEach(deal => {
                const row = table.insertRow();
                
                // ID
                const idCell = row.insertCell();
                idCell.textContent = deal.ID;
                
                // Название
                const titleCell = row.insertCell();
                titleCell.textContent = deal.TITLE;
                
                // Дата создания
                const createdCell = row.insertCell();
                createdCell.textContent = new Date(deal.DATE_CREATE).toLocaleDateString();
                
                // Дата услуги
                const serviceDateCell = row.insertCell();
                serviceDateCell.textContent = deal.UF_CRM_1749802456 
                    ? new Date(deal.UF_CRM_1749802456).toLocaleDateString() 
                    : '-';
                
                // Статус
                const statusCell = row.insertCell();
                const statusBadge = document.createElement('span');
                statusBadge.className = `status-badge ${getStatusClass(deal.STAGE_ID)}`;
                statusBadge.textContent = getStatusText(deal.STAGE_ID);
                statusCell.appendChild(statusBadge);
                
                // Город
                const cityCell = row.insertCell();
                cityCell.textContent = deal.UF_CRM_1749802469 || '-';
                
                // Услуги
                const servicesCell = row.insertCell();
                let servicesText = '-';
                
                if (deal.UF_CRM_1749802574) {
                    // Преобразуем ID в названия
                    servicesText = deal.UF_CRM_1749802574
                        .map(id => SERVICE_NAMES[id] || `Неизвестная услуга (${id})`)
                        .join(', ');
                }
                servicesCell.textContent = servicesText;
            });
            
            servicesList.appendChild(table);
        }
        
        function getStatusClass(status) {
            switch(status) {
                case 'NEW': return 'status-new';
                case 'PROCESSING': return 'status-in-progress';
                case 'FINAL_INVOICE': return 'status-in-progress';
                case 'WON': return 'status-completed';
                case 'LOSE': return 'status-cancelled';
                default: return '';
            }
        }
        
        function getStatusText(status) {
            switch(status) {
                case 'NEW': return 'Новая';
                case 'PROCESSING': return 'В работе';
                case 'FINAL_INVOICE': return 'Оплата';
                case 'WON': return 'Завершена';
                case 'LOSE': return 'Отменена';
                default: return status;
            }
        }
        
        function showNoServicesView(message) {
            document.getElementById('services-list').innerHTML = `
                <div class="no-services">
                    <p>${message}</p>
                </div>
            `;
        }
        
        function showFallbackView() {
            document.getElementById('greeting').textContent = 'Мои услуги';
            document.getElementById('user-container').innerHTML = `
                <div class="welcome-text">
                    Для просмотра услуг откройте приложение в Telegram
                </div>
            `;
        }
        
        document.addEventListener('DOMContentLoaded', initApp);
    </script>
</body>
</html>
