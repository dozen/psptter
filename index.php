<?php
require 'class.php';
$stopwatch = new Timer();

if (isset($_GET['message'])) {
  echo $_GET['message'];
}
//認証
if (isset($_GET['redirect'])) {
  Authering::Redirect();
} else if (isset($_REQUEST['oauth_token'])) {
  Authering::Callback();
}

//テスト用。
if (isset($_GET['logout'])) {
  Authering::Logout();
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
  print_r($status);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>N-PSPったー</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="<?php echo Config::ROOT_ADDRESS ?>style.css" rel="stylesheet" type="text/css">
    <script src="<?php echo Config::ROOT_ADDRESS ?>js.js" type="text/javascript"></script>
    <script src="<?php echo Config::ROOT_ADDRESS ?>lojax.js" type="text/javascript"></script>
  </head>
  <body>
    <div id="header">
      <div>
        <a href="<?php echo Config::ROOT_ADDRESS ?>">ホーム</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>mentions/">返信</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>retweets_of_me/">RTされた</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_by_me/">RTした</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_to_me/">みんなのRT</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>favorites/">ふぁぼ</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>search/">検索</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>setting/">設定</a>
      </div>
      <form name="post" method="post" action="<?php echo Config::ROOT_ADDRESS ?>tweet.php">
        <input type="hidden" name="id">
        <textarea name="tweet" onChange="strCount()"></textarea>
        <input type="submit" class="button"  value="ツイート"> <span id="strcount">　</span>
      </form>
    </div>
    <?php foreach ($status as $line) {
      $line = Twitter::Retweet($line); ?>
      <div class="<?php echo $twitter->JudgeReply($line->text) ?>">
        <div class="icon">
          <img class="icon" src="<?php echo $line->user->profile_image_url ?>">
        </div>
        <div class="text">
          <a href="<?php echo Config::ROOT_ADDRESS . $line->user->screen_name ?>/"><?php echo $line->user->screen_name ?></a> <span class="small"><?php echo $line->user->name ?>　<?php echo $line->source ?>から</span><br>
          <?php echo Twitter::StatusProcessing($line->text) ?>
        </div>
        <div class="buttonbar">
          <span class="small"><?php echo Twitter::RetweetStatus($line->retweet_count, $line->retweeted_user) ?><?php echo $twitter->time($line->created_at) ?></span>
          <?php echo $twitter->ToolBar($line->user->screen_name, $line->favorited, $line->id, $line->text, $line->id, $line->in_reply_to_status_id) ?>
        </div>
      </div>
    <?php } ?>
    <div id="footer">
      <div style="float:left">
        <?php echo Pagenation::Navi($_GET['page'], "") ?>
      </div>
      <div style="text-align:right">
        <?php echo $stopwatch->Show() . ' 秒' ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
      </div>
    </div>
  </body>
</html>
