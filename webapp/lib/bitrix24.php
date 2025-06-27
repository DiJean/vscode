<?php
class Bitrix24 {
    private $webhookUrl = 'https://b24-saiczd.bitrix24.ru/rest/1/gwr1en9g6spkiyj9/';
    
    // Поиск контакта по телефону или email
    public function findContact($phone = null, $email = null) {
        $params = [
            'filter' => [
                'LOGIC' => 'OR'
            ]
        ];
        
        if ($phone) {
            $params['filter'][] = ['PHONE' => $phone];
        }
        
        if ($email) {
            $params['filter'][] = ['EMAIL' => $email];
        }
        
        $response = $this->call('crm.contact.list', $params);
        return $response['result'][0] ?? null;
    }
    
    // Создание нового контакта
    public function createContact($data) {
        $fields = [
            'NAME' => $data['first_name'] ?? '',
            'LAST_NAME' => $data['last_name'] ?? '',
            'PHONE' => [['VALUE' => $data['phone'], 'VALUE_TYPE' => 'WORK']],
            'EMAIL' => $data['email'] ? [['VALUE' => $data['email'], 'VALUE_TYPE' => 'WORK']] : [],
            'COMMENTS' => $data['comment'] ?? '',
            'SOURCE_ID' => 'WEB',
            'ASSIGNED_BY_ID' => 1 // ID ответственного
        ];
        
        return $this->call('crm.contact.add', ['fields' => $fields]);
    }
    
    // Создание сделки
    public function createDeal($contactId, $data) {
        $fields = [
            'TITLE' => 'Заказ услуги: ' . ($data['service'] ?? 'Услуга'),
            'CONTACT_ID' => $contactId,
            'CATEGORY_ID' => 0, // Воронка по умолчанию
            'STAGE_ID' => 'NEW', // Стадия: Новая
            'OPPORTUNITY' => $data['price'] ?? 0,
            'CURRENCY_ID' => 'RUB',
            'COMMENTS' => $data['comment'] ?? '',
            'SOURCE_ID' => 'WEB',
            'ASSIGNED_BY_ID' => 1 // ID ответственного
        ];
        
        return $this->call('crm.deal.add', ['fields' => $fields]);
    }
    
    // Отправка запроса к Bitrix24 API
    private function call($method, $params = []) {
        $url = $this->webhookUrl . $method;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => http_build_query($params)
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => $error];
        }
        
        return json_decode($response, true);
    }
}
?>