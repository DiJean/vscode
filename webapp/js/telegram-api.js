// Функция инициализации Telegram приложения
function initTelegramApp(callback) {
    if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
        return false;
    }
    
    const tg = Telegram.WebApp;
    
    try {
        tg.ready();
        
        if (tg.isExpanded !== true && tg.expand) {
            tg.expand();
        }
        
        tg.backgroundColor = '#6a11cb';
        if (tg.setHeaderColor) {
            tg.setHeaderColor('#6a11cb');
        }
        
        return callback(tg);
    } catch (e) {
        console.error('Telegram API error:', e);
        return false;
    }
}

// Настройка главной кнопки
function setupMainButton(tg, onClickHandler) {
    if (tg.MainButton) {
        tg.MainButton.setText("Продолжить");
        tg.MainButton.onClick(onClickHandler);
        tg.MainButton.show();
        return true;
    }
    return false;
}

// Получение данных пользователя
function getUserData(tg) {
    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        return tg.initDataUnsafe.user;
    }
    return null;
}
