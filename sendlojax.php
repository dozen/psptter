<?php

require 'class.php';
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  Authering::Redirect();
}
//投稿系の処理。
if (isset($_GET['retweet'])) {
  $twitter->Tweet('retweet', array('id' => $_GET['retweet']));
} else if (isset($_GET['fav'])) {
  $twitter->Tweet('fav', array('id' => $_GET['fav']));
} else if (isset($_GET['fav_dest'])) {
  $twitter->Tweet('fav_dest', array('id' => $_GET['fav_dest']));
} else if (isset($_GET['destroy'])) {
  $twitter->Tweet('destroy', array('id' => $_GET['destroy']));
} else if ($_GET['tm'] == 'follow') {
  $twitter->Tweet('follow', array('user_id' => $_GET['user_id']));
} else if ($_GET['tm'] == 'remove') {
  $twitter->Tweet('remove', array('user_id' => $_GET['user_id']));
}
$status = $twitter->Status();
if ($status->http_code != 200) {
  echo 'error:' . $status->http_code;
} else {
  echo 'OK';
}
?>