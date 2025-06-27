<?php
require '../../lib/bitrix24.php';
header('Content-Type: text/html; charset=utf-8');
$version = $_GET['v'] ?? time();
$service = $_GET['service'] ?? 'Услуга';
$price = $_GET['price'] ?? 0;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bitrix = new Bitrix24();
    $formData = $_POST;
    
    // Поиск существующего контакта
    $contact = $bitrix->findContact($formData['phone'], $formData['email']);
    
    if (!$contact) {
        // Создаем новый контакт
        $contactResponse = $bitrix->createContact($formData);
        $contactId = $contactResponse['result'] ?? null;
    } else {
        $contactId = $contact['ID'];
    }
    
    if ($contactId) {
        // Создаем сделку
        $formData['service'] = $service;
        $formData['price'] = $price;
        $dealResponse = $bitrix->createDeal($contactId, $formData);
        
        if ($dealResponse['result']) {
            $success = true;
            $dealId = $dealResponse['result'];
        } else {
            $error = 'Ошибка при создании сделки: ' . json_encode($dealResponse);
        }
    } else {
        $error = 'Ошибка при создании контакта: ' . json_encode($contactResponse);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <script src="https://telegram.org/js/telegram-web-app.js?<?=$version?>"></script>
    <link rel="stylesheet" href="/webapp/css/style.css?<?=$version?>">
</head>
<body>
    <div class="container">
        <div class="greeting">Оформление заказа</div>
        
        <?php if (isset($success)): ?>
            <div class="success-message">
                <h3>✅ Заказ успешно оформлен!</h3>
                <p>Номер сделки в Bitrix24: <?= $dealId ?></p>
                <p>Спасибо за ваш заказ! Мы свяжемся с вами в ближайшее время.</p>
                <div class="back-button" onclick="window.location.href='/webapp/client/services.php?v=<?=$version?>'">
                    Вернуться к услугам
                </div>
            </div>
        <?php else: ?>
            <form id="order-form" method="POST">
                <input type="hidden" name="service" value="<?= htmlspecialchars($service) ?>">
                <input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">
                
                <div class="service-info">
                    <h3><?= htmlspecialchars($service) ?></h3>
                    <p>Стоимость: <?= number_format($price, 0, '', ' ') ?> ₽</p>
                </div>
                
                <div class="form-group">
                    <label for="first_name">Имя *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name">
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="comment">Комментарий к заказу</label>
                    <textarea id="comment" name="comment" rows="3"></textarea>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= $error ?></div>
                <?php endif; ?>
                
                <button type="submit" class="submit-button">Оформить заказ</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        if (tg) {
            tg.expand();
            tg.setHeaderColor('#6a11cb');
            tg.MainButton.hide();
            
            // Автозаполнение данных пользователя Telegram
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const user = tg.initDataUnsafe.user;
                if (user.first_name && !document.getElementById('first_name').value) {
                    document.getElementById('first_name').value = user.first_name;
                }
                if (user.last_name && !document.getElementById('last_name').value) {
                    document.getElementById('last_name').value = user.last_name;
                }
            }
            
            // Валидация формы
            document.getElementById('order-form').addEventListener('submit', function(e) {
                const phone = document.getElementById('phone').value;
                if (!phone.match(/^(\+7|8)[0-9]{10}$/)) {
                    e.preventDefault();
                    alert('Введите корректный номер телефона в формате +7XXXXXXXXXX');
                    return false;
                }
                
                const email = document.getElementById('email').value;
                if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    e.preventDefault();
                    alert('Введите корректный email адрес');
                    return false;
                }
                
                tg.showPopup({
                    title: 'Подтверждение',
                    message: 'Вы уверены, что хотите оформить заказ?',
                    buttons: [
                        {id: 'confirm', type: 'ok', text: 'Подтвердить'},
                        {id: 'cancel', type: 'cancel', text: 'Отмена'}
                    ]
                }, function(buttonId) {
                    if (buttonId === 'confirm') {
                        e.target.submit();
                    }
                });
                
                e.preventDefault();
                return false;
            });
        }
    </script>
    
    <style>
        .service-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .service-info h3 {
            font-size: 1.4rem;
            margin-bottom: 5px;
        }
        
        .service-info p {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 15px;
            width: 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            text-align: left;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.25);
        }
        
        .submit-button {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .submit-button:hover {
            background: rgba(255, 255, 255, 0.35);
        }
        
        .error-message {
            color: #ff6b6b;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 107, 107, 0.1);
            margin: 15px 0;
            text-align: center;
        }
        
        .success-message {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
        }
        
        .success-message h3 {
            margin-bottom: 15px;
            color: #4ade80;
        }
    </style>
</body>
</html>