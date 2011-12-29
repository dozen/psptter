<?php
require 'class.php';
if ($_POST['count']) {
  setcookie('count', $_POST['count'], time() + 60 * 60 * 24 * 30, '/');
}
if (!$_COOKIE['count']) {
  $count = 10;
} else {
  if ($_POST['count']) {
    $count = $_POST['count'];
  } else {
    $count = $_COOKIE['count'];
  }
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
        <input type="submit" value="設定終了">
      </form>
    </div>
  </body>
</html>