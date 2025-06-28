<?php
// Базовый путь к папке webapp
$base_path = '/webapp/';
$js_path = $base_path . 'js/';
$css_path = $base_path . 'css/';
$client_path = $base_path . 'client/';
$doer_path = $base_path . 'doer/';

// Полный URL сайта
$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
?>