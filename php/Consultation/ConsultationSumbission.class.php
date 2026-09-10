<?php

class ConsultationSubmission {
    private $formData;
    private $config;

    public function __construct($config, $postData) {
        $this->config = $config;
        $secretKey = $this->config['recaptcha_secret'] ?? '';
        if ($secretKey === '') {
            die('error');
        }
        $captchaResponse = $postData['g-recaptcha-response'];
        

        // Вызов функции для проверки reCAPTCHA
        $captchaVerification = $this->verify_captcha($secretKey, $captchaResponse);

        if (!$captchaVerification->success) {
            die('error');
        }
        
        // Проверка honeypot
        if (!empty($postData['honeypot'])) {
            die('error'); // Остановить выполнение, если honeypot заполнен
        }
        $this->formData = $this->sanitizeInput($postData);
    }

    private function sanitizeInput($data) {
        $sanitized = [];
        $fields = [
            'userName', 'userAge', 'userCity', 'contactMethod', 
            'userProblem', 'whatsappPhone', 'telegramUsername'
        ];

        foreach ($fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? 
                htmlspecialchars($data[$field]) : '';
        }

        $sanitized['userConsent'] = isset($data['userConsent']) ? 'Да' : 'Нет';
        $sanitized['issues'] = isset($data['issues']) ? 
            implode(", ", $data['issues']) : 'Не указано';
        
        // Handle phone/contact logic
        $sanitized['phone'] = !empty($data['whatsappPhone']) ? 
            $data['whatsappPhone'] : (!empty($data['telegramUsername']) ? 
            $data['telegramUsername'] : 'Не указано');

        return $sanitized;
    }

    private function createEmailMessage() {
        return "
        <html>
        <head>
        <title>Заявка на консультацию</title>
        </head>
        <body>
        <h2>Новая заявка на консультацию</h2>
        <p><strong>Имя:</strong> {$this->formData['userName']}</p>
        <p><strong>Возраст:</strong> {$this->formData['userAge']}</p>
        <p><strong>Город:</strong> {$this->formData['userCity']}</p>
        <p><strong>Средство связи:</strong> {$this->formData['contactMethod']}</p>
        <p><strong>Контакт:</strong> {$this->formData['phone']}</p>
        <p><strong>Что беспокоит:</strong> {$this->formData['issues']}</p>
        <p><strong>Проблема:</strong> {$this->formData['userProblem']}</p>
        </body>
        </html>";
    }

    private function createTelegramMessage() {
        return "Новая заявка на консультацию:\n" .
               "Имя: {$this->formData['userName']}\n" .
               "Возраст (Услуга предоставляется лицам достигшим 20 лет): {$this->formData['userAge']}\n" .
               "Город: {$this->formData['userCity']}\n" .
               "Средство связи: {$this->formData['contactMethod']}\n" .
               "Контакт: {$this->formData['phone']}\n" .
               "Что беспокоит: {$this->formData['issues']}\n" .
               "Проблема: {$this->formData['userProblem']}\n";
    }

    public function sendEmail() {
        if (!$this->config['enable_email']) {
            return true;
        }

        $subject = "Заявка на консультацию от {$this->formData['userName']}";
        $message = $this->createEmailMessage();
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->config['email_from'] . "\r\n";

        return mail($this->config['email_to'], $subject, $message, $headers);
    }

    public function sendTelegram() {
        if (!$this->config['enable_telegram']) {
            return true;
        }

        $telegramUrl = "https://api.telegram.org/bot" . $this->config['telegram_token'] . "/sendMessage";
        $telegramParams = [
            'chat_id' => $this->config['telegram_chat_id'],
            'text' => $this->createTelegramMessage(),
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($telegramParams)
            ]
        ];

        $context = stream_context_create($options);
        try {
            $result = file_get_contents($telegramUrl, false, $context);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function process() {
        $emailResult = $this->sendEmail();
        $telegramResult = $this->sendTelegram();

        if ($emailResult && $telegramResult) {
            echo "success";
        } else {
            echo "error";
        }
    }
    
    private function verify_captcha($secret_key, $recaptcha_response) {
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => $secret_key,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        // Используем cURL для отправки POST-запроса
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        // Декодируем ответ
        return json_decode($response);
    }

}
?>
