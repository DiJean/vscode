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
    <link rel="stylesheet" href="/webapp/css/about.css?<?= $version ?>">
    <script>
        // Сохраняем состояние перед переходом на about.php
        sessionStorage.setItem('returnToIndex', 'true');
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="telegram-icon">
                <img src="/webapp/css/icons/bot-avatar.jpg" alt="Аватар бота">
            </div>
            <h1>О нашем сервисе</h1>
        </div>

        <div class="about-container">
            <div class="about-content">
                <p>Предлагаем полный спектр услуг по поддержанию порядка и надлежащего состояния мест погребения ваших близких. Наша команда профессионалов ответственно подходит к выполнению работ любой сложности, обеспечивая аккуратность и соблюдение всех необходимых требований.</p>

                <h3 class="about-subtitle">Основные направления деятельности:</h3>

                <ol class="services-list">
                    <li class="service-item">
                        <div class="service-title">Регулярный уход</div>
                        <div class="service-description">включает уборку территории (очистка от мусора, листьев), поддержание чистоты надгробий и памятников, стрижку газонов, удаление сорняков.</div>
                    </li>

                    <li class="service-item">
                        <div class="service-title">Сезонное обслуживание</div>
                        <div class="service-description">подготовка участка к зимнему периоду или праздничным мероприятиям (Покраска ограды, установка венков).</div>
                    </li>

                    <li class="service-item">
                        <div class="service-title">Ремонтные работы</div>
                        <div class="service-description">восстановление поврежденных элементов мемориальных комплексов, реставрация мраморных плит, гранитных сооружений.</div>
                    </li>

                    <li class="service-item">
                        <div class="service-title">Дизайн и благоустройство</div>
                        <div class="service-description">разработка индивидуальных проектов озеленения и благоустройства территорий, создание композиций из цветов и декоративных растений.</div>
                    </li>

                    <li class="service-item">
                        <div class="service-title">Комплексная уборка</div>
                        <div class="service-description">генеральная очистка территории после длительного отсутствия внимания со стороны родственников покойного.</div>
                    </li>

                    <li class="service-item">
                        <div class="service-title">Мониторинг состояния объекта</div>
                        <div class="service-description">регулярные осмотры места захоронения с целью выявления возможных повреждений и необходимости проведения ремонтных мероприятий.</div>
                    </li>
                </ol>

                <div class="guarantee-section">
                    <div class="guarantee-icon">
                        <svg viewBox="0 0 24 24">
                            <path fill="white" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" />
                        </svg>
                    </div>
                    <p>Мы гарантируем высокое качество выполнения работ, оперативность и ответственность наших сотрудников. Обращаясь к нам, вы можете быть уверены, что место упокоения вашего близкого будет ухоженным и достойным памяти о нем.</p>
                </div>
            </div>

            <div class="back-btn-container">
                <button id="back-button" class="back-btn">
                    <svg class="btn-icon" viewBox="0 0 24 24" width="20" height="20">
                        <path fill="white" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                    </svg>
                    На главную
                </button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('back-button').addEventListener('click', function() {
            // Используем историю браузера для возврата
            window.history.back();
        });
    </script>
</body>

</html>