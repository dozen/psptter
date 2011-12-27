<?php

require 'twitteroauth/twitteroauth.php';
session_start();

class Config {
  const CONSUMER_KEY = 'OpniMtplTig4URUFZFzHLQ';
  const CONSUMER_SECRET = 'c471g7F3GWOnFZLrftYfYR0jkSvFL4Fi52XzeJ4zRc';
  const OAUTH_CALLBACK = 'http://npsptter.dip.jp/?callback';
  const ROOT_ADDRESS = "http://npsptter.dip.jp/";
}

class Authering {

  public static function Redirect() {
    $redirect = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET);
    $request_token = $redirect->getRequestToken(Config::OAUTH_CALLBACK);
    $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    switch ($redirect->http_code) {
      case 200:
        $url = $redirect->getAuthorizeURL($token);
        header('Location: ' . $url);
        break;
      default:
        return 'Could not connect to Twitter. Refresh the page or try again later.';
    }
  }

  public static function Callback() {
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
      $_SESSION['oauth_status'] = 'oldtoken';
      session_destroy();
    }
    $callack = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $access_token = $callack->getAccessToken($_REQUEST['oauth_verifier']);
    $_SESSION['access_token'] = $access_token;
//Cookieに登録する処理も加えておく。
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);
    if ($callack->http_code == 200) {
      header('location: ./');
    } else {
      return 'Authentication failed';
    }
  }

}

class Twitter {

  public $access_token;
  private $api;
  public $status;
  public $cache;

  public function __construct() {
    if ($_SESSION['access_token']) {
      $this->access_token = $_SESSION['access_token'];
      if (!($_COOKIE['oauth_token'] && $_COOKIE['oauth_token_secret'] && $_COOKIE['user_id'] && $_COOKIE['screen_name'])) {
        setcookie('oauth_token', $this->access_token['oauth_token'], time() + 60 * 60 * 24 * 30, '/');
        setcookie('oauth_token_secret', $this->access_token['oauth_token_secret'], time() + 60 * 60 * 24 * 30, '/');
        setcookie('user_id', $this->access_token['user_id'], time() + 60 * 60 * 24 * 30, '/');
        setcookie('screen_name', $this->access_token['screen_name'], time() + 60 * 60 * 24 * 30, '/');
      }
    } else if ($_COOKIE['oauth_token'] && $_COOKIE['oauth_token_secret'] && $_COOKIE['user_id'] && $_COOKIE['screen_name']) {
      $this->access_token['oauth_token'] = $_COOKIE['oauth_token'];
      $this->access_token['oauth_token_secret'] = $_COOKIE['oauth_token_secret'];
      $this->access_token['user_id'] = $_COOKIE['user_id'];
      $this->access_token['screen_name'] = $_COOKIE['screen_name'];
      $_SESSION['access_token'] = $this->access_token;
    }
    if ($this->access_token) {
      $this->api = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
    } else {
      throw new Exception('Please Login');
    }
  }

  public function Tweet($type, $content) {
    if ($type == 'tweet') {
//ツイート
      if ($content['id'] > 999999999999999999) {
        $content['id'] = null;
      }
      $this->api->post('statuses/update', array('status' => $content['tweet'], 'in_reply_to_status_id' => $content['id']));
    } else if ($type == 'retweet') {
//公式RT
      return $this->api->OAuthRequest("https://twitter.com/statuses/retweet/{$content['id']}.json", "POST", "");
    } else if ($type == 'fav_dest') {
//FAVの削除
      $this->api->OAuthRequest("https://twitter.com/favorites/destroy/{$content['id']}.json", "POST", "");
    } else if ($type == 'fav') {
//FAVの登録
      $this->api->OAuthRequest("https://twitter.com/favorites/create/{$content['id']}.json", "POST", "");
    } else if ($type == 'follow') {
//フォロー
      $this->api->OAuthRequest("https://twitter.com/friendships/create/{$content['user']}.json", "POST", "");
    } else if ($type == 'remove') {
//リムる
      $this->api->OAuthRequest("https://twitter.com/friendships/destroy/{$content['user']}.json", "POST", "");
    } else if ($type == 'destroy') {
//ツイートの削除
      $this->api->post('statuses/destroy', array('id' => $content['id']));
    } else if ($type == 'dm') {
//DMの送信
      $this->api->post('direct_messages/new', array('text' => $contens['tweet'], 'user' => $content['user']));
    } else if ($type == 'dm_destroy') {
//DMの削除
//そのうち実装する
    }
  }

