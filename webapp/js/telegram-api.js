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
    try {
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            return Telegram.WebApp.initDataUnsafe?.user || null;
        }
    } catch (e) {
        console.error('Error getting user data:', e);
    }
    return null;
}

// Исправлено: сделана функцией
export function isMobile() {
    const tg = Telegram.WebApp;
    return tg?.isMobile || false;
}

export const MainButton = {
    show: (text, color = '#6a11cb', textColor = '#ffffff') => {
        try {
            if (typeof Telegram !== 'undefined' && Telegram.WebApp?.MainButton) {
                const mainButton = Telegram.WebApp.MainButton;
                mainButton.setText(text);
                mainButton.color = color;
                mainButton.textColor = textColor;
                mainButton.show();
                return true;
            }
        } catch (e) {
            console.error('Error showing main button:', e);
        }
        return false;
    },
    hide: () => {
        try {
            if (Telegram.WebApp?.MainButton) {
                Telegram.WebApp.MainButton.hide();
                return true;
            }
        } catch (e) {
            console.error('Error hiding main button:', e);
        }
        return false;
    },
    onClick: (handler) => {
        try {
            if (Telegram.WebApp?.MainButton) {
                Telegram.WebApp.MainButton.onClick(handler);
                return true;
            }
        } catch (e) {
            console.error('Error setting main button click:', e);
        }
        return false;
    },
    enable: () => {
        try {
            if (Telegram.WebApp?.MainButton) {
                Telegram.WebApp.MainButton.enable();
                return true;
            }
        } catch (e) {
            console.error('Error enabling main button:', e);
        }
        return false;
    },
    disable: () => {
        try {
            if (Telegram.WebApp?.MainButton) {
                Telegram.WebApp.MainButton.disable();
                return true;
            }
        } catch (e) {
            console.error('Error disabling main button:', e);
        }
        return false;
    },
    showProgress: () => {
        try {
            const tg = Telegram.WebApp;
            if (tg?.MainButton) {
                tg.MainButton.setText("Отправка...");
                tg.MainButton.disable();
                if (tg.showProgress) {
                    tg.showProgress();
                }
                return true;
            }
        } catch (e) {
            console.error('Error showing progress:', e);
        }
        return false;
    },
    hideProgress: () => {
        try {
            const tg = Telegram.WebApp;
            if (tg?.MainButton) {
                tg.MainButton.setText("Отправить заявку");
                tg.MainButton.enable();
                if (tg.hideProgress) {
                    tg.hideProgress();
                }
                return true;
            }
        } catch (e) {
            console.error('Error hiding progress:', e);
        }
        return false;
    }
};