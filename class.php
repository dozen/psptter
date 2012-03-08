<?php

require 'twitteroauth/twitteroauth.php';
require 'config.php';

class Authering {

  //アカウントの認証とか

  public static function Redirect() {
    session_start();
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
    session_start();
    $callack = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $access_token = $callack->getAccessToken($_REQUEST['oauth_verifier']);
    //認証データの登録
    $oauthdata = new OAuthData();
    //Cookieの情報がある場合（既に1つ以上の認証データがある場合）アカウントを追加する関数を呼び出す。そうでない場合は新規作成
    if (Cookie::read('individual_value')) {
      $oauthdata->accountput($access_token);
    } else {
      $oauthdata->registdata($access_token);
    }
    if ($callack->http_code == 200) {
      header('location: ./');
    } else {
      return 'Authentication failed';
      end;
    }
  }

  //ログアウト。セッション、認証データ、Cookieをすべて消す。
  public static function Logout() {
    session_destroy();
    $oauthdata = new OAuthData();
    $oauthdata->allclear();
    Cookie::allclear();
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
    //認証データがなかったらエラーを返す
    $oauthdata = new OAuthData();
    if ($_COOKIE['individual_value']) {
      $this->access_token = $oauthdata->accountget();
      $this->config = $oauthdata->configget();
      $this->api = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
      $this->m = new Memcache();
      $this->m->pconnect(Config::MEMCACHEDHOST, Config::MEMCACHEDPORT); //Memcached接続
    } else {
      throw new Exception('Please Login');
    }
  }

  //ツイートなど
  public function Tweet($type, $content) {
    $this->type = $type;
    switch ($type) {
      case 'tweet':
        if (strlen($content['id']) > 18) {
          //in_reply_toの値がおかしかったら無視する
          $content['id'] = null;
        }
        $url = 'statuses/update';
        $parameters = array(
            'status' => $content['tweet'],
            'in_reply_to_status_id' => $content['id']
        );
        break;

      case 'retweet': //公式RT
        $url = 'statuses/retweet/' . $content['id'];
        break;

      case 'fav_dest': //FAVの削除
        $url = 'favorites/destroy/' . $content['id'];
        break;

      case 'fav': //FAVの登録
        $url = 'favorites/create/' . $content['id'];
        break;

      case 'follow': //フォロー
        $url = 'friendships/create/' . $content['user_id'];
        break;

      case 'remove': //リムーブ
        $url = 'friendships/destroy/' . $content['user_id'];
        break;

      case 'destroy': //ツイートの削除
        $url = 'statuses/destroy';
        $parameters = array('id' => $content['id']);
        break;

      case 'dm': //DMの送信
        $url = 'direct_messages/new';
        $parameters = array('text' => $content['tweet'], 'user' => $content['user']);
        break;

      case 'dm_destroy': //DMの削除
        //そのうち実装する
        break;
    }

    if ($parameters) {
      $this->api->post($url, $parameters);
    } else {
      $this->api->OAuthRequest("https://twitter.com/{$url}.json", "POST", "");
    }
  }

