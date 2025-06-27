(function() {
    console.log('[BitrixCRM] Начало загрузки модуля CRM');
    
    const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
    
    // Функция создания заявки
    async function createServiceRequest(data) {
        console.log('[BitrixCRM] Создание заявки', data);
        try {
            // Разделяем полное имя на компоненты
            const nameParts = (data.fullName || '').split(' ');
            const firstName = nameParts[0] || '';
            const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
            
            // Формируем данные для Bitrix24
            const requestData = {
                fields: {
                    TITLE: `Запрос на услугу: ${data.service}`,
                    NAME: firstName,
                    LAST_NAME: lastName,
                    PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                    EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                    UF_CRM_685D295664A8A: data.serviceDate,
                    UF_CRM_685D2956BF4C8: data.city,
                    UF_CRM_685D2956C64E0: data.service,
                    UF_CRM_685D2956D0916: data.cemetery,
                    UF_CRM_1751022940: data.sector,
                    UF_CRM_685D2956D7C70: data.row,
                    UF_CRM_685D2956DF40F: data.plot,
                    COMMENTS: data.comments || 'Дополнительная информация не указана'
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.add`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(requestData),
            });
            
            const result = await response.json();
            console.log('[BitrixCRM] Результат создания заявки', result);
            return result;
        } catch (error) {
            console.error('[BitrixCRM] Ошибка при создании заявки:', error);
            return { error: true, message: error.message };
        }
    }

    // Функция получения заявок пользователя
    async function getUserRequests(email) {
        console.log('[BitrixCRM] Получение заявок для', email);
        try {
            const filter = {
                filter: {'EMAIL': email},
                select: ['ID', 'TITLE', 'DATE_CREATE', 'STATUS_ID', 'UF_CRM_685D2956C64E0'],
                order: { "DATE_CREATE": "DESC" }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.list`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(filter),
            });
            
            const result = await response.json();
            console.log('[BitrixCRM] Результат получения заявок', result);
            return result;
        } catch (error) {
            console.error('[BitrixCRM] Ошибка при получении заявок:', error);
            return { error: true, message: error.message };
        }
    }

    // Экспорт функций
    window.BitrixCRM = {
        createServiceRequest,
        getUserRequests
    };

    console.log('[BitrixCRM] Модуль успешно загружен');
    document.dispatchEvent(new CustomEvent('BitrixCRMLoaded'));
})();