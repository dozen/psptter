<?php

require 'twitteroauth/twitteroauth.php';
require 'config.php';

class Authering {

    /**
     * アカウントの認証とか
     */
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
        $accessToken = $callack->getAccessToken($_REQUEST['oauth_verifier']);
        //認証データの登録
        $oauthData = new OAuthData();
        //Cookieの情報がある場合（既に1つ以上の認証データがある場合）アカウントを追加する関数を呼び出す。そうでない場合は新規作成
        if (Cookie::read('individual_value')) {
            $oauthData->accountput($accessToken);
        } else {
            $oauthData->registdata($accessToken);
        }
        if ($callack->http_code == 200) {
            header('location: ./');
        } else {
            return 'Authentication failed';
            end;
        }
    }

    /**
     * ログアウト。セッション、認証データ、Cookieをすべて消す。
     */
    public static function Logout() {
        session_destroy();
        $oauthData = new OAuthData();
        $oauthData->allclear();
        Cookie::allclear();
        header('Location: ./');
    }

}

class Twitter {

    public $accessToken;
    public $api;
    public $status;
    public $profile;

    public function __construct() {
        //認証データがなかったらエラーを返す
        $oauthData = new OAuthData();
        if ($_COOKIE['individual_value']) {
            $this->access_token = $oauthData->accountget();
            if (!$this->access_token) {
                //Cookie::allclear();
                throw new Exception('Please Login');
            }
            $this->config = $oauthData->configGet();
            $this->api = new TwitterOAuth(Config::CONSUMER_KEY, Config::CONSUMER_SECRET, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
            $this->m = new Memcache();
            $this->m->pconnect(Config::MEMCACHED_HOST, Config::MEMCACHED_PORT); //Memcached接続
        } else {
            throw new Exception('Please Login');
        }
    }

    /**
     * ツイートなど
     */
    public function Tweet($type, $content) {
        $this->type = $type;
        switch ($type) {
            case 'tweet':
                //in_reply_toの値がおかしかったら無視する
                if (strlen($content['id']) > 18) {
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
            httpStatus($this->api);
        } else {
            $this->api->OAuthRequest($this->api->host . $url . '.json', "POST", "");
            httpStatus($this->api);
        }
    }

    /**
     * タイムラインの取得
     */
    public function GetStatus($type = false, $option = null) {
        unset($option['tm']);
        //何ページ目を取得するのかを指定。指定がない場合1ページ目を表示
        if (!$option['page']) {
            $option['page'] = 1;
        }
        if (isset($option['page']) && !$option['page']) {
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
            
        } else if ($type == 'lists') {
            $type = 'lists/all';
        } else if ($type == 'list') {
            $type = 'lists/statuses';
        } else if ($option['screen_name']) {
            //ユーザのタイムライン
            $this->type = 'user_timeline';
            $this->page = $option['page'];
            $type = 'statuses/user_timeline';
        } else if ($type == 'trends') {
            //トレンド
            $type = 'trends/23424856';
        } else if ($type == false) {
            //ホームタイムライン
            $type = 'statuses/home_timeline';
        } else if ($type == 'dm') {
            $type = 'direct_messages';
        }
        $this->status = $this->api->get($type, $option);
        httpStatus($this->api);
        $httpStatus = httpStatus();
        if ($httpStatus->http_code == 200) {
            //タイムラインの巻き戻り防止用。1ページ目であり、ユーザのタイムライン・フレンド一覧・フォロワー一覧でない場合にだけキャッシュする。
            if ($option['page'] == 1 && $type != 'statuses/user_timeline' && $type != 'statuses/friends' && $type != 'statuses/followers' && $type != 'lists/all' && $type != 'lists/statuses') {
                $cache = $this->m->get($this->access_token['screen_name'] . ':' . $type); //キャッシュを取得
                //取得したデータよりキャッシュのほうが新しい場合はキャッシュを返す。
                if (strtotime($cache[0]->created_at) >= strtotime($this->status[0]->created_at)) {
                    return $cache;
                } else {
                    //キャッシュのほうが古い場合は取得したデータ返し、且つキャッシュする。
                    $this->m->set($this->access_token['screen_name'] . ':' . $type, $this->status, 0, Config::CACHE_LIMIT);
                    return $this->status;
                }
            } else {
                //タイムラインの巻き戻り防止が適用できないものは取得したデータをそのまま返す
                return $this->status;
            }
        } else {
            return false;
        }
    }

    /**
     * 検索結果の取得
     */
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
        $result = $this->api->OAuthRequest("https://search.twitter.com/search.json?q=$search", "GET", $option);
        httpStatus($this->api);
        return json_decode($result);
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
                $this->m->set($this->access_token['screen_name'] . ':status_id:' . $status_id, $this->response, 0, Config::CACHE_LIMIT);
            }
            $this->status[] = $this->response;
            //in_reply_toがあるかを判定
            if ($this->response->in_reply_to_status_id) {
                $status_id = $this->response->in_reply_to_status_id;
            } else {
                httpStatus($this->api);
                unset($status_id);
            }
        }
        return $this->status;
    }

    /**
     * リプライ or ツイートの分別をして、divタグのclassを返す
     */
    public function JudgeReply($text) {
        if (strpos($text, '@' . $this->access_token['screen_name']) !== false) {
            return 'reply';
        } else {
            return 'normal';
        }
    }

    /**
     * "1日前 返信先 | 非RT | RT | ☆ | 返信"←これ
     */
    public function ToolBar($screen_name, $favorited, $status_id, $text, $in_reply_to_status_id, $protected) {
        $reply = ' | <a href="" onclick="add_text(\'@' . $screen_name . ' \',\'' . $status_id . '\');return false">返信</a>';
        if ($this->config['lojax'] == 'disable') {
            $rt = '<a href="" onclick="add_text(\'' . rawurlencode(' RT @' . $screen_name . ': ' . htmlspecialchars_decode($text)) . '\');return false">非RT</a> | ';
            //LoJAXが無効の場合
            if ($screen_name == $this->access_token['screen_name']) {
                //ツイートの削除ボタン、RT、非公式RTを実装
                $destroy = ' | <a href="/send.php?destroy=' . $status_id . '">消</a>';
            } else {
                $destroy = null;
                if (!$protected) {
                    $rt .= '<a href="/send.php?retweet=' . $status_id . '">RT</a> | ';
                }
            }
            //ふぁぼ
            if ($favorited) {
                $fav = '<a href="/send.php?fav_dest=' . $status_id . '">★</a>';
            } else {
                $fav = '<a href="/send.php?fav=' . $status_id . '">☆</a>';
            }
        } else {
            //LoJAXが有効な場合
            $this->i++;
            if ($screen_name == $this->access_token['screen_name']) {
                //ツイートの削除ボタン、RT、非公式RTを実装
                $destroy = ' | <a href="" id="destroy' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', \'' . $this->i . '\', \'destroy\');return false">消</a>';
                $rt = '<a href="" onclick="add_text(\'' . rawurlencode(' RT @' . $screen_name . ': ' . htmlspecialchars_decode($text)) . '\');return false">非RT</a> | ';
            } else {
                $destroy = null;
                $rt = '<a href="" onclick="add_text(\'' . rawurlencode(' RT @' . $screen_name . ': ' . htmlspecialchars_decode($text)) . '\');return false">非RT</a> | ';
                if (!$protected) {
                    $rt .= '<a href="" id="retweet' . $this->i . '" onclick="makeRequest(\'' . $status_id . '\', \'' . $this->i . '\', \'retweet\');return false">RT</a> | ';
                }
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
            $mention = '<a href="/talk/' . $status_id . '">返信先</a> | ';
        } else {
            $mention = null;
        }
        return $mention . $rt . $fav . $destroy . $reply;
    }

    /**
     * 誰がリツイートしたかを表示する
     */
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

    /**
     * URL, ユーザ, ハッシュタグにリンクを貼る
     */
    public static function StatusProcessing($status) {
        $status = preg_replace("/htt[ps]{1,}:\/\/t\.co\/[a-zA-Z0-9]{1,}/u", "<a target=\"_blank\" href=\"$0\">$0</a>", $status);
        $status = preg_replace("/[#＃]([a-zA-Z0-9\-_一-龠ぁ-ゞゔゕゖア-ンーヽヾヴｦ-ﾟ々]{1,})/u", "<a href='/search/?s=%23$1'>#$1</a>", $status);
        $status = preg_replace("/@([a-zA-Z0-9-_]{1,})/u", "<a href='/$1/'>@$1</a>", $status);
        return nl2br($status);
    }

    /**
     * 取得したトレンドを検索できるようにリンクを貼る
     */
    public static function TrendsProcessing($status) {
        return '<a href="/search/?s=' . rawurlencode($status) . '">' . $status . '</a><br>';
    }

    /**
     * 投稿時刻の加工
     */
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

    /**
     * 何人がRTしたかを表示するだけ
     */
    public static function RetweetStatus($retweet_count, $retweeted_user) {
        if ($retweet_count) {
            $retweetstatus = $retweet_count . '人がリツイート　';
        }
        if ($retweeted_user) {
            $retweetstatus = '<a href="/' . $retweeted_user . '/">' . $retweeted_user . 'がリツイート</a>　' . $retweetstatus;
        }
        return $retweetstatus;
    }

    /**
     * ユーザのプロフィールを表示する
     */
    public function UserProfile($screen_name) {
        if ($this->type == 'user_timeline' && $this->page == 1) {
            $this->profile = $this->status[0]->user;
            $this->m->set($this->access_token['screen_name'] . ':profile:' . $this->profile->screen_name, $this->profile, 0, Config::CACHE_LIMIT);
        } else {
            $this->profile = $this->m->get($this->access_token['screen_name'] . ':profile:' . $screen_name);
        }
    }

    /**
     * フォロー・リムーブのリンクを作成
     */
    public function Follow($user_id, $following) {
        if ($this->config['lojax'] == 'disable') {
            //LoJAXが無効な場合
            if ($following) {
                $results = '<a href="/send.php?remove=' . $user_id . '">リムーブ</a>';
            } else {
                $results = '<a href="/send.php?follow=' . $user_id . '">フォロー</a>';
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

function httpStatus($status = false) {
    static $cache;
    if (!isset($cache) && $status) {
        $cache->http_code = $status->http_code;
        $cache->rateLimit = (object) array('limit' => $status->http_header['x_ratelimit_limit'], 'remaining' => $status->http_header['x_ratelimit_remaining'], 'reset' => $status->http_header['x_ratelimit_reset']);
    } else if (!isset($cache)) {
        return false;
    }
    return $cache;
}

/**
 * パフォーマンス計測用
 */
class Timer {

    private $time;

    public function __construct() {
        $this->time = microtime(true);
    }

    public function Show() {
        return round(microtime(true) - $this->time, 3) . '秒';
    }

}

/**
 * ページにまつわる細々したもの。
 */
class Page {

    public function __construct() {
        $data = new OAuthData();
        //フッターやアイコンのサイズなどの設定を読み込む
        $this->config = $data->configGet();
    }

    /**
     * アイコンのサイズ設定に従ってツイートの内容部分のスタイルを変更する
     */
    public function textStyle() {
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

    /**
     * アイコンのサイズの設定に従ってアイコンのスタイルを変更する
     */
    public function IconStyle($url, $protected) {
        if ($this->config['icon'] == 'disable') {
            if ($protected) {
                $protected = '<div class="icon"><img src="/smallprotected.png"></div>';
            }
            return $protected;
        } else if ($this->config['icon'] == 'middle') {
            if ($protected) {
                $protected = '<img class="protected" src="/smallprotected.png">';
            }
            $class = 'iconmiddle';
        } else if ($this->config['icon'] == 'small') {
            if ($protected) {
                $protected = '<img class="protected" src="/smallprotected.png">';
            }
            $class = 'iconsmall';
        } else {
            if ($protected) {
                $protected = '<img class="protected" src="/protected.png">';
            }
            $class = 'icon';
        }
        return '<div class="icon">' . $protected . '<img src="' . $url . '" class="' . $class . '"></div>';
    }

    /**
     * ヘッダ
     */
    public function Header() {
        $results = '<title>PSPったー - psptter</title>' .
                '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' .
                '<link href="/style.css" rel="stylesheet" type="text/css">' .
                '<script src="/js.js" type="text/javascript"></script>';
        //3DS用の設定。
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Nintendo 3DS') !== false) {
            $results .= '<meta name="viewport" content="width=320">';
        }
        //LoJAXを有効にしている場合、lojax.jsを読み込む
        if ($this->config['lojax'] == "enable") {
            $results = $results .
                    '<script src="/lojax-settings.js" type="text/javascript"></script>' .
                    '<script src="/lojax.js" type="text/javascript"></script>';
        }
        return $results;
    }

    /**
     * メニューバー
     */
    public static function MenuBar() {
        return '<div id="menu">
  <a href="/">ホーム</a>
  <a href="/mentions/">返信</a>
  <a href="/retweets_of_me/">RTされた</a>
  <a href="/' . OAuthData::$account['screen_name'] . '/">自分</a>
  <a href="/retweeted_to_me/">みんなのRT</a>
  <a href="/favorites/">ふぁぼ</a>
  <a href="/search/">検索</a>
  <a href="/lists/' . OAuthData::$account['screen_name'] . '/">リスト</a>
  <a href="/trends/">トレンド</a>
  <a href="/setting/">設定</a>
  </div>';
    }

    /**
     * ページネーション
     */
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

    /**
     * フォロー・フォロワーのページネーション。特殊なので別実装
     */
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

    public static function showStatus() {
        $status = httpStatus();
        if ($status->rateLimit) {
            echo 'API:' . $status->rateLimit->remaining . '/' . $status->rateLimit->limit;
        }
    }

    public static function tweetByLojax() {
        if (Oauthdata::$config['lojax'] === 'enable') {
            return ' onclick="tweetStatus();return false"';
        } else {
            return '';
        }
    }

}

//各ページでいちいちインスタンス作成するコードを書くのが面倒なのでここで作成する
$page = new Page();

/**
 * kumofs
 */
class Data {

    public function __construct() {
        $this->kumo = new Memcache();
        $this->kumo->pconnect(Config::KUMOFS_HOST, Config::KUMOFS_PORT);
    }

    /**
     * データの書き込み
     */
    public function write($key, $value) {
        return $this->kumo->set($key, serialize($value), false, Config::KUMOFS_CACHE_LIMIT);
    }

    /**
     * データの読み込み
     */
    public function read($keys) {
        return unserialize($this->kumo->get($keys));
    }

    /**
     * データの削除
     */
    public function delete($keys) {
        $this->kumo->delete($keys);
        unset($this->cache);
    }

}

//認証データと設定データを弄るためのクラス
class OAuthData {

    static public $account, $config;

    public function __construct() {
        $this->data = new Data();
        $this->regularUpdate();
    }

    /**
     * アカウントの情報を取得する
     */
    public function accountget() {
        $individualValue = md5($_COOKIE['individual_value']);
        $oauthData = $this->data->read($individualValue);
        $account = $oauthData['config']['current_account'];
        self::$account = $oauthData['account'][$account];
        self::$config = $oauthData['config'];
        return $oauthData['account'][$account];
    }

    /**
     * 設定の読み込み
     */
    public function configGet() {
        $individualValue = md5($_COOKIE['individual_value']);
        $oauthData = $this->data->read($individualValue);
        return $oauthData['config'];
    }

    /**
     * アカウント一覧
     */
    public function accountlist() {
        $individualValue = md5($_COOKIE['individual_value']);
        $accountlist = $this->data->read($individualValue);
        $this->dealDustData($individualValue, $accountlist);
        return array_keys($accountlist['account']);
    }

    /**
     * 設定の書き込み
     */
    public function configput($key, $value) {
        $individualValue = md5($_COOKIE['individual_value']);
        $data = $this->data->read($individualValue);
        $data['config'][$key] = $value;
        $result = $this->data->write($individualValue, $data);
        return $result;
    }

    public function dealDustData() {
        $individualValue = md5(Cookie::read('individual_value'));
        $data = $this->data->read($individualValue);
        if (isset($data['account'][''])) {
            unset($data['account']['']);
            $this->data->write($individualValue, $data);
        }
    }

    /**
     * アカウント追加
     */
    public function accountput($accsessToken) {
        $account = $accsessToken['screen_name'];
        $individualValue = md5(Cookie::read('individual_value'));
        $data = $this->data->read($individualValue);
        if ($data) {
            $data['account'][$account] = $accsessToken;
            $this->configput('current_account', $account);
        } else {
            return $this->registdata($accsessToken);
        }
        return $this->data->write($individualValue, $data);
    }

    /**
     * アカウント情報の削除
     */
    public function accountclear($account) {
        $individualValue = md5(Cookie::read('individual_value'));
        $oauthData = $this->data->read($individualValue);
        if (count($oauthData['account']) > 1) {
            unset($oauthData['account'][$account]);
            $this->data->write($individualValue, $oauthData);
        }
    }

    public function allclear() {
        $this->data->delete(md5(Cookie::read('individual_value')));
    }

    /**
     * 初回認証時にデータを登録
     */
    public function registdata($oauthData) {
        //kumofsに登録するデータの内容
        $registdata = array(
            'config' => array(
                'count' => 10,
                'footer' => '',
                'icon' => 'normal',
                'lojax' => 'disable',
                'current_account' => $oauthData['screen_name']
            ),
            'account' => array(
                $oauthData['screen_name'] => $oauthData
            ),
        );
        //individual_value=個体識別番号。この値をCookieに保存し、これをもとにkumofsからデータを読み込む。
        $individualValue = md5(microtime(true) . mt_rand() . Config::SALT);
        $result = $this->data->write(md5($individualValue), $registdata);
        if ($result) {
            Cookie::write(array('individual_value' => $individualValue));
        }
        return $result;
    }

    /**
     * Cookieとkumofs上のデータの寿命を定期的に更新
     */
    public function regularUpdate() {
        if (!$_COOKIE['update'] && $_COOKIE['individual_value']) {
            setcookie('update', '1', time() + 259200); //3日に一度更新する
            setcookie('individual_value', $_COOKIE['individual_value'], time() + Config::KUMOFS_CACHE_LIMIT);
            $individualValue = md5(Cookie::read('individual_value'));
            $oauthData = $this->data->read($individualValue);
            $this->data->write($individualValue, $oauthData);
        }
    }

}

/**
 * Cookieを弄るためのクラス（不要？）
 */
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

/**
 * 顔文字の変換
 */
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
            '。ぴき。', '、ぴき、', '。ぴきぴき。', '、ぴきぴき、', '。もうしわけねえ。', '、もうしわけねえ、',
            '。どこ。', '、どこ、', '。なう。', '、なう、', '。ほも。', '、ほも、'
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
            '(＃＾ω＾)ﾋﾟｷﾋﾟｷ', '(＃＾ω＾)', '(＃＾ω＾)ﾋﾟｷﾋﾟｷ', '(＃＾ω＾)', 'ヽ(\'ω\')ﾉ三ヽ(\'ω\')ﾉもうしわけねぇもうしわけねぇ ', 'ヽ(\'ω\')ﾉ三ヽ(\'ω\')ﾉ',
            '┗┏┗┏(\'o\')┓┛┓┛', '┗┏┗┏(\'o\')┓┛┓┛', '(´へεへ`*)', '(´へεへ`*)', '┌（┌＾o＾）┐ﾎﾓｫ…', '┌（┌＾o＾）┐'
        )
    );
    return str_replace($aa['str'], $aa['aa'], $object);
}

//ブラウザキャッシュを無効化
header('Content-type: text/html; charset=UTF-8');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
