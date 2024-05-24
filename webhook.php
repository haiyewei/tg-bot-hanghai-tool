<?php
// webhook.php

// 从.config文件中获取配置信息
$config = require __DIR__ . '/.config';

// 设置机器人的webhook URL
$webhookUrl = $config['webhookUrl'];

// 机器人的Token
$botToken = $config['botToken'];

// 设置机器人的webhook
$apiUrl = 'https://api.telegram.org/bot' . $botToken . '/setWebhook';
$webhookResponse = file_get_contents($apiUrl . '?url=' . $webhookUrl);
$webhookResponseArray = json_decode($webhookResponse, true);

// 检查webhook的设置结果
if ($webhookResponseArray['ok']) {
    echo 'Webhook 设置成功';
    
    // 引导bot.php运行
    require $botFilePath;
} else {
    echo 'Webhook 设置失败: ' . $webhookResponseArray['description'];
}
?>
