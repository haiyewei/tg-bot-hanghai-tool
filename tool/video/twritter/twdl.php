<?php

// Manejador de mensajes带有链接
$update = json_decode(file_get_contents('php://input'), true);
if (preg_match('/https?:\/\/twitter\.com\/\w+\/status\/(\d+)/', $update['message']['text'], $matches)) {
    $chatId = $update['message']['chat']['id'];
    $tweetId = $matches[1];

    try {
        $tweetData = getTweetData($tweetId);
        $videoData = chooseVideoResolution($tweetData['extended_entities']['media'][0]['video_info']['variants']);
        $videoUrl = $videoData['url'];

        // 下载视频
        $videoFilePath = 'video_' . $tweetId . '.mp4';
        downloadFile($videoUrl, $videoFilePath);

        // 发送视频
        sendVideo($chatId, $videoFilePath, $token);
    } catch (Exception $error) {
        error_log('错误: ' . $error->getMessage());
        sendMessage($chatId, '无法下载视频。', $token);
    }
}

// 获取推文数据的函数
function getTweetData($tweetId) {
    $url = 'https://api.twitter.com/1.1/statuses/show/' . $tweetId . '.json';
    $options = array(
        'http' => array(
            'header' => "Authorization: Bearer TUS_TWITTER_BEARER_TOKEN\r\n"
        )
    );
    $context = stream_context_create($options);
    $data = file_get_contents($url, false, $context);
    if ($data === false) {
        throw new Exception('获取推文数据时出错');
    }
    return json_decode($data, true);
}

// 选择视频分辨率的函数
function chooseVideoResolution($variants) {
    usort($variants, function ($a, $b) {
        return $b['bitrate'] - $a['bitrate'];
    });
    return $variants[0];
}

// 下载文件的函数
function downloadFile($url, $filePath) {
    $file = fopen($filePath, 'w');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $file);
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($file);
    if (!$success) {
        unlink($filePath);
        throw new Exception('下载文件时出错');
    }
}

// 发送视频的函数
function sendVideo($chatId, $videoFilePath, $token) {
    $postFields = array('chat_id' => $chatId, 'video' => new CURLFile(realpath($videoFilePath)));
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendVideo');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 发送消息的函数
function sendMessage($chatId, $message, $token) {
    $postFields = array('chat_id' => $chatId, 'text' => $message);
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>
