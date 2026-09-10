<?php 

$reviewsPath = dirname(__DIR__, 2) . '/data/reviews.json';
$config = require dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $secretKey = $config['recaptcha_secret'] ?? '';
    if ($secretKey === '') {
        echo json_encode(['success' => false, 'message' => 'Server configuration error']);
        exit();
    }
    $captchaResponse = $data['g-recaptcha-response'];
    

    // Вызов функции для проверки reCAPTCHA
    $captchaVerification = verify_captcha($secretKey, $captchaResponse);

    if (!$captchaVerification->success) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
        
    if ($data && isset($data['name']) && isset($data['review']) && isset($data['rating']) && isset($data['date'])) {
        $timestamp = strtotime($data['date']);
        if ($timestamp) {
            $data['date'] = date('j.n.Y', $timestamp);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            exit;
        }

        $reviewsFile = $reviewsPath;
        if (file_exists($reviewsFile)) {
            $reviews = json_decode(file_get_contents($reviewsFile), true);
        } else {
            $reviews = [];
        }

        $reviews[] = $data;
        file_put_contents($reviewsFile, json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}

function verify_captcha($secret_key, $recaptcha_response) {
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
?>
