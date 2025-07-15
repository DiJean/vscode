<?php
require_once('/var/www/config.php');
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заявки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/deal-details.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <script>
        window.BITRIX_WEBHOOK = '<?= BITRIX_WEBHOOK ?>';
    </script>
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="/webapp/doer/dashboard.php" class="btn btn-outline-primary">← Назад к списку заявок</a>
        </div>

        <h1 class="text-center mb-4">Детали заявки</h1>

        <div class="detail-card" id="deal-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>

        <!-- Секция завершения заявки - СКРЫТА ПО УМОЛЧАНИЮ -->
        <div class="completion-section" id="completion-section" style="display: none;">
            <h3>Завершение заявки</h3>
            <form id="complete-deal-form" enctype="multipart/form-data">
                <input type="hidden" name="deal_id" id="deal-id-hidden">
                <input type="hidden" name="tg_user_id" id="tg-user-id-hidden">

                <div class="photo-upload-container">
                    <div class="photo-upload">
                        <div class="detail-label">Фото до работы</div>
                        <div class="photo-preview" id="before-preview">
                            <span class="photo-placeholder">Изображение не выбрано</span>
                        </div>
                        <div class="camera-controls">
                            <button type="button" class="btn btn-sm btn-outline-primary take-photo-btn" data-target="before">
                                <i class="bi bi-camera"></i> Сделать фото
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary upload-photo-btn" data-target="before">
                                <i class="bi bi-upload"></i> Выбрать файл
                            </button>
                            <input type="file" name="before_photo" accept="image/*" style="display:none" data-target="before">
                        </div>
                    </div>
                    <div class="photo-upload">
                        <div class="detail-label">Фото после работы</div>
                        <div class="photo-preview" id="after-preview">
                            <span class="photo-placeholder">Изображение не выбрано</span>
                        </div>
                        <div class="camera-controls">
                            <button type="button" class="btn btn-sm btn-outline-primary take-photo-btn" data-target="after">
                                <i class="bi bi-camera"></i> Сделать фото
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary upload-photo-btn" data-target="after">
                                <i class="bi bi-upload"></i> Выбрать файл
                            </button>
                            <input type="file" name="after_photo" accept="image/*" style="display:none" data-target="after">
                        </div>
                    </div>
                </div>

                <button type="submit" class="complete-btn" id="complete-btn">Завершить заявку</button>
            </form>

            <div class="completed-photos" id="completed-photos" style="display: none;">
                <h4>Загруженные фото</h4>
                <div class="row mt-3" id="uploaded-photos-container">
                    <!-- Здесь будут отображаться загруженные фото -->
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для просмотра фото -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Просмотр фото</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" alt="" class="modal-photo">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для камеры -->
    <div class="modal fade" id="camera-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Сделать фото</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="camera-container">
                        <video id="camera-preview" autoplay playsinline></video>
                        <canvas id="camera-canvas" style="display:none"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <button id="capture-btn" class="btn btn-primary">
                            <i class="bi bi-camera"></i> Сделать снимок
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        const BITRIX_WEBHOOK = window.BITRIX_WEBHOOK;
        const version = '<?= $version ?>';
        const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
        const cameraModal = new bootstrap.Modal(document.getElementById('camera-modal'));

        // Словарь статусов заявок
        const stageNames = {
            'NEW': 'Новый заказ',
            'PREPARATION': 'Подготовка',
            'PREPAYMENT_INVOICE': 'Оплата',
            'EXECUTING': 'В работе',
            'FINAL_INVOICE': 'Выставлен счет',
            'WON': 'Успешно завершена',
            'LOSE': 'Не нашли участок',
            'APOLOGY': 'Анализ неудачи'
        };

        // Словарь услуг
        const serviceNames = {
            '69': 'Уход',
            '71': 'Цветы',
            '73': 'Ремонт',
            '75': 'Церковная служба'
        };

        document.addEventListener('DOMContentLoaded', async function() {
            // Инициализация Telegram WebApp
            let tg = null;
            let user = null;
            try {
                if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
                    tg = Telegram.WebApp;
                    tg.ready();
                    user = tg.initDataUnsafe.user;
                }
            } catch (e) {
                console.error('Telegram WebApp init error', e);
            }

            // Получаем ID заявки из URL
            const urlParams = new URLSearchParams(window.location.search);
            const dealId = urlParams.get('id');
            if (!dealId) {
                showError('Не указан ID заявки');
                return;
            }

            // Загружаем детали заявки
            const dealContainer = document.getElementById('deal-container');
            try {
                const deal = await getDealDetails(dealId);
                if (!deal) {
                    showError('Заявка не найдена');
                    return;
                }

                // Для клиента: загружаем данные об исполнителе
                if (deal.performerId) {
                    const performer = await getPerformerInfo(deal.performerId);
                    if (performer) {
                        deal.performerName = `${performer.NAME || ''} ${performer.LAST_NAME || ''}`.trim();
                    }
                }

                // Отображаем детали заявки с цветными статусами
                renderDealDetails(deal, user ? 'doer' : 'client');

                // Проверяем, является ли текущий пользователь исполнителем
                if (user) {
                    // Ищем контакт текущего пользователя (исполнителя) по Telegram ID
                    const performerContact = await findPerformerByTgId(user.id);

                    // Показываем секцию завершения ТОЛЬКО для статуса "В работе"
                    if (performerContact && performerContact.ID == deal.performerId && deal.stageId === 'EXECUTING') {
                        // Показываем секцию завершения
                        document.getElementById('completion-section').style.display = 'block';
                        // Заполняем hidden поля формы
                        document.getElementById('deal-id-hidden').value = dealId;
                        document.getElementById('tg-user-id-hidden').value = user.id;

                        // Инициализация загрузки фото
                        initPhotoUpload();
                    }

                    // Если заявка завершена, показываем загруженные фото
                    if (deal.stageId === 'WON') {
                        document.getElementById('completed-photos').style.display = 'block';
                        showUploadedPhotos(deal);
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки заявки', error);
                showError('Ошибка загрузки данных заявки');
            }
        });

        // Функции работы с камерой
        function initCamera() {
            let currentCameraTarget = null;
            const cameraPreview = document.getElementById('camera-preview');
            const cameraCanvas = document.getElementById('camera-canvas');
            const captureBtn = document.getElementById('capture-btn');
            let mediaStream = null;

            // Обработчики кнопок съемки фото
            document.querySelectorAll('.take-photo-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    currentCameraTarget = this.dataset.target;
                    try {
                        await startCamera();
                        cameraModal.show();
                    } catch (error) {
                        console.error('Camera error:', error);
                        alert('Не удалось открыть камеру. Используйте загрузку файла.');
                    }
                });
            });

            // Обработчики кнопок загрузки файла
            document.querySelectorAll('.upload-photo-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const target = this.dataset.target;
                    document.querySelector(`input[name="${target}_photo"]`).click();
                });
            });

            // Обработчики для стандартных input[type=file]
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function(e) {
                    const target = this.dataset.target;
                    const preview = document.getElementById(`${target}-preview`);
                    const file = e.target.files[0];

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            preview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = '<span class="photo-placeholder">Изображение не выбрано</span>';
                    }
                });
            });

            // Сделать снимок
            captureBtn.addEventListener('click', function() {
                if (!cameraPreview.srcObject) return;

                const context = cameraCanvas.getContext('2d');
                cameraCanvas.width = cameraPreview.videoWidth;
                cameraCanvas.height = cameraPreview.videoHeight;

                context.drawImage(cameraPreview, 0, 0, cameraCanvas.width, cameraCanvas.height);

                // Конвертируем в Blob
                cameraCanvas.toBlob(blob => {
                    if (blob) {
                        // Создаем File объект
                        const file = new File([blob], `photo_${Date.now()}.jpg`, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });

                        // Обновляем превью
                        const preview = document.getElementById(`${currentCameraTarget}-preview`);
                        const url = URL.createObjectURL(blob);
                        preview.innerHTML = `<img src="${url}" alt="Preview">`;

                        // Обновляем input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        const input = document.querySelector(`input[name="${currentCameraTarget}_photo"]`);
                        input.files = dataTransfer.files;

                        // Закрываем камеру
                        stopCamera();
                        cameraModal.hide();
                    }
                }, 'image/jpeg', 0.95);
            });

            // Очистка при закрытии модалки камеры
            document.getElementById('camera-modal').addEventListener('hidden.bs.modal', stopCamera);
        }

        async function startCamera() {
            stopCamera();

            const constraints = {
                video: {
                    facingMode: 'environment',
                    width: {
                        ideal: 1920
                    },
                    height: {
                        ideal: 1080
                    }
                },
                audio: false
            };

            try {
                mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                cameraPreview.srcObject = mediaStream;
                return true;
            } catch (err) {
                if (err.name === 'NotFoundError' || err.name === 'OverconstrainedError') {
                    // Попробуем фронтальную камеру, если задняя недоступна
                    constraints.video.facingMode = 'user';
                    mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                    cameraPreview.srcObject = mediaStream;
                    return true;
                }
                throw err;
            }
        }

        function stopCamera() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            if (cameraPreview.srcObject) {
                cameraPreview.srcObject = null;
            }
        }

        function initPhotoUpload() {
            initCamera();

            // Обработчик отправки формы
            document.getElementById('complete-deal-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                // Проверка наличия файлов
                const beforeFile = this.elements.before_photo.files[0];
                const afterFile = this.elements.after_photo.files[0];

                if (!beforeFile || !afterFile) {
                    alert('Пожалуйста, загрузите оба фото!');
                    return;
                }

                // Проверка, что файлы не пустые
                if (beforeFile.size === 0 || afterFile.size === 0) {
                    alert('Файлы не должны быть пустыми!');
                    return;
                }

                const formData = new FormData(this);
                const completeBtn = document.getElementById('complete-btn');
                completeBtn.disabled = true;
                completeBtn.textContent = 'Отправка...';

                try {
                    const response = await fetch('/webapp/doer/complete_deal.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Показываем сообщение об успехе
                        if (typeof Telegram !== 'undefined' && Telegram.WebApp && Telegram.WebApp.showPopup) {
                            Telegram.WebApp.showPopup({
                                title: 'Успех!',
                                message: 'Заявка успешно завершена',
                                buttons: [{
                                    id: 'ok',
                                    type: 'ok'
                                }]
                            });
                        } else {
                            alert('Заявка успешно завершена!');
                        }

                        // Обновляем страницу через 2 секунды
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert(result.error || 'Ошибка при завершении заявки');
                        completeBtn.disabled = false;
                        completeBtn.textContent = 'Завершить заявку';
                    }
                } catch (error) {
                    console.error('Ошибка отправки формы', error);
                    alert('Сетевая ошибка');
                    completeBtn.disabled = false;
                    completeBtn.textContent = 'Завершить заявку';
                }
            });
        }

        // Остальные функции (getDealDetails, findPerformerByTgId, getPerformerInfo, renderDealDetails, 
        // getFileUrl, showUploadedPhotos, openPhotoModal, showError) остаются без изменений
        // ... [код функций из предыдущей версии] ...

        async function getDealDetails(dealId) {
            // ... [код без изменений] ...
        }

        async function findPerformerByTgId(tgId) {
            // ... [код без изменений] ...
        }

        async function getPerformerInfo(performerId) {
            // ... [код без изменений] ...
        }

        function renderDealDetails(deal, userType = 'client') {
            // ... [код без изменений] ...
        }

        function getFileUrl(fileId) {
            // ... [код без изменений] ...
        }

        function showUploadedPhotos(deal) {
            // ... [код без изменений] ...
        }

        function openPhotoModal(photoUrl) {
            // ... [код без изменений] ...
        }

        function showError(message) {
            // ... [код без изменений] ...
        }
    </script>
</body>

</html>