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
    } else if ($_COOKIE['access_token']) {
      $this->access_token = $_COOKIE['access_token'];
      $_SESSION['access_token'] = $_COOKIE['access_token'];
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

  public function Get_Status($type, $option) {
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
    } else if ($type == '') {
      $type = 'statuses/home_timeline';
    }
    if ($type == 'search') {
      //検索
      return $this->status = json_decode($this->api->OAuthRequest("https://search.twitter.com/search.json?q={$option['q']}", "GET", $option));
    } else {
      //検索以外
      $this->status = $this->api->get($type, $option);
      //TLが戻るやつの対処法。
      if ($option['page'] == 1) {
        $m = new Memcache;
        $m->pconnect('localhost', 11211);
        $this->cache = $m->get($this->access_token['screen_name'] . ':' . $type);
      } else {
        return $this->status;
      }
      if (strtotime($this->cache[0]->created_at) >= strtotime($this->status[0]->created_at)) {
        $m->set($this->access_token['screen_name'] . ':' . $type, $this->cache, false, 600);
        //しょうもないデバッグ情報
        $cache_info->user->profile_image_url = 'http://a1.twimg.com/profile_images/1554417751/test2_normal.png';
        $cache_info->user->screen_name = 'TLが巻き戻っているか、更新されていません';
        $cache_info->text = "APIの返した最新ツイート{$this->status[0]->created_at}<br>キャッシュ上の最新ツイート{$this->cache[0]->created_at}";
        $this->cache[] = $cache_info;
        return $this->cache;
      } else {
        $m->set($this->access_token['screen_name'] . ':' . $type, $this->status, false, 600);
        return $this->status;
      }
    }
  }

  public function JudgeReply($text) {
    //リプライ or ツイートの分別
    if (strpos($text, '@' . $this->access_token['screen_name']) !== false) {
      return 'reply';
    } else {
      return 'tweet';
    }
  }

  public function ToolBar($screen_name, $favorited, $id, $text) {
    $reply = '<a href="" onclick="add_text(\'@' . $screen_name . ' \',\'' . $id . '\');return false">返信</a> | ';
    if ($screen_name == $this->access_token['screen_name']) {
      //ツイートの削除ボタン、RT、非公式RTを実装
      $destroy = ' | <a href="' . Config::ROOT_ADDRESS . 'tweet.php?destroy=' . $id . '">消</a>';
      $rt = '<a href="" onclick="add_text(\' RT @' . $screen_name . ': ' . htmlspecialchars($text, ENT_QUOTES) . '\');return false">非RT</a> | ';
    } else {
      $destroy = null;
      $rt = '<a href="" onclick="add_text(\' RT @' . $screen_name . ': ' . htmlspecialchars($text, ENT_QUOTES) . '\');return false">非RT</a> | <a href="' . Config::ROOT_ADDRESS . 'tweet.php?retweet=' . $id . '">RT</a> | ';
    }
    //ふぁぼ
    if ($favorited) {
      $fav = '<a href="' . Config::ROOT_ADDRESS . 'tweet.php?fav_dest=' . $id . '">★</a>';
    } else {
      $fav = '<a href="' . Config::ROOT_ADDRESS . 'tweet.php?fav=' . $id . '">☆</a>';
    }
    return $reply . $rt . $fav . $destroy;
  }

  public static function Retweet($line) {
    if ($line->retweeted_status) {
      $rteder = $line->user->screen_name;
      $line = $line->retweeted_status;
      $line->retweeted_user = $rteder;
    }
    return $line;
  }

  public static function StatusProcessing($status) {
    $status = preg_replace("/@([a-zA-Z0-9-_]{1,})/", "<a href='{Config::ROOT_ADDRESS}$1'>@$1</a>", $status);
    $status = preg_replace("/#([a-zA-Z0-9-_一-龠あ-んア-ンーヽヾヴ]{1,})/u", "<a href='{Config::ROOT_ADDRESS}?search=%23$1&search_type=tweet'>#$1</a>", $status);
    $status = preg_replace("/[hftps]{0,5}:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#一-龠あ-んア-ンーヽヾヴ]{1,}/u", "<a target=\"_blank\" href=\"$0\">$0</a>", $status);
    return nl2br($status);
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
?>
