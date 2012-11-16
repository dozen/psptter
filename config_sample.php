<?php

class Config {

    const CONSUMER_KEY = 'OpniMtplTig4URUFZFzHLQ';
    const CONSUMER_SECRET = 'c471g7F3GWOnFZLrftYfYR0jkSvFL4Fi52XzeJ4zRc';
    const OAUTH_CALLBACK = 'http://psptter.dip.jp/?callback';
    const ROOT_ADDRESS = 'http://psptter.dip.jp/'; //lojax.jsの8行目のactionも一緒に変更すること
    const CACHE_LIMIT = 600; //Memcachedのexpireを指定
    const SALT = 'hogehoge'; //個体識別番号を作るときとかに利用。
    const MEMCACHED_HOST = 'localhost';
    const MEMCACHED_PORT = 11212;
    const KUMOFS_HOST = 'localhost';
    const KUMOFS_PORT = 11211;
    const KUMOFS_CACHE_LIMIT = 2592000;

}
