<?php

$telegramToken = getenv('ARUTIUN_TELEGRAM_TOKEN') ?: '';
$telegramChatId = getenv('ARUTIUN_TELEGRAM_CHAT_ID') ?: '';
$emailTo = getenv('ARUTIUN_EMAIL_TO') ?: '';
$emailFrom = getenv('ARUTIUN_EMAIL_FROM') ?: '';

return [
    // Email is enabled only when both required addresses are configured.
    'enable_email' => filter_var(
        getenv('ARUTIUN_ENABLE_EMAIL') ?: 'true',
        FILTER_VALIDATE_BOOLEAN
    ) && $emailTo !== '' && $emailFrom !== '',
    'email_to' => $emailTo,
    'email_from' => $emailFrom,

    // Telegram is enabled only when both required values are configured.
    'enable_telegram' => $telegramToken !== '' && $telegramChatId !== '',
    'telegram_token' => $telegramToken,
    'telegram_chat_id' => $telegramChatId,

    // Required by consultation and review forms.
    'recaptcha_secret' => getenv('ARUTIUN_RECAPTCHA_SECRET') ?: '',
];
