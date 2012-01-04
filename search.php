<?php
require 'class.php';
$stopwatch = new Timer();

//インスタンス作成
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  header('Location: ../');
}

//検索結果の取得
if ($_GET['s']) {
  $status = $twitter->GetSearch($_GET)->results;
}
?>

<!DOCTYPE html>
<html>
  <head>
    <title>N-PSPったー</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="<?php echo Config::ROOT_ADDRESS ?>style.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <div id="header">
      <div>
        <a href="<?php echo Config::ROOT_ADDRESS ?>">ホーム</a> <a href="<?php echo Config::ROOT_ADDRESS ?>mentions/">返信</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweets_of_me/">RTされた</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_by_me/">RTした</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_to_me/">みんなのRT</a> <a href="<?php echo Config::ROOT_ADDRESS ?>favorites/">ふぁぼ</a> <a href="<?php echo Config::ROOT_ADDRESS ?>search/">検索</a> <a href="<?php echo Config::ROOT_ADDRESS ?>setting/">設定</a>
      </div>
      <form method="get" action="<?php echo Config::ROOT_ADDRESS ?>search/">
        <textarea name="s"></textarea>
        <input type="submit" class="button" value="検索">
      </form>
    </div>
    <?php foreach ($status as $line) {
      $line = Twitter::Retweet($line); ?>
      <div class="normal">
        <div class="icon">
          <img class="icon" src="<?php echo $line->profile_image_url ?>">
        </div>
        <div class="text">
          <a href="<?php echo Config::ROOT_ADDRESS . $line->from_user ?>/"><?php echo $line->from_user ?></a> <span class="small"><?php echo $line->from_user_name ?></span><br>
          <?php echo nl2br(Twitter::StatusProcessing($line->text)) ?>
        </div>
        <div class="buttonbar">
          <span class="small"><?php echo $twitter->time($line->created_at) ?></span>
        </div>
      </div>
    <?php } ?>
    <div id="footer">
      <div style="float:left">
        <?php echo Pagenation::Navi($_GET['page'], $_GET['s']) ?>
      </div>
      <div style="text-align:right">
        <?php echo $stopwatch->Show() . ' 秒' ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
      </div>
    </div>
  </body>
</html>