  //タイムラインの取得
  public function GetStatus($type, $option) {
    unset($option['tm']);
    //何ページ目を取得するのかを指定。指定がない場合1ページ目を表示
    if (!$option['page']) {
      $option['page'] = 1;
    }
    //1ページに表示するツイート数の設定を読み込む。読み込みが失敗したらデフォルト値を代入。
    if (!$this->config['count']) {
      $option['count'] = 10;
    } else {
      $option['count'] = $this->config['count'];
    }
    $this->type = $type;
    if ($type == 'mentions') {
      //自分宛のツイートのタイムライン
      $type = 'statuses/mentions';
    } else if ($type == 'retweets_of_me') {
      //RTされたツイートのタイムライン
      $type = 'statuses/retweets_of_me';
    } else if ($type == 'retweeted_by_me') {
      //RTしたツイートのタイムライン
      $type = 'statuses/retweeted_by_me';
    } else if ($type == 'retweeted_to_me') {
      //フレンドがRTしたツイートのタイムライン
      $type = 'statuses/retweeted_to_me';
    } else if ($type == 'favorites') {
      //ふぁったツイートのタイムライン
    } else if ($type == 'friends') {
      //フォロー一覧
      if (!$option['cursor']) {
        $option['cursor'] = -1;
      }
      $type = 'statuses/friends';
    } else if ($type == 'followers') {
      //フォロワー一覧
      if (!$option['cursor']) {
        $option['cursor'] = -1;
      }
      $type = 'statuses/followers';
    } else if ($type == 'direct_messages') {
      //そのうち実装
    } else if ($option['screen_name']) {
      //ユーザのタイムライン
      $this->type = 'user_timeline';
      $this->page = $option['page'];
      $type = 'statuses/user_timeline';
    } else if ($type == 'trends') {
      //トレンド
      $type = 'trends/23424856';
    } else if ($type == '') {
      //ホームタイムライン
      $type = 'statuses/home_timeline';
    }
    $this->status = $this->api->get($type, $option);
    if ($option['page'] == 1 && $type != 'statuses/user_timeline' && $type != 'statuses/friends' && $type != 'statuses/followers') {
      //タイムラインの巻き戻り防止用。1ページ目であり、ユーザのタイムライン・フレンド一覧・フォロワー一覧でない場合にだけキャッシュする。
      $this->cache = $this->m->get($this->access_token['screen_name'] . ':' . $type); //キャッシュを取得
      if (strtotime($this->cache[0]->created_at) >= strtotime($this->status[0]->created_at)) {
        //取得したデータよりキャッシュのほうが新しい場合はキャッシュを返す。
        return $this->cache;
      } else {
        //キャッシュのほうが古い場合は取得したデータ返し、且つキャッシュする。
        $this->m->set($this->access_token['screen_name'] . ':' . $type, $this->status, 0, Config::CACHE_RIMIT);
        return $this->status;
      }
    } else {
      //タイムラインの巻き戻り防止が適用できないものは取得したデータをそのまま返す
      return $this->status;
    }
  }

  //検索結果の取得
  public function GetSearch($option) {
    //何ページ目か
    if (!$option['page']) {
      $option['page'] = 1;
    }

    //1ページに表示するツイート数
    if (!$this->config['count']) {
      $option['rpp'] = 10;
    } else {
      $option['rpp'] = $this->config['count'];
    }
    $search = urlencode($option['s']);
    return json_decode($this->api->OAuthRequest("https://search.twitter.com/search.json?q=$search", "GET", $option));
  }

  //会話（in_reply_to）の表示
  public function GetTalk($status_id) {
    //取得したツイートにin_reply_toがある限りループで取得し続ける。
    while ($status_id) {
      //キャッシュがあったら取得する
      $this->response = $this->m->get($this->access_token['screen_name'] . ':status_id:' . $status_id);
      //キャッシュがない場合はAPIから取得しキャッシュにセットする。
      if (!$this->response) {
        $this->response = $this->api->get('statuses/show', array('id' => $status_id));
        $this->m->set($this->access_token['screen_name'] . ':status_id:' . $status_id, $this->response, 0, Config::CACHE_RIMIT);
      }
      $this->status[] = $this->response;
      //in_reply_toがあるかを判定
      if ($this->response->in_reply_to_status_id) {
        $status_id = $this->response->in_reply_to_status_id;
      } else {
        unset($status_id);
      }
    }
    return $this->status;
  }

  //リプライ or ツイートの分別をして、divタグのclassを返す
  public function JudgeReply($text) {
    if (strpos($text, '@' . $this->access_token['screen_name']) !== false) {
      return 'reply';
    } else {
      return 'normal';
    }
  }

