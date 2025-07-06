<?php
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
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/deal-details.css?<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- УДАЛЕН ВСТРОЕННЫЙ БЛОК STYLE -->
</head>

<body class="theme-beige">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="/webapp/doer/dashboard.php" class="back-btn">← Назад к списку заявок</a>
        </div>

        <h1 class="text-center mb-4">Детали заявки</h1>

        <div class="detail-card" id="deal-container">
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
        </div>

        <!-- Секция завершения заявки -->
        <div class="completion-section" id="completion-section" style="display: none;">
            <h3>Завершение заявки</h3>
            <form id="complete-deal-form" enctype="multipart/form-data">
                <input type="hidden" name="deal_id" id="deal-id-hidden">
                <input type="hidden" name="tg_user_id" id="tg-user-id-hidden">

                <div class="photo-upload-container">
                    <div class="photo-upload">
                        <div class="detail-label">Фото до работы</div>
                        <div class="photo-preview" id="before-preview">
                            <span>Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="before_photo" accept="image/*" hidden>
                        </label>
                    </div>
                    <div class="photo-upload">
                        <div class="detail-label">Фото после работы</div>
                        <div class="photo-preview" id="after-preview">
                            <span>Изображение не выбрано</span>
                        </div>
                        <label class="upload-btn">
                            📸 Загрузить фото
                            <input type="file" name="after_photo" accept="image/*" hidden>
                        </label>
                    </div>
                </div>

                <button type="submit" class="complete-btn" id="complete-btn">Завершить заявку</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/webapp/js/bitrix-integration.js?<?= $version ?>"></script>

    <script>
        // JavaScript без изменений
    </script>
</body>
</html>