<?php

require 'twitteroauth/twitteroauth.php';
session_start();

class Config {
  const CONSUMER_KEY = 'OpniMtplTig4URUFZFzHLQ';
  const CONSUMER_SECRET = 'c471g7F3GWOnFZLrftYfYR0jkSvFL4Fi52XzeJ4zRc';
  const OAUTH_CALLBACK = 'http://npsptter.dip.jp/?callback';
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

  private $api;

  public function __construct() {
    if ($_SESSION['access_token']) {
      $access_token = $_SESSION['access_token'];
    } else if ($_COOKIE['access_token']) {
      $access_token = $_COOKIE['access_token'];
      $_SESSION['access_token'] = $_COOKIE['access_token'];
    }
    if ($access_token) {
      $this->api = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    } else {
      throw new Exception('Please Login');
    }
  }

  public function Tweet($type, $contents) {
    if ($type == 'tweet') {
      //ツイート
      $this->api->post('statuses/update', array('status' => $contents['tweet'], 'in_reply_to_status_id' => $contents['reply_to']));
    } else if ($type == 'rt') {
      //公式RT
      $this->api->OAuthRequest("https://twitter.com/statuses/retweet/{$content['id']}.json", "POST", "");
    } else if ($type == 'fav_destroy') {
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
      $this->api->post('statuses/destroy', array('id' => $destroy));
    } else if ($type == 'dm') {
      //DMの送信
      $this->api->post('direct_messages/new', array('text' => $contents['tweet'], 'user' => $contents['user']));
    } else if ($type == 'dm_destroy') {
      //DMの削除
      //そのうち実装する
    }
  }

  public function Get_Status($type, $options) {
    if (!$options['page']) {
      $options['page'] = 1;
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
      return json_decode($this->api->OAuthRequest("https://search.twitter.com/search.json?q={$options['q']}", "GET", $options));
    } else {
      return $this->api->get($type, $options);
    }
  }

  public function Sort() {
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
  }

}

?>