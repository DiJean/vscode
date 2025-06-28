<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Мои запросы</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?=$version?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?=$version?>">
    <style>
        .btn-create {
            display: block; width: 100%; padding: 16px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white; text-align: center; border-radius: 16px;
            font-size: 1.2rem; font-weight: bold; text-decoration: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); margin-top: 20px;
        }
        .requests-list { margin-top: 30px; }
        .request-item {
            background: rgba(255,255,255,0.1); border-radius: 16px;
            padding: 20px; margin-bottom: 15px; text-align: left;
        }
        .request-service { font-weight: bold; font-size: 1.1rem; }
        .request-date { opacity: 0.9; margin-top: 5px; }
        .request-status {
            margin-top: 10px; padding: 5px 10px; border-radius: 12px;
            background: rgba(255,255,255,0.2); display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">Мои запросы</div>
        <a href="/webapp/client/order.php?v=<?=$version?>" class="btn-create">+ Создать новый запрос</a>
        <div class="requests-list" id="requests-list">
            <div class="request-item"><div class="request-service">Загрузка...</div></div>
        </div>
    </div>

    <script>
        // Восстанавливаем данные пользователя
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        const selectedRole = localStorage.getItem('selectedRole') || sessionStorage.getItem('selectedRole');
        
        if (!selectedRole || selectedRole !== 'client') {
            // Если роль не сохранена, возвращаем на главную
            window.location.href = '/?v=<?=$version?>';
        } else {
            // Сохраняем роль повторно для надежности
            localStorage.setItem('selectedRole', 'client');
            sessionStorage.setItem('selectedRole', 'client');
            
            // Обновляем приветствие
            if (userData.firstName) {
                document.querySelector('.greeting').textContent = `Привет, ${userData.firstName}!`;
            }
        }
        
        const email = localStorage.getItem('userEmail');
        if (!email) {
            document.getElementById('requests-list').innerHTML = `
                <div class="request-item">
                    <div class="request-service">Вы еще не создавали заявок</div>
                </div>
            `;
        }
    </script>

    <script src="/webapp/js/bitrix-integration.js?<?=$version?>"></script>
    <script>
        if (email) {
            BitrixCRM.getUserRequests(email)
                .then(response => {
                    const leads = response.result || [];
                    let html = '';
                    if (leads.length === 0) {
                        html = `<div class="request-item"><div class="request-service">У вас пока нет заявок</div></div>`;
                    } else {
                        leads.forEach(lead => {
                            const date = new Date(lead.DATE_CREATE).toLocaleDateString('ru-RU');
                            const service = lead.UF_CRM_685D2956C64E0 || 'Услуга не указана';
                            html += `
                                <div class="request-item">
                                    <div class="request-service">${service}</div>
                                    <div class="request-date">Создано: ${date}</div>
                                    <div class="request-status">Статус: ${lead.STATUS_ID || 'Новый'}</div>
                                </div>
                            `;
                        });
                    }
                    document.getElementById('requests-list').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('requests-list').innerHTML = `
                        <div class="request-item">
                            <div class="request-service">Ошибка загрузки данных</div>
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>