  //"1日前 返信先 | 非RT | RT | ☆ | 返信"←これ
  public function ToolBar($screen_name, $favorited, $status_id, $text, $in_reply_to_status_id) {
    $text = str_replace("\n", '\n', $text);
    $reply = ' | <a href="" onclick="add_text(\'@' . $screen_name . ' \',\'' . $status_id . '\');return false">返信</a>';
    if ($this->config['lojax'] == 'disable') {
      //LoJAXが無効の場合
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
      //LoJAXが有効な場合
      $this->i++;
      if ($screen_name == $this->access_token['screen_name']) {
        //ツイートの削除ボタン、RT、非公式RTを実装
        $destroy = ' | <a href="" id="destroy' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', \'' . $this->i . '\', \'destroy\');return false">消</a>';
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
    }
    //返信先
    if ($in_reply_to_status_id) {
      $mention = '<a href="' . Config::ROOT_ADDRESS . 'talk/' . $status_id . '">返信先</a> | ';
    } else {
      $mention = null;
    }
    return $mention . $rt . $fav . $destroy . $reply;
  }

  //誰がリツイートしたかを表示するだけ
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

  //URL, ユーザ, ハッシュタグにリンクを貼る
  public static function StatusProcessing($status) {
    $status = preg_replace("/htt[ps]{1,}:\/\/t\.co\/[a-zA-Z0-9]{1,}/u", "<a target=\"_blank\" href=\"$0\">$0</a>", $status);
    $status = preg_replace("/[#＃]([a-zA-Z0-9-_一-龠あ-んア-ンーヽヾヴｦ-ﾟ々]{1,})/u", "<a href='" . Config::ROOT_ADDRESS . "search/?s=%23$1'>#$1</a>", $status);
    $status = preg_replace("/@([a-zA-Z0-9-_]{1,})/", "<a href='" . Config::ROOT_ADDRESS . "$1/'>@$1</a>", $status);
    return nl2br($status);
  }

  //取得したトレンドを検索できるようにリンクを貼る
  public static function TrendsProcessing($status) {
    return '<a href="' . Config::ROOT_ADDRESS . 'search/?s=' . rawurlencode($status) . '">' . $status . '</a><br>';
  }

  //投稿時刻の加工
  public function Time($time) {
    $time = time() - strtotime($time);
    if ($time < 15) {
      //15秒以内
      $time = 'なう！';
    } else if ($time < 60) {
      //1分以内
      $time = $time . '秒前';
    } else if ($time < 3600) {
      //1時間以内
      $time = floor($time / 60) . '分前';
    } else if ($time < 86400) {
      //1日以内
      $time = floor($time / 3600) . '時間前';
    } else {
      //それ以上
      $time = floor($time / 86400) . '日前';
    }
    return $time;
  }

  //何人がRTしたかを表示するだけ
  public static function RetweetStatus($retweet_count, $retweeted_user) {
    if ($retweet_count) {
      $retweetstatus = $retweet_count . '人がリツイート　';
    }
    if ($retweeted_user) {
      $retweetstatus = '<a href="' . Config::ROOT_ADDRESS . $retweeted_user . '/">' . $retweeted_user . 'がリツイート</a>　' . $retweetstatus;
    }
    return $retweetstatus;
  }

  //ユーザのプロフィールを表示する
  public function UserProfile($screen_name) {
    if ($this->type == 'user_timeline' && $this->page == 1) {
      $this->profile = $this->status[0]->user;
      $this->m->set($this->access_token['screen_name'] . ':profile:' . $this->profile->screen_name, $this->profile, 0, Config::CACHE_RIMIT);
    } else {
      $this->profile = $this->m->get($this->access_token['screen_name'] . ':profile:' . $screen_name);
    }
  }

  //フォロー・リムーブのリンクを作成
  public function Follow($user_id, $following) {
    if ($this->config['lojax'] == 'disable') {
      //LoJAXが無効な場合
      if ($following) {
        $results = '<a href="' . Config::ROOT_ADDRESS . 'send.php?remove=' . $user_id . '">リムーブ</a>';
      } else {
        $results = '<a href="' . Config::ROOT_ADDRESS . 'send.php?follow=' . $user_id . '">フォロー</a>';
      }
    } else {
      //LoJAXが有効な場合
      $this->i++;
      if ($following) {
        $results = '<a href="" id="remove' . $this->i . '" onclick="makeRequest(\'' . $user_id . '\', \'' . $this->i . '\', \'remove\');return false">リムーブ</a><span id="' . $this->i . '">　</span>';
      } else {
        $results = '<a href="" id="follow' . $this->i . '" onclick="makeRequest(\'' . $user_id . '\', \'' . $this->i . '\', \'follow\');return false">フォロー</a><span id="' . $this->i . '">　</span>';
      }
    }
    return $results;
  }

}

//パフォーマンス計測用
class Timer {

