<?php

require 'class.php';
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  Authering::Redirect();
}
//投稿系の処理。
if (isset($_POST['retweet'])) {
  $twitter->Tweet('retweet', array('id' => $_POST['retweet']));
} else if (isset($_POST['fav'])) {
  $twitter->Tweet('fav', array('id' => $_POST['fav']));
} else if (isset($_POST['fav_dest'])) {
  $twitter->Tweet('fav_dest', array('id' => $_POST['fav_dest']));
} else if (isset($_POST['destroy'])) {
  $twitter->Tweet('destroy', array('id' => $_POST['destroy']));
} else if (isset($_POST['follow'])) {
  $twitter->Tweet('follow', array('user_id' => $_POST['follow']));
} else if (isset($_POST['remove'])) {
  $twitter->Tweet('remove', array('user_id' => $_POST['remove']));
} else {
  echo 'error :request empty';
  end;
}
$status = $twitter->api;
if ($status->http_code == 200) {
  echo 'OK';
} else {
  echo 'error:' . $status->http_code;
}
?>
