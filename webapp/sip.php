<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIP-звонок в Bitrix24</title>
    <script src="https://cdn.jsdelivr.net/npm/sip.js@0.20.0/dist/sip.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
        }

        .container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        input,
        button {
            padding: 10px;
            margin: 5px;
        }

        button {
            cursor: pointer;
        }

        .status {
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
        }

        .connected {
            background: #d4edda;
        }

        .calling {
            background: #cce5ff;
        }

        .error {
            background: #f8d7da;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>SIP-звонок в Bitrix24</h2>

        <div id="connectionPanel">
            <button id="connectBtn">Подключиться к SIP</button>
            <div id="status" class="status"></div>
        </div>

        <div id="callPanel" class="hidden">
            <input type="text" id="extensionInput" placeholder="Внутренний номер сотрудника">
            <button id="callBtn">Позвонить</button>
            <button id="hangupBtn" disabled>Завершить звонок</button>
        </div>

        <audio id="remoteAudio" autoplay controls class="hidden"></audio>
    </div>

    <script>
        // Конфигурация (ЗАМЕНИТЕ НА СВОИ ДАННЫЕ)
        const sipConfig = {
            uri: 'sip:ваш_логин@ваш_домен.bitrix.info', // Ваш SIP логин
            password: 'ваш_пароль', // Ваш SIP пароль
            websocket: 'wss://sip.bitrix.info:443/ws', // Стандартный Bitrix24 WS
            domain: 'ваш_домен.bitrix.info' // Ваш домен Bitrix24
        };

        // Элементы интерфейса
        const connectBtn = document.getElementById('connectBtn');
        const callBtn = document.getElementById('callBtn');
        const hangupBtn = document.getElementById('hangupBtn');
        const extensionInput = document.getElementById('extensionInput');
        const statusDiv = document.getElementById('status');
        const callPanel = document.getElementById('callPanel');
        const remoteAudio = document.getElementById('remoteAudio');

        let ua;
        let session;

        // Инициализация SIP
        function initSIP() {
            ua = new SIP.UA({
                uri: sipConfig.uri,
                password: sipConfig.password,
                transportOptions: {
                    wsServers: [sipConfig.websocket],
                    connectionRecoveryMinInterval: 2000,
                    maxReconnectionAttempts: 5
                },
                sessionDescriptionHandlerFactoryOptions: {
                    constraints: {
                        audio: true,
                        video: false
                    }
                }
            });

            ua.on('connected', () => {
                statusDiv.textContent = 'Подключено к SIP-серверу';
                statusDiv.className = 'status connected';
                callPanel.classList.remove('hidden');
            });

            ua.on('disconnected', () => {
                statusDiv.textContent = 'Отключено от SIP-сервера';
                statusDiv.className = 'status';
                callPanel.classList.add('hidden');
            });

            ua.on('registrationFailed', (response) => {
                statusDiv.textContent = `Ошибка регистрации: ${response.cause}`;
                statusDiv.className = 'status error';
            });
        }

        // Подключение к серверу
        connectBtn.addEventListener('click', () => {
            statusDiv.textContent = 'Подключаемся...';
            initSIP();
            ua.start();
        });

        // Совершение звонка
        callBtn.addEventListener('click', () => {
            const extension = extensionInput.value.trim();
            if (!extension) {
                alert('Введите внутренний номер сотрудника');
                return;
            }

            // Формируем SIP-адрес из внутреннего номера
            const target = `sip:${extension}@${sipConfig.domain}`;

            statusDiv.textContent = `Звонок на ${extension}...`;
            statusDiv.className = 'status calling';

            session = ua.invite(target, {
                sessionDescriptionHandlerOptions: {
                    constraints: {
                        audio: true,
                        video: false
                    }
                }
            });

            session.on('accepted', () => {
                statusDiv.textContent = `Разговор с ${extension} активен`;
                hangupBtn.disabled = false;
                remoteAudio.classList.remove('hidden');
                remoteAudio.srcObject = session.sessionDescriptionHandler.remoteMediaStream;
            });

            session.on('failed', () => {
                statusDiv.textContent = `Сотрудник ${extension} не ответил`;
                statusDiv.className = 'status error';
                hangupBtn.disabled = true;
            });

            session.on('bye', () => endCall(extension));
        });

        // Завершение звонка
        hangupBtn.addEventListener('click', () => {
            if (session) session.bye();
            endCall(extensionInput.value.trim());
        });

        // Обработка завершения звонка
        function endCall(extension) {
            statusDiv.textContent = extension ? `Звонок с ${extension} завершён` : 'Звонок завершён';
            statusDiv.className = 'status';
            hangupBtn.disabled = true;
            if (remoteAudio.srcObject) {
                remoteAudio.srcObject.getTracks().forEach(track => track.stop());
                remoteAudio.srcObject = null;
            }
            remoteAudio.classList.add('hidden');
        }
    </script>
</body>

</html>