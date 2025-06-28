const BITRIX_WEBHOOK = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

async function bitrixRequest(method, params = {}) {
    try {
        const response = await fetch(`${BITRIX_WEBHOOK}${method}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(params)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Bitrix API error:', error);
        throw error;
    }
}

export async function createLead(data) {
    const nameParts = data.fullName.split(' ');
    const firstName = nameParts[0] || '';
    const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
    
    const leadData = {
        fields: {
            TITLE: `Заявка от ${data.fullName}`,
            NAME: firstName,
            LAST_NAME: lastName,
            PHONE: [{ VALUE: data.phone, VALUE_TYPE: 'WORK' }],
            EMAIL: [{ VALUE: data.email, VALUE_TYPE: 'WORK' }],
            UF_CRM_1749802456: data.serviceDate,
            UF_CRM_1749802469: data.city,
            UF_CRM_1749802574: data.services.join(', '),
            UF_CRM_1749802612: data.cemetery,
            UF_CRM_1749802619: data.row,
            UF_CRM_1749802630: data.plot,
            UF_CRM_1749802631: data.plotNumber,
            COMMENTS: `Telegram: @${data.username || 'отсутствует'}\nДополнительно: ${data.additionalInfo || 'не указано'}`
        }
    };
    
    return bitrixRequest('crm.lead.add', leadData);
}

export async function getUserRequests(email) {
    return bitrixRequest('crm.lead.list', {
        filter: { 'EMAIL': email },
        select: ['ID', 'TITLE', 'DATE_CREATE', 'STATUS_ID', 'UF_*', 'COMMENTS'],
        order: { "DATE_CREATE": "DESC" }
    });
}