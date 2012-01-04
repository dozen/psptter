<?php
require 'class.php';

if ($_POST['count']) {
  $count = $_POST['count'];
  setcookie('count', $count, time() + 60 * 60 * 24 * 30, '/');
} else if ($_COOKIE['count']) {
  $count = $_COOKIE['count'];
} else {
  $count = 10;
}

if (isset($_POST['footer'])) {
  if ($_POST['footer']) {
    $footer = $_POST['footer'];
    setcookie('footer', $footer, time() + 60 * 60 * 24 * 30, '/');
  } else {
    setcookie('footer', '', time() - 24800, '/');
  }
} else if ($_COOKIE['footer']) {
  $footer = $_COOKIE['footer'];
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>設定 | PSPったー</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="<?php echo Config::ROOT_ADDRESS ?>style.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <div id="header">
      <div>
        <a href="<?php echo Config::ROOT_ADDRESS ?>">ホーム</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>mentions/">返信</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>retweets_of_me/">RTされた</a>
        <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_by_me/">RTした</a> <a href="<?php echo Config::ROOT_ADDRESS ?>retweeted_to_me/">みんなのRT</a> <a href="<?php echo Config::ROOT_ADDRESS ?>favorites/">ふぁぼ</a> <a href="<?php echo Config::ROOT_ADDRESS ?>search/">検索</a> <a href="<?php echo Config::ROOT_ADDRESS ?>setting/">設定</a>
      </div>
    </div>
    <div class="normal">
      <form method="post" action="./">
        1ページに表示するツイート(最大200):<input type="text" size="3" name="count" value="<?php echo $count ?>"><br>
        フッター <input type="text" size="20" name="footer" value="<?php echo $footer ?>"><br>
        <input type="submit" value="設定終了">
      </form>
    </div>
    <div class="normal">
      <form action="<?php echo Config::ROOT_ADDRESS ?>profile_image.php" method="post" enctype="multipart/form-data">
        <input type="file" name="image">
        <input type="submit" value="アイコンを変更">
      </form>
      ※時間がかかります＞＜；
    </div>
  </body>
</html>