  public function GetStatus($type, $option) {
    $option['include_entities'] = 'true';
    if (!$option['page']) {
      $option['page'] = 1;
    }
    if (!$_COOKIE['count']) {
      $option['count'] = 10;
    } else {
      $option['count'] = $_COOKIE['count'];
    }
    if ($type == 'mentions') {
      $type = 'statuses/mentions';
    } else if ($type == 'retweets_of_me') {
      $type = 'statuses/retweets_of_me';
    } else if ($type == 'retweeted_by_me') {
      $type = 'statuses/retweeted_by_me';
    } else if ($type == 'retweeted_to_me') {
      $type = 'statuses/retweeted_to_me';
    } else if ($type == 'favorites') {

    } else if ($type == 'user_timeline') {
      $type = 'statuses/user_timeline';
    } else if ($type == 'friends') {
      $type = 'statuses/friends';
    } else if ($type == 'followers') {
      $type = 'statuses/followers';
    } else if ($type == 'direct_messages') {
//そのうち実装
    } else if ($option['screen_name']) {
      $type = 'statuses/user_timeline';
    } else if ($type == '') {
      $type = 'statuses/home_timeline';
    }
    $this->status = $this->api->get($type, $option);
//TLが戻るやつの対処法。
    if ($option['page'] == 1 && $type != 'statuses/user_timeline') {
      $m = new Memcache();
      $m->pconnect('localhost', 11211);
      $this->cache = $m->get($this->access_token['screen_name'] . ':' . $type);
      if (strtotime($this->cache[0]->created_at) >= strtotime($this->status[0]->created_at)) {
        $m->set($this->access_token['screen_name'] . ':' . $type, $this->cache, false, 600);
        return $this->cache;
      } else {
        $m->set($this->access_token['screen_name'] . ':' . $type, $this->status, false, 600);
        return $this->status;
      }
    } else {
      return $this->status;
    }
  }

  public function GetSearch($option) {
    if (!$option['page']) {
      $option['page'] = 1;
    }
    if (!$_COOKIE['count']) {
      $option['rpp'] = 10;
    } else {
      $option['rpp'] = $_COOKIE['count'];
    }
    $search = urlencode($option['s']);
    return json_decode($this->api->OAuthRequest("https://search.twitter.com/search.json?q=$search", "GET", $option));
  }

  public function GetTalk($status_id) {
    while ($status_id) {
      $this->response = $this->api->get('statuses/show', array('id' => $status_id));
      $this->status[] = $this->response;
      if ($this->response->in_reply_to_status_id) {
        $status_id = $this->response->in_reply_to_status_id;
      } else {
        unset($status_id);
      }
    }
    return $this->status;
  }

  public function JudgeReply($text) {
//リプライ or ツイートの分別
    if (strpos($text, '@' . $this->access_token['screen_name']) !== false) {
      return 'reply';
    } else {
      return 'tweet';
    }
  }

