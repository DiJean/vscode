<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIP Телефония</title>
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/sip.css?<?= $version ?>">
    <!-- Подключаем SIP.js через CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sip.js@0.20.0/dist/sip.min.js"></script>
</head>

<body>
    <div class="container">
        <h1>SIP Телефония</h1>
        <div class="sip-container">
            <div id="sip-status">Статус: Не подключено</div>
            <div class="sip-controls">
                <input type="text" id="phone-number" placeholder="Номер телефона" disabled>
                <button id="call-btn" disabled>Позвонить</button>
                <button id="hangup-btn" disabled>Завершить</button>
            </div>
            <div class="sip-actions">
                <button id="connect-btn">Подключиться</button>
                <button id="disconnect-btn" disabled>Отключиться</button>
            </div>
        </div>
    </div>

    <script>
        // Ожидаем полной загрузки страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Конфигурация SIP
            const sipConfig = {
                uri: 'sip:1001@cloud.mikrod.ru',
                password: 'password',
                wsServers: 'wss://cloud.mikrod.ru:8089/ws'
            };

            // Глобальные переменные
            let userAgent;
            let session;

            // Элементы интерфейса
            const connectBtn = document.getElementById('connect-btn');
            const disconnectBtn = document.getElementById('disconnect-btn');
            const callBtn = document.getElementById('call-btn');
            const hangupBtn = document.getElementById('hangup-btn');
            const phoneNumberInput = document.getElementById('phone-number');
            const statusDisplay = document.getElementById('sip-status');

            // Обработчики событий
            connectBtn.addEventListener('click', connectSIP);
            disconnectBtn.addEventListener('click', disconnectSIP);
            callBtn.addEventListener('click', makeCall);
            hangupBtn.addEventListener('click', hangupCall);

            // Функция подключения к SIP серверу
            function connectSIP() {
                statusDisplay.textContent = 'Статус: Подключение...';

                // Проверяем доступность SIP объекта
                if (typeof SIP === 'undefined') {
                    statusDisplay.textContent = 'Ошибка: Библиотека SIP не загружена';
                    return;
                }

                // Создаем UserAgent
                userAgent = new SIP.UA(sipConfig);

                // Обработчики событий UserAgent
                userAgent.on('connected', () => {
                    statusDisplay.textContent = 'Статус: Подключено';
                    connectBtn.disabled = true;
                    disconnectBtn.disabled = false;
                    callBtn.disabled = false;
                    phoneNumberInput.disabled = false;
                });

                userAgent.on('disconnected', () => {
                    statusDisplay.textContent = 'Статус: Отключено';
                    connectBtn.disabled = false;
                    disconnectBtn.disabled = true;
                    callBtn.disabled = true;
                    hangupBtn.disabled = true;
                    phoneNumberInput.disabled = true;
                });

                userAgent.on('registrationFailed', () => {
                    statusDisplay.textContent = 'Ошибка регистрации';
                    connectBtn.disabled = false;
                });

                // Запускаем подключение
                userAgent.start();
            }

            // Функция отключения от SIP сервера
            function disconnectSIP() {
                if (userAgent) {
                    userAgent.stop();
                }
            }

            // Функция совершения звонка
            function makeCall() {
                const number = phoneNumberInput.value.trim();
                if (!number) return;

                statusDisplay.textContent = `Статус: Звонок на ${number}...`;
                session = userAgent.invite(`sip:${number}@cloud.mikrod.ru`, {
                    sessionDescriptionHandlerOptions: {
                        constraints: {
                            audio: true,
                            video: false
                        }
                    }
                });

                // Обработчики событий сессии
                session.on('accepted', () => {
                    statusDisplay.textContent = 'Статус: Разговор';
                    hangupBtn.disabled = false;
                    callBtn.disabled = true;
                });

                session.on('bye', () => {
                    statusDisplay.textContent = 'Статус: Звонок завершен';
                    hangupBtn.disabled = true;
                    callBtn.disabled = false;
                });

                session.on('failed', () => {
                    statusDisplay.textContent = 'Статус: Ошибка звонка';
                    hangupBtn.disabled = true;
                    callBtn.disabled = false;
                });
            }

            // Функция завершения звонка
            function hangupCall() {
                if (session) {
                    session.bye();
                }
            }
        });
    </script>
</body>

</html>