// Единая функция инициализации Telegram WebApp
export function initTelegramApp() {
    return new Promise((resolve, reject) => {
        if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
            reject(new Error('Telegram WebApp API not available'));
            return;
        }
        
        const tg = Telegram.WebApp;
        
        try {
            tg.ready();
            
            // Автоматическое раскрытие на весь экран
            if (tg.isExpanded !== true && typeof tg.expand === 'function') {
                tg.expand();
            }
            
            // Установка цветовой схемы
            tg.backgroundColor = '#6a11cb';
            if (typeof tg.setHeaderColor === 'function') {
                tg.setHeaderColor('#6a11cb');
            }
            
            // Возвращаем объект WebApp
            resolve(tg);
        } catch (e) {
            console.error('Telegram initialization error:', e);
            reject(e);
        }
    });
}

// Генерация CSRF токена
export function generateCSRFToken() {
    const token = window.crypto.getRandomValues(new Uint32Array(1))[0].toString(16);
    sessionStorage.setItem('csrf_token', token);
    return token;
}

// Получение данных пользователя
export function getUserData(tg) {
    return tg?.initDataUnsafe?.user || null;
}
