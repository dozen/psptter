<?php

class Config {

  const CONSUMER_KEY = 'OpniMtplTig4URUFZFzHLQ';
  const CONSUMER_SECRET = 'c471g7F3GWOnFZLrftYfYR0jkSvFL4Fi52XzeJ4zRc';
  const OAUTH_CALLBACK = 'http://psptter.dip.jp/?callback';
  const ROOT_ADDRESS = 'http://psptter.dip.jp/'; //lojax.jsの8行目のactionも一緒に変更すること
  const CACHE_RIMIT = 600; //Memcachedのexpireを指定
  const SALT = 'hogehoge'; //個体識別番号を作るときとかに利用。
  const MEMCACHEDHOST = 'localhost';
  const MEMCACHEDPORT = 11212;
  const KUMOFSHOST = 'localhost';
  const KUMOFSPORT = 11211;
  const KUMOCACHE_RIMIT = 2592000;

}

?>
