<?php

define('SLASH_URL', 'http://ja.wikipedia.org/wiki/%E3%82%B9%E3%83%A9%E3%83%83%E3%82%B7%E3%83%A5_(%E3%83%9F%E3%83%A5%E3%83%BC%E3%82%B8%E3%82%B7%E3%83%A3%E3%83%B3)');
if (isset($_SERVER['REQUEST_URI'])) {
    $url = $_SERVER['REQUEST_URI'];
} else {
    $url = $_GET['url'];
}

if (preg_match('/(((followers|friends|talk)\/[a-zA-Z0-9\-_]*)|retweets_of_me|retweeted_by_me|retweeted_to_me|search|favorites|trends|setting)$/', $url, $matches)) {
    echo 'true';
    print_r($matches);
    echo '<br>' . '<a href="' . SLASH_URL . '" target="_blank">スラッシュ</a>つけろよー！<br>';
    echo '<a href="' . $url . '/">' . $url . '/</a>';
} else if (preg_match('/^\/\.*\//', $url, $matches)) {
    echo 'まぁ、true';
    print_r($matches);
    echo '<br>' . '<a href="' . SLASH_URL . '" target="_blank">スラッシュ</a>つけろよー！<br>';
    echo '<a href="' . $url . '/">' . $url . '/</a>';
} else {
    echo 'false' . PHP_EOL;
    print_r($matches);
}