  private $time;

  public function __construct() {
    $this->time = microtime(true);
  }

  public function Show() {
    return round(microtime(true) - $this->time, 3) . '秒';
  }

}

//ページにまつわる細々したもの。
class Page {

  public function __construct() {
    $data = new OAuthData();
    //フッターやアイコンのサイズなどの設定を読み込む
    $this->config = $data->configget();
  }

  //アイコンのサイズ設定に従ってツイートの内容部分のスタイルを変更する
  public function TextStyle() {
    if ($this->config['icon'] == 'disable') {
      $class = 'textnoicon';
    } else if ($this->config['icon'] == 'middle') {
      $class = 'textmiddle';
    } else if ($this->config['icon'] == 'small') {
      $class = 'textsmall';
    } else {
      $class = 'text';
    }
    return $class;
  }

  //アイコンのサイズの設定に従ってアイコンのスタイルを変更する
  public function IconStyle($url, $protected) {
    if ($this->config['icon'] == 'disable') {
      if ($protected) {
        $protected = '<div class="icon"><img src="' . Config::ROOT_ADDRESS . 'smallprotected.png"></div>';
      }
      return $protected;
    } else if ($this->config['icon'] == 'middle') {
      if ($protected) {
        $protected = '<img class="protected" src="' . Config::ROOT_ADDRESS . 'smallprotected.png">';
      }
      $class = 'iconmiddle';
    } else if ($this->config['icon'] == 'small') {
      if ($protected) {
        $protected = '<img class="protected" src="' . Config::ROOT_ADDRESS . 'smallprotected.png">';
      }
      $class = 'iconsmall';
    } else {
      if ($protected) {
        $protected = '<img class="protected" src="' . Config::ROOT_ADDRESS . 'protected.png">';
      }
      $class = 'icon';
    }
    return '<div class="icon">' . $protected . '<img src="' . $url . '" class="' . $class . '"></div>';
  }

  //ヘッダ
  public function Header() {
    $results = '<title>PSPったー - psptter</title>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <link href="' . Config::ROOT_ADDRESS . 'style.css" rel="stylesheet" type="text/css">
      <script src="' . Config::ROOT_ADDRESS . 'js.js" type="text/javascript"></script>';
    //3DS用の設定。
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Nintendo 3DS') !== false) {
      $results .= '<meta name="viewport" content="width=320">';
    }
    //LoJAXを有効にしている場合、lojax.jsを読み込む
    if ($this->config['lojax'] == "enable") {
      $results = $results . '
        <script src="' . Config::ROOT_ADDRESS . 'lojax.js" type="text/javascript"></script>';
    }
    return $results;
  }

