(function() {
    // Используем глобальную переменную, установленную PHP
    const BITRIX_WEBHOOK = window.BITRIX_WEBHOOK || 'https://b24-saiczd.bitrix24.ru/rest/1/5sjww0g09qa2cc0u/';
    
    // Словарь для преобразования ID услуг в названия
    const serviceNames = {
        '69': 'Уход',
        '71': 'Цветы',
        '73': 'Ремонт',
        '75': 'Церковная служба',
        '77': 'Установка памятника',
        '79': 'Благоустройство'
    };

    async function findContactByTgId(tgId) {
        try {
            const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list.json`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    filter: {'UF_CRM_1751128872': String(tgId)},
                    select: ['ID']
                })
            });
            
            const data = await response.json();
            return data.result && data.result.length > 0 ? data.result[0] : null;
        } catch (error) {
            console.error('Ошибка поиска контакта:', error);
            return null;
        }
    }

    async function getDealsByContactId(contactId) {
        try {
            const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.list.json`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    filter: {'CONTACT_ID': contactId},
                    select: [
                        'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID',
                        'UF_CRM_685D295664A8A',
                        'UF_CRM_685D2956BF4C8',
                        'UF_CRM_685D2956C64E0',
                        'UF_CRM_1751128612' // ID исполнителя
                    ],
                    order: { "DATE_CREATE": "DESC" }
                })
            });
            
            const data = await response.json();
            
            // Преобразуем ID услуг в названия
            if (data.result) {
                data.result.forEach(deal => {
                    deal.services = mapServices(deal.UF_CRM_685D2956C64E0);
                });
            }
            
            return data.result || [];
        } catch (error) {
            console.error('Ошибка загрузки сделок:', error);
            return [];
        }
    }

    // Функция преобразования ID услуг в названия
    function mapServices(serviceField) {
        if (!serviceField) return 'Услуга не указана';
        
        let serviceIds = [];
        if (Array.isArray(serviceField)) {
            serviceIds = serviceField;
        } else if (typeof serviceField === 'string') {
            serviceIds = serviceField.split(',');
        } else {
            serviceIds = [String(serviceField)];
        }
        
        return serviceIds.map(id => 
            serviceNames[id] || `Услуга #${id}`
        ).join(', ');
    }

    async function getPerformersInfo(performerIds) {
        try {
            // Уникальные ID исполнителей
            const uniqueIds = [...new Set(performerIds)];
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.list.json`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    filter: { 'ID': uniqueIds },
                    select: ['ID', 'NAME', 'LAST_NAME']
                })
            });
            
            const data = await response.json();
            return data.result || [];
        } catch (error) {
            console.error('Ошибка загрузки исполнителей:', error);
            return [];
        }
    }

    async function getDealDetails(dealId) {
        try {
            const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.get.json`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    id: dealId,
                    select: [
                        'ID', 'TITLE', 'DATE_CREATE', 'STAGE_ID', 'COMMENTS',
                        'UF_CRM_685D295664A8A', // Желаемая дата услуги
                        'UF_CRM_685D2956BF4C8', // Город
                        'UF_CRM_685D2956C64E0', // Услуги
                        'UF_CRM_685D2956D0916', // Кладбище
                        'UF_CRM_1751022940',    // Сектор
                        'UF_CRM_685D2956D7C70', // Ряд
                        'UF_CRM_685D2956DF40F', // Участок
                        'UF_CRM_1751128612',    // Исполнитель
                        'UF_CRM_1751200529',     // Фото до
                        'UF_CRM_1751200549'      // Фото после
                    ]
                })
            });
            
            const data = await response.json();
            
            if (data.result) {
                const deal = data.result;
                deal.services = mapServices(deal.UF_CRM_685D2956C64E0);
                return deal;
            }
            
            return null;
        } catch (error) {
            console.error('Ошибка получения деталей заявки:', error);
            return null;
        }
    }

    async function getUserRequests(tgUserId) {
        try {
            const contact = await findContactByTgId(tgUserId);
            if (!contact) return [];
            
            return await getDealsByContactId(contact.ID);
        } catch (error) {
            console.error('Ошибка получения заявок:', error);
            return [];
        }
    }

    async function createContact(data) {
        try {
            const contactData = {
                fields: {
                    NAME: data.firstName,
                    LAST_NAME: data.lastName,
                    PHONE: [{VALUE: data.phone, VALUE_TYPE: 'WORK'}],
                    EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                    TYPE_ID: 'CLIENT',
                    SOURCE_ID: 'REPEAT_SALE',
                    UF_CRM_1751128872: String(data.tgUserId)
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.add.json`, {
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

    async function updateContact(contactId, data) {
        try {
            const contactData = {
                id: contactId,
                fields: {
                    NAME: data.firstName,
                    LAST_NAME: data.lastName,
                    EMAIL: [{VALUE: data.email, VALUE_TYPE: 'WORK'}],
                    UF_CRM_1751128872: String(data.tgUserId)
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.contact.update.json`, {
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

    async function createDeal(contactId, data) {
        try {
            const dealData = {
                fields: {
                    TITLE: `Заявка от ${data.fullName}`,
                    CONTACT_ID: contactId,
                    PHONE: [{VALUE: data.phone, VALUE_TYPE: 'MOBILE'}],
                    EMAIL: [{VALUE: data.email, VALUE_TYPE: 'MOBILE'}],
                    UF_CRM_1751128872: String(data.tgUserId),
                    UF_CRM_685D295664A8A: data.serviceDate,
                    UF_CRM_685D2956BF4C8: data.city,
                    UF_CRM_685D2956C64E0: data.services,
                    UF_CRM_685D2956D0916: data.cemetery,
                    UF_CRM_1751022940: data.sector,
                    UF_CRM_685D2956D7C70: data.row,
                    UF_CRM_685D2956DF40F: data.plot,
                    COMMENTS: `Telegram: @${data.username || 'отсутствует'}\n` +
                              `Дополнительная информация: ${data.additionalInfo || 'не указано'}`
                }
            };
            
            const response = await fetch(`${BITRIX_WEBHOOK}crm.deal.add.json`, {
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

    async function processServiceRequest(data) {
        try {
            const phoneRegex = /^7\d{10}$/;
            if (!phoneRegex.test(data.phone)) {
                throw new Error('Некорректный номер телефона');
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                throw new Error('Некорректный email');
            }
            
            if (!data.services || data.services.length === 0) {
                throw new Error('Не выбрано ни одной услуги');
            }
            
            const existingContact = await findContactByTgId(data.tgUserId);
            let contactId;
            
            if (existingContact) {
                contactId = existingContact.ID;
                await updateContact(contactId, {
                    firstName: data.fullName.split(' ')[0],
                    lastName: data.fullName.split(' ')[1] || '',
                    email: data.email,
                    tgUserId: data.tgUserId
                });
            } else {
                contactId = await createContact({
                    firstName: data.fullName.split(' ')[0],
                    lastName: data.fullName.split(' ')[1] || '',
                    phone: data.phone,
                    email: data.email,
                    tgUserId: data.tgUserId
                });
            }
            
            if (!contactId) {
                throw new Error('Не удалось создать/обновить контакт');
            }
            
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

    window.BitrixCRM = {
        processServiceRequest,
        getUserRequests,
        getPerformersInfo,
        getDealDetails,
        mapServices // Экспортируем для использования в других местах
    };
})();