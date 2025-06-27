// /api/bitrix-proxy.php
<?php
header('Content-Type: application/json');
$webhook = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ch = curl_init($webhook . $data['method']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['params']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
}
?>
