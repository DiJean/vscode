export function initTelegramApp() {
    if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
        return null;
    }
    
    const tg = Telegram.WebApp;
    
    try {
        tg.ready();
        if (tg.isExpanded !== true) {
            tg.expand();
        }
        tg.backgroundColor = '#6a11cb';
        if (tg.setHeaderColor) {
            tg.setHeaderColor('#6a11cb');
        }
        return tg;
    } catch (e) {
        console.error('Telegram init error:', e);
        return null;
    }
}

export function getUserData() {
    const tg = Telegram.WebApp;
    return tg?.initDataUnsafe?.user || null;
}

export function isMobile() {
    const tg = Telegram.WebApp;
    return tg?.isMobile || false;
}

export const MainButton = {
    show: (text, color = '#6a11cb', textColor = '#ffffff') => {
        const tg = Telegram.WebApp;
        if (!tg?.MainButton) return;
        
        tg.MainButton.setText(text);
        tg.MainButton.color = color;
        tg.MainButton.textColor = textColor;
        tg.MainButton.show();
    },
    hide: () => {
        Telegram.WebApp.MainButton.hide();
    },
    onClick: (handler) => {
        Telegram.WebApp.MainButton.onClick(handler);
    },
    enable: () => {
        Telegram.WebApp.MainButton.enable();
    },
    disable: () => {
        Telegram.WebApp.MainButton.disable();
    },
    showProgress: () => {
        const tg = Telegram.WebApp;
        if (!tg.MainButton) return;
        
        tg.MainButton.setText("Отправка...");
        tg.MainButton.disable();
        if (tg.showProgress) tg.showProgress();
    },
    hideProgress: () => {
        const tg = Telegram.WebApp;
        if (!tg.MainButton) return;
        
        tg.MainButton.setText("Отправить заявку");
        tg.MainButton.enable();
        if (tg.hideProgress) tg.hideProgress();
    }
};