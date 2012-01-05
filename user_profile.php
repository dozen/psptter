<?php
require 'class.php';
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  header('Location: ../');
  end;
}
if ($_GET['screen_name']) {
  $status = $twitter->GetStatus('', $_GET);
  $twitter->UserProfile($_GET['screen_name']);
} else {
  header('Location: ../');
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
        <a href="<?php echo Config::ROOT_ADDRESS ?>">ホーム</a> <a href="<?php echo Config::ROOT_ADDRESS ?>mentions/">返信</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweets_of_me/">RTされた</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_by_me/">RTした</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_to_me/">みんなのRT</a> <a href="<?php echo Config::ROOT_ADDRESS ?>favorites/">ふぁぼ</a> <a href="<?php echo Config::ROOT_ADDRESS ?>search/">検索</a> <a href="<?php echo Config::ROOT_ADDRESS ?>setting/">設定</a>
      </div>
      <form name="post" method="post" action="<?php echo Config::ROOT_ADDRESS ?>tweet.php">
        <textarea rows="2" cols="40" name="tweet"></textarea>
        <input type="hidden" name="id">
        <input type="submit" class="button" value="ツイート"> <a href="" onclick="checkCount();return false">チェック</a>: <span id="log">0文字</span>
      </form>
      <div class="profile">
        <div class="icon"><img src="<?php echo $twitter->profile->profile_image_url ?>" class="icon"></div>
        <div class="text">
          <span style="font-size:x-large"><?php echo $twitter->profile->screen_name . ' / ' . $twitter->profile->name ?>　</span><?php echo $twitter->profile->location ?><br>
          フォロー: <a href="<?php echo Config::ROOT_ADDRESS . 'friends/' . $twitter->profile->screen_name ?>/"><?php echo $twitter->profile->friends_count ?></a>　フォロワー: <a href="<?php echo Config::ROOT_ADDRESS . 'followers/' . $twitter->profile->screen_name ?>/"><?php echo $twitter->profile->followers_count ?></a>　<?php echo $twitter->Follow($twitter->profile->id, $twitter->profile->following) ?><br>
          <?php echo Twitter::StatusProcessing($twitter->profile->description) ?>
        </div>
        <div style="clear:both"></div>
      </div>
    </div>
    <?php foreach ($status as $line) { ?>
      <div class="normal">
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
      <?php echo Pagenation::Navi($_GET['page'], "") ?><br>
      <?php echo $stopwatch->Show() . ' 秒' ?>
    </div>
  </body>
</html>