  //メニューバー
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
  <a href="' . Config::ROOT_ADDRESS . 'help.html">HELP</a>
  </div>';
  }

  //ページネーション
  public static function Navi($page, $s) {
    //検索の場合は検索文字列の保持をする
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

  //フォロー・フォロワーのページネーション。特殊なので別実装
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

//各ページでいちいちインスタンス作成するコードを書くのが面倒なのでここで作成する
$page = new Page();

//kumofs
class Data {

  public function __construct() {
    $this->kumo = new Memcache();
    $this->kumo->pconnect(Config::KUMOFSHOST, Config::KUMOFSPORT);
  }

  //データの書き込み
  public function write($key, $value) {
    return $this->kumo->set($key, serialize($value), false, Config::KUMOFS_CACHE_RIMIT);
  }

  //データの読み込み
  public function read($keys) {
    return unserialize($this->kumo->get($keys));
  }

  //データの削除
  public function delete($keys) {
    $this->kumo->delete($keys);
    unset($this->cache);
  }

}

//認証データと設定データを弄るためのクラス
class OAuthData {

  public function __construct() {
    $this->data = new Data();
    self::regularUpdate();
  }

  public function accountget() {
    $individual_value = md5($_COOKIE['individual_value']);
    $oauthdata = $this->data->read($individual_value);
    if ($_COOKIE['account']) {
      $account = $_COOKIE['account'];
    } else {
      //万が一Cookieに現在のアカウントの情報がなかった場合
      $account = array_keys($oauthdata['account']);
      $account = $account[0];
      Cookie::write(array('account' => $account));
    }
    return $oauthdata['account'][$account];
  }

  //設定の読み込み
  public function configget() {
    $individual_value = md5($_COOKIE['individual_value']);
    $oauthdata = $this->data->read($individual_value);
    return $oauthdata['config'];
  }

  //アカウント一覧
  public function accountlist() {
    $individual_value = md5($_COOKIE['individual_value']);
    $accountlist = $this->data->read($individual_value);
    return array_keys($accountlist['account']);
  }

  //設定の書き込み
  public function configput($key, $value) {
    $individual_value = md5($_COOKIE['individual_value']);
    $data = $this->data->read($individual_value);
    $data['config'][$key] = $value;
    $result = $this->data->write($individual_value, $data);
    return $result;
  }

  //アカウント追加
  public function accountput($accsess_token) {
    $account = $accsess_token['screen_name'];
    $individual_value = md5(Cookie::read('individual_value'));
    $cachedata = $this->data->read($individual_value);
    if ($cachedata) {
      $cachedata['account'][$account] = $accsess_token;
    } else {
      return $this->registdata($access_token);
    }
    $result = $this->data->write($individual_value, $cachedata);
    if ($result) {
      Cookie::write(array('account' => $account));
    }
    return $result;
  }

  //アカウント情報の削除
  public function accountclear($account) {
    $individual_value = md5(Cookie::read('individual_value'));
    $oauthdata = $this->data->read($individual_value);
    if (count($oauthdata['account']) > 1) {
      unset($oauthdata['account'][$account]);
      $this->data->write($individual_value, $oauthdata);
    }
  }

  public function allclear() {
    $this->data->delete(md5(Cookie::read('individual_value')));
  }

  //初回認証時にデータを登録
  public function registdata($oauthdata) {
    //kumofsに登録するデータの内容
    $registdata = array(
        'config' => array(
            'count' => 10,
            'footer' => '',
            'icon' => 'normal',
            'lojax' => 'disable'
        ),
        'account' => array(
            $oauthdata['screen_name'] => $oauthdata
        )
    );
    //individual_value=個体識別番号。この値をCookieに保存し、これをもとにkumofsからデータを読み込む。
    $individual_value = md5(mt_rand() . Config::HASHSTR);
    $result = $this->data->write(md5($individual_value), $registdata);
    if ($result) {
      Cookie::write(array('account' => $oauthdata['screen_name'], 'individual_value' => $individual_value));
    }
    return $result;
  }

  //individual_valueのexpireを定期的に更新
  public function regularUpdate() {
    if (!$_COOKIE['update'] && $_COOKIE['individual_value']) {
      setcookie('update', '1', time() + 259200); //3日に一度更新する
      setcookie('individual_value', $_COOKIE['individual_value'], time() + Config::KUMOFS_CACHE_RIMIT);
      //現在のアカウントも一応更新しておく
      setcookie('account', $_COOKIE['account'], time() + Config::KUMOFS_CACHE_RIMIT);
    }
  }

}

//Cookieを弄るためのクラス
class Cookie {

  public static function write($values) {
    //Cookieを登録
    foreach ($values as $key => $value) {
      setcookie($key, $value, time() + 2592000, '/');
    }
  }

  public static function read($keys) {
    //Cookieを取得
    if (is_array($keys)) {
      $values = array();
      foreach ($keys as $key) {
        $values[$key] = $_COOKIE[$key];
      }
    } else {
      $values = $_COOKIE[$keys];
    }
    return $values;
  }

  public static function clear($keys) {
    //Cookieを破棄
    if (is_array($keys)) {
      foreach ($keys as $key) {
        setcookie($key, '', time() - 48000, '/');
      }
    } else {
      setcookie($keys, '', time() - 48000, '/');
    }
  }

  public static function allclear() {
    //OAuthのCookieをすべて破棄
    self::clear(array_keys($_COOKIE));
  }

}

//顔文字の変換
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

//ブラウザキャッシュを無効化
header('Content-type: text/html; charset=UTF-8');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
?>
