const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

const SERVICE_IDS = {
    69: 'Уход',
    71: 'Цветы',
    73: 'Ремонт',
    75: 'Церковная служба'
};
// Функция поиска контакта по телефону
async function findContactByPhone(phone) {
    try {
        const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                filter: {'PHONE': phone},
                select: ['ID', 'NAME', 'LAST_NAME', 'EMAIL']
            })
        });
        
        const data = await response.json();
        return data.result && data.result.length > 0 ? data.result[0] : null;
    } catch (error) {
        console.error('Ошибка поиска контакта:', error);
        return null;
    }
}

// Создание нового контакта
async function createContact(data) {
    try {
        const contactData = {
            fields: {
                NAME: data.firstName,
                LAST_NAME: data.lastName,
                PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}]
            }
        };
        
        const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(contactData)
        });
        
        const result = await response.json();
        return result.result;
    } catch (error) {
        console.error('Ошибка создания контакта:', error);
        return null;
    }
}

// Обновление существующего контакта
async function updateContact(contactId, data) {
    try {
        const contactData = {
            id: contactId,
            fields: {
                NAME: data.firstName,
                LAST_NAME: data.lastName,
                EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}]
            }
        };
        
        const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(contactData)
        });
        
        const result = await response.json();
        return result.result;
    } catch (error) {
        console.error('Ошибка обновления контакта:', error);
        return null;
    }
}

// Создание сделки с обновленными кодами полей
async function createDeal(contactId, data) {
    try {
        const nameParts = data.fullName.split(' ');
        const firstName = nameParts[0] || '';
        const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
        
        const dealData = {
            fields: {
                TITLE: `Заявка от ${data.fullName}`,
                CONTACT_ID: contactId,
                PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                
                // Обновленные пользовательские поля с новыми кодами
                UF_CRM_685D295664A8A: data.serviceDate, // Желаемая дата услуги
                UF_CRM_685D2956BF4C8: data.city,        // Город
                UF_CRM_685D2956C64E0: data.services,    // Услуга
                UF_CRM_685D2956D0916: data.cemetery,    // Кладбище
                UF_CRM_1751022940: data.sector,         // Сектор
                UF_CRM_685D2956D7C70: data.row,         // Ряд
                UF_CRM_685D2956DF40F: data.plot,        // Участок
                
                // Комментарий
                COMMENTS: `Telegram: @${data.username || 'отсутствует'}\n` +
                          `Дополнительная информация: ${data.additionalInfo || 'не указано'}`
            }
        };
        
        const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.add`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dealData)
        });
        
        const result = await response.json();
        return result.result;
    } catch (error) {
        console.error('Ошибка создания сделки:', error);
        return null;
    }
}

// Основная функция обработки заявки
export async function processServiceRequest(data) {
    // Старый код создания лида (закомментирован)
    /*
    const requestData = {
        fields: {
            NAME: firstName,
            LAST_NAME: lastName,
            PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
            EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
            UF_CRM_1749802456: data.serviceDate,
            UF_CRM_1749802469: data.city,
            UF_CRM_1749802574: data.services,
            UF_CRM_1749802612: data.cemetery,
            UF_CRM_1749802619: data.row,
            UF_CRM_1749802630: data.plot,
            UF_CRM_1749802631: data.plotNumber,
            COMMENTS: `Telegram: @${data.username || 'отсутствует'}\n` +
                      `Дополнительная информация: ${data.additionalInfo || 'не указано'}`
        }
    };
    
    return fetch(`${BITRIX_WEBHOOK}crm.lead.add`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(requestData),
    });
    */
    
    // Новый код работы с контактом и сделкой
    try {
        // Поиск существующего контакта
        const existingContact = await findContactByPhone(data.phone);
        let contactId;
        
        if (existingContact) {
            // Обновление существующего контакта
            contactId = existingContact.ID;
            await updateContact(contactId, data);
        } else {
            // Создание нового контакта
            contactId = await createContact(data);
        }
        
        if (!contactId) {
            throw new Error('Не удалось создать/обновить контакт');
        }
        
        // Создание сделки с новыми кодами полей
        const dealId = await createDeal(contactId, {
            ...data,
            sector: data.sector || '' // Добавляем поле сектора
        });
        
        if (!dealId) {
            throw new Error('Не удалось создать сделку');
        }
        
        return { success: true, contactId, dealId };
        
    } catch (error) {
        console.error('Ошибка обработки заявки:', error);
        return { success: false, error: error.message };
    }
}

// Получение списка заявок пользователя (оставляем без изменений)
export async function getUserRequests(email) {
    const filter = {
        filter: {'EMAIL': email},
        select: [
            'ID', 'TITLE', 'DATE_CREATE', 'STATUS_ID', 
            'UF_CRM_1749802456', 'UF_CRM_1749802469',
            'UF_CRM_1749802574', 'COMMENTS'
        ],
        order: { "DATE_CREATE": "DESC" }
    };
    
    return fetch(`${BITRIX_WEBHOOK}crm.lead.list`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(filter),
    }).then(response => response.json());
}