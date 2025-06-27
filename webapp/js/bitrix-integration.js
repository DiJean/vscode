// Убедимся, что код не содержит синтаксических ошибок
const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

// Основной объект для функций Bitrix
window.bitrixFunctions = {
    // Создание контакта
    createContact: async function(userData) {
        try {
            const contactData = {
                fields: {
                    NAME: userData.first_name || 'Клиент',
                    LAST_NAME: userData.last_name || 'Без фамилии',
                    OPENED: "Y",
                    TYPE_ID: "CLIENT",
                    PHONE: userData.phone ? [{VALUE: userData.phone, VALUE_TYPE: 'WORK'}] : [],
                    EMAIL: userData.email ? [{VALUE: userData.email, VALUE_TYPE: 'WORK'}] : [],
                    SOURCE_ID: "TELEGRAM_WEBAPP",
                    UTM_SOURCE: "Telegram WebApp"
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(contactData),
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                return { 
                    error: true, 
                    error_description: errorData.error_description || 'Ошибка создания контакта'
                };
            }
            
            return await response.json();
        } catch (error) {
            return { 
                error: true, 
                error_description: error.message 
            };
        }
    },
    
    // Создание сделки
    createDeal: async function(contactId, title = 'Новая заявка') {
        try {
            const dealData = {
                fields: {
                    TITLE: title,
                    CONTACT_ID: contactId,
                    TYPE_ID: "SALE",
                    STAGE_ID: "NEW",
                    OPENED: "Y",
                    SOURCE_ID: "TELEGRAM_WEBAPP",
                    UTM_SOURCE: "Telegram WebApp"
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.add`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dealData),
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                return { 
                    error: true, 
                    error_description: errorData.error_description || 'Ошибка создания сделки'
                };
            }
            
            return await response.json();
        } catch (error) {
            return { 
                error: true, 
                error_description: error.message 
            };
        }
    },
    
    // Обновление сделки
    updateDeal: async function(dealId, data) {
        try {
            const dealData = {
                id: dealId,
                fields: {
                    UF_CRM_685D295664A8A: data.serviceDate,
                    UF_CRM_685D2956BF4C8: data.city,
                    UF_CRM_685D2956C64E0: data.services,
                    UF_CRM_685D2956D0916: data.cemetery,
                    UF_CRM_685D2956D7C70: data.row,
                    UF_CRM_685D2956DF40F: data.plot,
                    COMMENTS: `Telegram: @${data.username}\n` +
                              `Дополнительная информация: ${data.additionalInfo || 'не указано'}`
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.update`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dealData),
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                return { 
                    error: true, 
                    error_description: errorData.error_description || 'Ошибка обновления сделки'
                };
            }
            
            return await response.json();
        } catch (error) {
            return { 
                error: true, 
                error_description: error.message 
            };
        }
    },
    
    // Получение сделок пользователя
    getUserDeals: async function(email) {
        try {
            const contactResponse = await fetch(`${BITRIX_WEBHOOK}crm.contact.list`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    filter: {'EMAIL': email},
                    select: ['ID']
                }),
            }).then(response => response.json());
            
            if (!contactResponse.result || contactResponse.result.length === 0) {
                return {result: []};
            }
            
            const contactId = contactResponse.result[0].ID;
            
            const dealResponse = await fetch(`${BITRIX_WEBHOOK}crm.deal.list`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    filter: {'CONTACT_ID': contactId},
                    select: ['ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID', 
                             'UF_CRM_685D295664A8A', 'UF_CRM_685D2956BF4C8',
                             'UF_CRM_685D2956C64E0', 'COMMENTS'],
                    order: {"DATE_CREATE": "DESC"}
                }),
            });
            
            return await dealResponse.json();
        } catch (error) {
            return {result: []};
        }
    }
};

console.log("Bitrix integration module loaded");
