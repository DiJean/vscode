<script src="/webapp/js/bitrix-integration.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const tg = window.Telegram && Telegram.WebApp;
        const email = localStorage.getItem('userEmail');
        
        if (!email) {
            document.getElementById('requests-list').innerHTML = `
                <div class="request-item">
                    <div class="request-service">Вы еще не создавали заявок</div>
                </div>
            `;
            return;
        }
        
        try {
            // Показываем индикатор загрузки
            document.getElementById('requests-list').innerHTML = `
                <div class="request-item">
                    <div class="request-service">Загрузка ваших заявок...</div>
                </div>
            `;
            
            // Используем функцию из глобального объекта
            const response = await BitrixCRM.getUserRequests(email);
            
            if (response.error) {
                throw new Error(response.message);
            }
            
            const leads = response.result || [];
            let requestsHtml = '';
            
            if (leads.length === 0) {
                requestsHtml = `
                    <div class="request-item">
                        <div class="request-service">У вас пока нет заявок</div>
                    </div>
                `;
            } else {
                leads.forEach(lead => {
                    // Форматируем дату
                    const date = new Date(lead.DATE_CREATE).toLocaleDateString('ru-RU', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                    // Получаем услугу (если есть)
                    const service = lead.UF_CRM_685D2956C64E0 || 'Услуга не указана';
                    
                    requestsHtml += `
                        <div class="request-item">
                            <div class="request-service">${service}</div>
                            <div class="request-date">Создано: ${date}</div>
                            <div class="request-status">Статус: ${formatStatus(lead.STATUS_ID)}</div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('requests-list').innerHTML = requestsHtml;
            
        } catch (error) {
            console.error('Ошибка при загрузке заявок:', error);
            document.getElementById('requests-list').innerHTML = `
                <div class="request-item">
                    <div class="request-service">Ошибка загрузки данных</div>
                    <div class="request-status">${error.message || 'Попробуйте позже'}</div>
                </div>
            `;
        }
    });
    
    // Форматирование статуса заявки
    function formatStatus(status) {
        const statusMap = {
            'NEW': 'Новая',
            'PROCESSED': 'В обработке',
            'FINALIZED': 'Завершена',
            'JUNK': 'Невалидная'
        };
        return statusMap[status] || status;
    }
</script>