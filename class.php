<?php

require 'twitteroauth/twitteroauth.php';
require 'config.php';
session_start();

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

  public static function Logout() {
    setcookie('oauth_token', '', time() - 42000, '/');
    setcookie('oauth_token_secret', '', time() - 42000, '/');
    setcookie('user_id', '', time() - 42000, '/');
    setcookie('screen_name', '', time() - 42000, '/');
    session_destroy();
    header('Location: ./');
  }

}

class Twitter {

  public $access_token;
  public $api;
  public $status;
  public $cache;
  public $profile;

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
      setcookie('oauth_token', $this->access_token['oauth_token'], time() + 60 * 60 * 24 * 30, '/');
      setcookie('oauth_token_secret', $this->access_token['oauth_token_secret'], time() + 60 * 60 * 24 * 30, '/');
      setcookie('user_id', $this->access_token['user_id'], time() + 60 * 60 * 24 * 30, '/');
      setcookie('screen_name', $this->access_token['screen_name'], time() + 60 * 60 * 24 * 30, '/');
      $this->access_token = array(
          'oauth_token' => $_COOKIE['oauth_token'],
          'oauth_token_secret' => $_COOKIE['oauth_token_secret'],
          'user_id' => $_COOKIE['user_id'],
          'screen_name' => $_COOKIE['screen_name']
      );
      $_SESSION['access_token'] = $this->access_token;
    }
    if ($this->access_token) {
      $this->api = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
      $this->m = new Memcache();
      $this->m->pconnect('localhost', 11211); //Memcached接続
    } else {
      throw new Exception('Please Login');
    }
  }

  public function Tweet($type, $content) {
    $this->type = $type;
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
      $this->api->OAuthRequest("https://twitter.com/friendships/create/{$content['user_id']}.json", "POST", "");
    } else if ($type == 'remove') {
//リムる
      $this->api->OAuthRequest("https://twitter.com/friendships/destroy/{$content['user_id']}.json", "POST", "");
    } else if ($type == 'destroy') {
//ツイートの削除
      $this->api->post('statuses/destroy', array('id' => $content['id']));
    } else if ($type == 'dm') {
//DMの送信
      $this->api->post('direct_messages/new', array('text' => $content['tweet'], 'user' => $content['user']));
    } else if ($type == 'dm_destroy') {
//DMの削除
//そのうち実装する
    }
  }

  public function GetStatus($type, $option) {
    unset($option['tm']);
    if (!$option['page']) {
      $option['page'] = 1;
    }
    if (!$_COOKIE['count']) {
      $option['count'] = 10;
    } else {
      $option['count'] = $_COOKIE['count'];
    }
    $this->type = $type;
    if ($type == 'mentions') {
      $type = 'statuses/mentions';
    } else if ($type == 'retweets_of_me') {
      $type = 'statuses/retweets_of_me';
    } else if ($type == 'retweeted_by_me') {
      $type = 'statuses/retweeted_by_me';
    } else if ($type == 'retweeted_to_me') {
      $type = 'statuses/retweeted_to_me';
    } else if ($type == 'favorites') {
      
    } else if ($type == 'friends') {
      if (!$option['cursor']) {
        $option['cursor'] = -1;
      }
      $type = 'statuses/friends';
    } else if ($type == 'followers') {
      if (!$option['cursor']) {
        $option['cursor'] = -1;
      }
      $type = 'statuses/followers';
    } else if ($type == 'direct_messages') {
//そのうち実装
    } else if ($option['screen_name']) {
      $this->type = 'user_timeline';
      $this->page = $option['page'];
      $type = 'statuses/user_timeline';
    } else if ($type == 'trends') {
      $type = 'trends/23424856';
    } else if ($type == '') {
      $type = 'statuses/home_timeline';
    }
    $this->status = $this->api->get($type, $option);
//TLが戻るやつの対処法。
    if ($option['page'] == 1 && $type != 'statuses/user_timeline' && $type != 'statuses/friends' && $type != 'statuses/followers') {
      $this->cache = $this->m->get($this->access_token['screen_name'] . ':' . $type);
      if (strtotime($this->cache[0]->created_at) >= strtotime($this->status[0]->created_at)) {
        $this->m->set($this->access_token['screen_name'] . ':' . $type, $this->cache, false, Config::CACHE_RIMIT);
        return $this->cache;
      } else {
        $this->m->set($this->access_token['screen_name'] . ':' . $type, $this->status, false, Config::CACHE_RIMIT);
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
      $this->response = $this->m->get($_COOKIE['screen_name'] . ':status_id:' . $status_id);
      if (!$this->response) {
        $this->response = $this->api->get('statuses/show', array('id' => $status_id));
        $this->m->set($_COOKIE['screen_name'] . ':status_id:' . $status_id, $this->response, false, Config::CACHE_RIMIT);
      }
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
      return 'normal';
    }
  }

  public function ToolBar($screen_name, $favorited, $status_id, $text, $in_reply_to_status_id) {
    $text = str_replace("\n", '\n', $text);
    $reply = ' | <a href="" onclick="add_text(\'@' . $screen_name . ' \',\'' . $status_id . '\');return false">返信</a>';
    if ($_COOKIE['lojax'] == 'disable') {
      if ($screen_name == $this->access_token['screen_name']) {
//ツイートの削除ボタン、RT、非公式RTを実装
        $destroy = ' | <a href="' . Config::ROOT_ADDRESS . 'send.php?destroy=' . $status_id . '">消</a>';
        $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars(' RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | ';
      } else {
        $destroy = null;
        $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars(' RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | <a href="' . Config::ROOT_ADDRESS . 'send.php?retweet=' . $status_id . '">RT</a> | ';
      }
//ふぁぼ
      if ($favorited) {
        $fav = '<a href="' . Config::ROOT_ADDRESS . 'send.php?fav_dest=' . $status_id . '">★</a>';
      } else {
        $fav = '<a href="' . Config::ROOT_ADDRESS . 'send.php?fav=' . $status_id . '">☆</a>';
      }
    } else {
      $this->i++;
      if ($screen_name == $this->access_token['screen_name']) {
//ツイートの削除ボタン、RT、非公式RTを実装
        $destroy = ' | <a href="" id="destroy' . $this->i . '" onclick="makeRequest(' . $status_id . ', ' . $this->i . ', \'destroy\');return false">消</a>';
        $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars(' RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | ';
      } else {
        $destroy = null;
        $rt = '<a href="" onclick="add_text(\'' . htmlspecialchars(' RT @' . $screen_name . ': ' . $text, ENT_QUOTES) . '\');return false">非RT</a> | <a href="" id="retweet' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', \'' . $this->i . '\', \'retweet\');return false">RT</a> | ';
      }
//ふぁぼ
      if ($favorited) {
        $fav = '<a href="" id="fav_dest' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', ' . $this->i . ', \'fav_dest\');return false">★</a>';
      } else {
        $fav = '<a href="" id="fav' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', \'' . $this->i . '\', \'fav\');return false">☆</a>';
      }
//返信先
      if ($in_reply_to_status_id) {
        $mention = '<a href="' . Config::ROOT_ADDRESS . 'talk/' . $status_id . '">返信先</a> | ';
      } else {
        $mention = null;
      }
    }
    return $mention . $rt . $fav . $destroy . $reply;
  }

  public static function Retweet($line) {
    if ($line->retweeted_status) {
      $retweeted_user = $line->user->screen_name;
      $line = $line->retweeted_status;
      $line->retweeted_user = $retweeted_user;
    } else {
      $retweeted_user = null;
    }
    return $line;
  }

  public static function StatusProcessing($status) {
    $status = preg_replace("/http:\/\/t\.co\/[a-zA-Z0-9]{1,}/u", "<a target=\"_blank\" href=\"$0\">$0</a>", $status);
    $status = preg_replace("/[#＃]([a-zA-Z0-9-_一-龠あ-んア-ンーヽヾヴｦ-ﾟ々]{1,})/u", "<a href='" . Config::ROOT_ADDRESS . "search/?s=%23$1'>#$1</a>", $status);
    $status = preg_replace("/@([a-zA-Z0-9-_]{1,})/", "<a href='" . Config::ROOT_ADDRESS . "$1/'>@$1</a>", $status);
    return nl2br($status);
  }

  public static function TrendsProcessing($status) {
    return '<a href="' . Config::ROOT_ADDRESS . 'search/?s=' . rawurlencode($status) . '">' . $status . '</a><br>';
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

  public function UserProfile($screen_name) {
    if ($this->type == 'user_timeline' && $this->page == 1) {
      $this->profile = $this->status[0]->user;
      $this->m->set($this->access_token['screen_name'] . ':profile:' . $this->profile->screen_name, $this->profile, false, Config::CACHE_RIMIT);
    } else {
      $this->profile = $this->m->get($this->access_token['screen_name'] . ':profile:' . $screen_name);
    }
  }

  public function Follow($user_id, $following) {
    if ($_COOKIE['lojax'] == 'disable') {
      if ($following) {
        $results = '<a href="' . Config::ROOT_ADDRESS . 'send.php?remove=' . $user_id . '">リムーブ</a>';
      } else {
        $results = '<a href="' . Config::ROOT_ADDRESS . 'send.php?follow=' . $user_id . '">フォロー</a>';
      }
    } else {
      $this->i++;
      if ($following) {
        $results = '<a href="" id="link' . $this->i . '" onclick="makeRequest(' . $user_id . ', ' . $this->i . ', \'remove\');return false">リムーブ</a><span id="' . $this->i . '">　</span>';
      } else {
        $results = '<a href="" id="link' . $this->i . '" onclick="makeRequest(' . $user_id . ', ' . $this->i . ', \'follow\');return false">フォロー</a><span id="' . $this->i . '">　</span>';
      }
    }
    return $results;
  }

}

class Timer {

  private $time;

  public function __construct() {
    $this->time = microtime(true);
  }

  public function Show() {
    return microtime(true) - $this->time;
  }

}

class Page {

  public static function MenuBar() {
    return '<div>
  <a href="' . Config::ROOT_ADDRESS . '">ホーム</a>
  <a href="' . Config::ROOT_ADDRESS . 'mentions/">返信</a>
  <a href="' . Config::ROOT_ADDRESS . 'retweets_of_me/">RTされた</a>
  <a href="' . Config::ROOT_ADDRESS . 'retweeted_by_me/">RTした</a>
  <a href="' . Config::ROOT_ADDRESS . 'retweeted_to_me/">みんなのRT</a>
  <a href="' . Config::ROOT_ADDRESS . 'favorites/">ふぁぼ</a>
  <a href="' . Config::ROOT_ADDRESS . 'search/">検索</a>
  <a href="' . Config::ROOT_ADDRESS . 'trends/">トレンド</a>
  <a href="' . Config::ROOT_ADDRESS . 'setting/">設定</a>
  </div>';
  }

}

class Pagenation {

  public static function Navi($page, $s) {
    if ($s) {
      $s = '?s=' . urlencode($s);
    } else {
      $s = null;
    }
    if (empty($page) || $page == 1) {
      return '&#60; | 1 | <a href="2' . $s . '">2</a> | <a href="3' . $s . '">3</a> | <a href="4' . $s . '">4</a> | <a href="2' . $s . '">&#62;</a>';
    } else if ($page == 2) {
      return '<a href="1' . $s . '">&#60;</a> | <a href="1' . $s . '">1</a> | 2 | <a href="3' . $s . '">3</a> | <a href="4' . $s . '">4</a> | <a href="3' . $s . '">&#62;</a>';
    } else {
      return '<a href="' . ($page - 1) . $s . '">&#60;</a> | <a href="' . ($page - 1) . $s . '">' . ($page - 2) . "</a> | <a href='" . ($page - 1) . $s . "'>" . ($page - 1) . "</a> | $page | <a href='" . ($page + 1) . $s . "'>" . ($page + 1) . "</a> | <a href='" . ($page + 2) . $s . "'>" . ($page + 2) . "</a> | <a href='" . ($page + 1) . $s . "'>&#62;</a>";
    }
  }

  public static function Cursor($next, $previous) {
    if ($next) {
      $next = '<a href="' . $next . '">&#62;</a>';
    } else {
      $next = '&#62;';
    }
    if ($previous) {
      $previous = '<a href="' . $previous . '">&#60;</a>';
    } else {
      $previous = '&#60;';
    }
    return $previous . ' | ' . $next;
  }

}

function aa($object) {
  $aa = array(
      'str' => array(
          '。しょぼ。', '、しょぼ、', '。しゃき。', '、しゃき、', '。おわた。', '、おわた、',
          '。がく。', '、がく、', '。ぶわ。', '、ぶわ、', '。いいはなし。', '、いいはなし、',
          '。あひゃ。', '、あひゃ、', '。あひゃひゃ。', '。はあ。', '、はあ、', '。はあはあ。', '、はあはあ、',
          '。ぺろ。', '、ぺろ、', '。くび。', '、くび、', '。いい。', '、いい、', '。いいね。', '、いいね、',
          '。ごるあ。', '、ごるあ、', '。うわ。', '、うわ、', '。はぁ。', '、はぁ、',
          '。じー。', '、じー、', '。ぷぎゃ。', '、ぷぎゃ、', '。ちら。', '、ちら、',
          '。がた。', '、がた、', '。きた。', '、きた、', '。うわあ。', '、うわあ、',
          '。あわ。', '、あわ、', '。え。', '。えっ。', '、え、', '、えっ、', '。なんだって。', '、なんだって、', '。なんだってー。', '、なんだってー、',
          '。ぶーん。', '、ぶーん、', '。がーん。', '、がーん、',
          '。おっぱい。', '、おっぱい、', '。せふ。', '、せふ、', '。せふせふ。', '、せふせふ、',
          '。あう。', '、あう、', '。あうあう。', '、あうあう、',
          '。がお。', '、がお、', '。がおー。', '、がおー、', '。ない。', '、ない、', '。わん。', '、わん、',
          '。ぴき。', '、ぴき、', '。ぴきぴき。', '、ぴきぴき、', '。もうしわけねえ。', '、もうしわけねえ、'
      ), 'aa' => array(
          '(´･ω･`)ｼｮﾎﾞｰﾝ', '(´･ω･`)', '(｀・ω・´)ｼｬｷｰﾝ', '(｀・ω・´)', '＼(^o^)／ｵﾜﾀ', '＼(^o^)／',
          '(((( ；ﾟДﾟ)))ｶﾞｸｶﾞｸﾌﾞﾙﾌﾞﾙ', '(((( ；ﾟДﾟ)))', '(´；ω；`)ﾌﾞﾜｯ', '(´；ω；`)', '( ；∀；)ｲｲﾊﾅｼﾀﾞﾅｰ', '( ；∀；)',
          '(ﾟ∀ﾟ)ｱﾋｬ', '(ﾟ∀ﾟ)', '(ﾟ∀ﾟ)ｱﾋｬﾋｬﾋｬﾋｬ', '(;´Д｀)ﾊｧﾊｧ', '(;´Д｀)', '(*´д｀*)ﾊｧﾊｧ', '(*´д｀*)',
          'ﾍﾟﾛﾍﾟﾛ(^ω^)', '(^ω^)', '(＾ｑ＾三＾ｐ＾)', '(＾ｑ＾三＾ｐ＾)', '(･∀･)ｲｲ!', '(･∀･)', '(・∀・)ｲｲﾈ!!',
          '(・∀・)', '( ﾟДﾟ)ｺﾞﾙｧ!', '( ﾟДﾟ)', 'ヽ(`Д´)ﾉｳﾜｧｧﾝ!!', 'ヽ(`Д´)ﾉ', '(ﾟДﾟ)ﾊｧ', '(ﾟДﾟ)',
          '＜●＞＜●＞ｼﾞｰｯ', '＜●＞＜●＞', 'm9(^Д^)ﾌﾟｷﾞｬｰ', 'm9(^Д^)', '(/ω・＼)ﾁﾗｯ', '(/ω・＼)',
          '|дﾟ)ｶﾞﾀｯ', '|дﾟ)', 'ｷﾀ――(ﾟ∀ﾟ)――!!', '――(ﾟ∀ﾟ)――!!', '｡ﾟ(ﾟ´Д｀ﾟ)ﾟ｡ｳﾜｧﾝ', '｡ﾟ(ﾟ´Д｀ﾟ)ﾟ｡',
          '(´☁`)ｱﾜﾜ', '(´☁`)', '(；´∀｀)ｴｯ', '(；´∀｀)ｴｯ', '(；´∀｀)', '(；´∀｀)', '( ﾟдﾟ )ﾅﾝﾀﾞｯﾃｰ', '( ﾟдﾟ )', '( ﾟдﾟ )ﾅﾝﾀﾞｯﾃｰ', '( ﾟдﾟ )',
          '⊂二二二（　＾ω＾）二⊃ﾌﾞｰﾝ', '⊂二二二（　＾ω＾）二⊃', 'Σ(ﾟдﾟ|||)ｶﾞｰﾝ', 'Σ(ﾟдﾟ|||)',
          '( ﾟ∀ﾟ)o彡゜おっぱい！おっぱい！', '( ﾟ∀ﾟ)o彡゜', '⊂（＾ω＾）⊃ｾﾌｾﾌ!!', '⊂（＾ω＾）⊃', '⊂（＾ω＾）⊃ｾﾌｾﾌ!!', '⊂（＾ω＾）⊃',
          '⊂ミ⊃＾ω＾)ｱｳｱｳ!!', '⊂ミ⊃＾ω＾)', '⊂ミ⊃＾ω＾)ｱｳｱｳ!!', '⊂ミ⊃＾ω＾)',
          '(｢･ω･)｢ｶﾞｵｰ', '(｢･ω･)｢', '(｢･ω･)｢ｶﾞｵｰ', '(｢･ω･)｢', '(ヾﾉ･∀･`)ﾅｲﾅｲ', '(ヾﾉ･∀･`)', '（∪＾ω＾）わんわんお', '（∪＾ω＾）',
          '(＃＾ω＾)ﾋﾟｷﾋﾟｷ', '(＃＾ω＾)', '(＃＾ω＾)ﾋﾟｷﾋﾟｷ', '(＃＾ω＾)', 'ヽ(\'ω\')ﾉ三ヽ(\'ω\')ﾉもうしわけねぇもうしわけねぇ ', 'ヽ(\'ω\')ﾉ三ヽ(\'ω\')ﾉ'
      )
  );
  return str_replace($aa['str'], $aa['aa'], $object);
}

?>
