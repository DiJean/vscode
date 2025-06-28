// bitrix-integration.js
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

// Создание сделки
async function createDeal(contactId, data) {
    try {
        const dealData = {
            fields: {
                TITLE: `Заявка от ${data.fullName}`,
                CONTACT_ID: contactId,
                PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                
                // Пользовательские поля
                UF_CRM_685D295664A8A: data.serviceDate,
                UF_CRM_685D2956BF4C8: data.city,
                UF_CRM_685D2956C64E0: data.services,
                UF_CRM_685D2956D0916: data.cemetery,
                UF_CRM_1751022940: data.sector,
                UF_CRM_685D2956D7C70: data.row,
                UF_CRM_685D2956DF40F: data.plot,
                
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
        
        // Создание сделки
        const dealId = await createDeal(contactId, {
            ...data,
            sector: data.sector || ''
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

// Получение списка заявок пользователя
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

// Поиск исполнителя по Telegram ID
export async function findPerformerByTgId(tgId) {
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