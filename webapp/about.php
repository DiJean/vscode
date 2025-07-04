<?php
header('Content-Type: text/html; charset=utf-8');
$version = time();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нашем сервисе</title>
    <link rel="stylesheet" href="/webapp/css/style.css?<?= $version ?>">
    <link rel="stylesheet" href="/webapp/css/about.css?<?= $version ?>">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="telegram-icon">
                <svg viewBox="0 0 24 24" width="40" height="40">
                    <path fill="white" d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.14.141-.259.259-.374.261l.213-3.053 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.136-.954l11.566-4.458c.538-.196 1.006.128.832.941z" />
                </svg>
            </div>
            <h1>О нашем сервисе</h1>
            <p>Профессиональный уход за местами погребения</p>
        </div>

        <div class="about-container">
            <div class="about-content">
                <p>Наш сервис предоставляет комплексные услуги по уходу за местами погребения. Мы понимаем, как важно сохранять память о близких, и стремимся обеспечить достойный уход за последним пристанищем ваших родных и друзей.</p>

                <div class="about-subtitle">Наши услуги</div>

                <ul class="services-list">
                    <li class="service-item">
                        <div class="service-title">Регулярный уход</div>
                        <div class="service-description">Покос травы, уборка листьев, поддержание чистоты и порядка на участке</div>
                    </li>
                    <li class="service-item">
                        <div class="service-title">Установка и обслуживание памятников</div>
                        <div class="service-description">Профессиональный монтаж, чистка и реставрация памятников</div>
                    </li>
                    <li class="service-item">
                        <div class="service-title">Благоустройство территории</div>
                        <div class="service-description">Озеленение, посадка цветов, устройство дорожек</div>
                    </li>
                    <li class="service-item">
                        <div class="service-title">Сезонный уход</div>
                        <div class="service-description">Подготовка к зиме, уборка снега, весенняя обработка</div>
                    </li>
                </ul>

                <div class="guarantee-section">
                    <div class="guarantee-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="white" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                        </svg>
                    </div>
                    <h3>Гарантия качества</h3>
                    <p>Все работы выполняются профессиональными сотрудниками с соблюдением этических норм и стандартов качества. Мы гарантируем бережное отношение и индивидуальный подход к каждому заказу.</p>
                </div>
            </div>
        </div>

        <div class="back-btn-container">
            <a href="/" class="back-btn">
                <svg class="btn-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="white" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
                </svg>
                На главную
            </a>
            <!--Добавленная ссылка на SIP
            <a href="/webapp/sip.php" class="back-btn" style="margin-left: 10px;">
                <svg class="btn-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="white" d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                </svg>
                SIP-телефония
            </a>-->
        </div>
    </div>
</body>

</html>