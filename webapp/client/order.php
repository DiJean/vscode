<script src="/webapp/js/bitrix-integration.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('request-form');
        const tg = window.Telegram && Telegram.WebApp;
        
        // Если пользователь авторизован в Telegram
        if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
            const user = tg.initDataUnsafe.user;
            const firstName = user.first_name || '';
            const lastName = user.last_name || '';
            
            // Автозаполнение имени, если доступно
            if (firstName || lastName) {
                document.getElementById('fullName').value = `${firstName} ${lastName}`.trim();
            }
        }
        
        // Установка текущей даты как минимальной для выбора
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('serviceDate').min = today;
        
        // Обработка отправки формы
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Показываем индикатор загрузки
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
            
            // Собираем данные формы
            const formData = {
                fullName: document.getElementById('fullName').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                service: document.getElementById('service').value,
                serviceDate: document.getElementById('serviceDate').value,
                city: document.getElementById('city').value,
                cemetery: document.getElementById('cemetery').value,
                sector: document.getElementById('sector').value,
                row: document.getElementById('row').value,
                plot: document.getElementById('plot').value,
                comments: document.getElementById('comments').value
            };
            
            try {
                // Используем функцию из глобального объекта
                const result = await BitrixCRM.createServiceRequest(formData);
                
                if (result.result) {
                    // Сохраняем email для последующего использования
                    localStorage.setItem('userEmail', formData.email);
                    
                    // Показываем уведомление
                    if (tg && tg.showAlert) {
                        tg.showAlert('✅ Запрос успешно создан!');
                    } else {
                        alert('✅ Запрос успешно создан!');
                    }
                    
                    // Возвращаемся к списку сервисов
                    setTimeout(() => {
                        window.location.href = '/webapp/client/services.php';
                    }, 1500);
                } else {
                    console.error('Bitrix24 error:', result);
                    const errorMsg = `❌ Ошибка: ${result.error_description || 'Неизвестная ошибка'}`;
                    
                    if (tg && tg.showAlert) {
                        tg.showAlert(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Восстанавливаем кнопку
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Отправить запрос';
                }
            } catch (error) {
                console.error('Request failed:', error);
                const errorMsg = '🚫 Ошибка сети или сервера. Попробуйте позже.';
                
                if (tg && tg.showAlert) {
                    tg.showAlert(errorMsg);
                } else {
                    alert(errorMsg);
                }
                
                // Восстанавливаем кнопку
                submitBtn.disabled = false;
                submitBtn.textContent = 'Отправить запрос';
            }
        });
    });
</script>