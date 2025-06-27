const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

// Отправка новой заявки
async function createServiceRequest(data) {
    // Разделяем полное имя на компоненты
    const nameParts = (data.fullName || '').split(' ');
    const firstName = nameParts[0] || '';
    const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
    
    // Формируем данные для Bitrix24
    const requestData = {
        fields: {
            // Основные поля
            TITLE: `Запрос на услугу: ${data.service}`,
            NAME: firstName,
            LAST_NAME: lastName,
            PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
            EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
            
            // Пользовательские поля
            UF_CRM_685D295664A8A: data.serviceDate, // Желаемая дата услуги
            UF_CRM_685D2956BF4C8: data.city,        // Город
            UF_CRM_685D2956C64E0: data.service,     // Услуга
            UF_CRM_685D2956D0916: data.cemetery,    // Кладбище
            UF_CRM_1751022940: data.sector,         // Сектор
            UF_CRM_685D2956D7C70: data.row,         // Ряд
            UF_CRM_685D2956DF40F: data.plot,        // Участок
            
            // Комментарий
            COMMENTS: data.comments || 'Дополнительная информация не указана'
        }
    };
    
    try {
        const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.add`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(requestData),
        });
        return await response.json();
    } catch (error) {
        console.error('Ошибка при создании заявки:', error);
        return { error: true, message: error.message };
    }
}

// Получение списка заявок пользователя
async function getUserRequests(email) {
    const filter = {
        filter: {'EMAIL': email},
        select: [
            'ID', 'TITLE', 'DATE_CREATE', 'STATUS_ID', 
            'UF_CRM_685D295664A8A', 'UF_CRM_685D2956BF4C8',
            'UF_CRM_685D2956C64E0', 'COMMENTS'
        ],
        order: { "DATE_CREATE": "DESC" }
    };
    
    try {
        const response = await fetch(`${BITRIX_WEBHOOK}crm.lead.list`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(filter),
        });
        return await response.json();
    } catch (error) {
        console.error('Ошибка при получении заявок:', error);
        return { error: true, message: error.message };
    }
}

// Экспорт функций для глобального доступа
window.BitrixCRM = {
    createServiceRequest,
    getUserRequests
};