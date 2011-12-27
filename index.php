<?php
require 'class.php';
$stopwatch = new Timer();
//認証
if (isset($_GET['redirect'])) {
  Authering::Redirect();
} else if (isset($_REQUEST['oauth_token'])) {
  Authering::Callback();
}

//テスト用。
if (isset($_GET['clear'])) {
  session_destroy();
}

//これもテスト用
if (isset($_GET['count'])) {
  setcookie('count', $_GET['count'], time() + 60 * 60 * 24 * 30, '/');
  unset($_GET['count']);
}

//インスタンス作成
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  include 'entrance.html';
  end;
}

//TLの取得
if ($_GET['status_id']) {
  $status = $twitter->GetTalk($_GET['status_id']);
} else {
  $status = $twitter->GetStatus($_GET['tm'], $_GET);
}

if (isset($_GET['debug'])) {
  header('content-type:text/plain');
  print_r($_GET);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>N-PSPったー</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="<?php echo Config::ROOT_ADDRESS ?>style.css" rel="stylesheet" type="text/css">
    <script src="<?php echo Config::ROOT_ADDRESS ?>js.js" type="text/javascript"></script>
  </head>
  <body>
    <div id="header">
      <div>
        <a href="<?php echo Config::ROOT_ADDRESS ?>">ホーム</a> <a href="<?php echo Config::ROOT_ADDRESS ?>mentions/">返信</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweets_of_me/">RTされた</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_by_me/">RTした</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_to_me/">みんなのRT</a> <a href="<?php echo Config::ROOT_ADDRESS ?>favorites/">ふぁぼ</a> <a href="<?php echo Config::ROOT_ADDRESS ?>search/">検索</a>
      </div>
      <form name="post" method="post" action="<?php echo Config::ROOT_ADDRESS ?>tweet.php">
        <textarea rows="2" cols="40" name="tweet"></textarea>
        <input type="hidden" name="id">
        <input type="submit" value="ツイート"> <span id="log">0文字</span>
      </form>
    </div>
    <?php foreach ($status as $line) {
      $line = Twitter::Retweet($line); ?>
      <div class="<?php echo $twitter->JudgeReply($line->text) ?>">
        <div class="profile">
          <img class="profile" src="<?php echo $line->user->profile_image_url ?>">
        </div>
        <div class="text">
          <a href="<?php echo Config::ROOT_ADDRESS . $line->user->screen_name ?>/"><?php echo $line->user->screen_name ?></a> <span class="small"><?php echo $line->user->name ?>　<?php echo $line->source ?>から</span><br>
  <?php echo nl2br(Twitter::StatusProcessing($line->text)) ?>
        </div>
        <div class="buttonbar">
          <span class="small"><?php echo Twitter::RetweetStatus($line->retweet_count, $line->retweeted_user) ?><?php echo $twitter->time($line->created_at) ?></span>
  <?php echo $twitter->ToolBar($line->user->screen_name, $line->favorited, $line->id, $line->text, $line->id, $line->in_reply_to_status_id) ?>
        </div>
      </div>
      <?php } ?>
    <div id="footer">
      <?php echo Pagenation::Navi($_GET['page'], "") ?><br>
<?php echo $stopwatch->Show() . ' 秒' ?>
    </div>
  </body>
</html>