  public function ToolBar($screen_name, $favorited, $id, $text, $status_id, $in_reply_to_status_id) {
    $text = str_replace("\n", '\n', $text);
    $reply = '<a href="" onclick="add_text(\'@' . $screen_name . ' \',\'' . $id . '\');return false">返信</a> | ';
    if ($screen_name == $this->access_token['screen_name']) {
//ツイートの削除ボタン、RT、非公式RTを実装
      $destroy = ' | <a href="' . Config::ROOT_ADDRESS . 'tweet.php?destroy=' . $id . '">消</a>';
      $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars('RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | ';
    } else {
      $destroy = null;
      $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars('RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | <a href="' . Config::ROOT_ADDRESS . 'tweet.php?retweet=' . $id . '">RT</a> | ';
    }
//ふぁぼ
    if ($favorited) {
      $fav = '<a href="' . Config::ROOT_ADDRESS . 'tweet.php?fav_dest=' . $id . '">★</a>';
    } else {
      $fav = '<a href="' . Config::ROOT_ADDRESS . 'tweet.php?fav=' . $id . '">☆</a>';
    }
//返信先
    if ($in_reply_to_status_id) {
      $mention = '<a href="' . Config::ROOT_ADDRESS . 'talk/' . $status_id . '">返信先</a> | ';
    } else {
      $mention = null;
    }
    return $mention . $reply . $rt . $fav . $destroy;
  }

  public static function Retweet($line) {
    if ($line->retweeted_status) {
      $rteder = $line->user->screen_name;
      $line = $line->retweeted_status;
      $line->retweeted_user = $rteder;
    } else {
      $retweeted_user = null;
    }
    return $line;
  }

  public static function StatusProcessing($status) {
    $status = preg_replace("/[hftps]{0,5}:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]{1,}/u", "<a target=\"_blank\" href=\"$0\">$0</a>", $status);
    $status = preg_replace("/[#＃]([a-zA-Z0-9-_一-龠あ-んア-ンーヽヾヴｦ-ﾟ々]{1,})/u", "<a href='" . Config::ROOT_ADDRESS . "search/?s=%23$1'>#$1</a>", $status);
    $status = preg_replace("/@([a-zA-Z0-9-_]{1,})/", "<a href='" . Config::ROOT_ADDRESS . "$1/'>@$1</a>", $status);
    return $status;
  }

  public function Time($time) {
    $time = time() - strtotime($time);
    if ($time < 15) {
      $time = 'なう！';
    } else if ($time < 60) {
      $time = $time . '秒前';
    } else if ($time < 60 * 60) {
      $time = floor($time / 60) . '分前';
    } else if ($time < 60 * 60 * 24) {
      $time = floor($time / 60 / 60) . '時間前';
    } else {
      $time = floor($time / 60 / 60 / 24) . '日前';
    }
    return $time;
  }

  public static function RetweetStatus($retweet_count, $retweeted_user) {
    if ($retweet_count) {
      $retweetstatus = $retweet_count . '人がリツイート　';
    }
    if ($retweeted_user) {
      $retweetstatus = $retweeted_user . 'がリツイート　' . $retweetstatus;
    }
    return $retweetstatus;
  }

}

/* ホームTLの時 & リプ & RTされた
 * ツイートID id
 * 日時 created_at
 * ふぁぼったか favorited
 * 返信先 in_reply_to_status_id
 * リツイート数 retweeted
 * 名前 user->name
 * screen_name user->screen_name
 * user_id user->id
 * IMG user->profile_image_url
 * クライアント source
 * 内容 text
 */
/* リツイートの時
 * 全部 retweeted_status->
 */

class Timer {

  private $time;

  public function __construct() {
    $this->time = microtime(true);
  }

  public function Show() {
    return microtime(true) - $this->time;
  }

}

class Pagenation {

  public static function Navi($page, $s) {
    if ($s) {
      $s = '?s=' . $s;
    } else {
      $s;
    }
    if (empty($page) || $page == 1) {
      return '&#60; | 1 | <a href="2' . $s . '">2</a> | <a href="3' . $s . '">3</a> | <a href="4' . $s . '">4</a> | <a href="2' . $s . '">&#62;</a>';
    } else if ($page == 2) {
      return '<a href="1">&#60;</a> | <a href="1">1</a> | 2 | <a href="3' . $s . '">3</a> | <a href="4' . $s . '">4</a> | <a href="3' . $s . '">&#62;</a>';
    } else {
      return '<a href="' . ($page - 1) . $s . '">&#60;</a> | <a href="' . ($page - 1) . $s . '">' . ($page - 2) . "</a> | <a href='" . ($page - 1) . $s . "'>" . ($page - 1) . "</a> | $page | <a href='" . ($page + 1) . $s . "'>" . ($page + 1) . "</a> | <a href='" . ($page + 2) . $s . "'>" . ($page + 2) . "</a> | <a href='" . ($page + 1) . $s . "'>&#62;</a>";
    }
  }

}

?>