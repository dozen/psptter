<?php

require 'class.php';
try {
  $twitter = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
  Authering::Redirect();
}
//投稿系の処理。
if (isset($_POST['tweet'])) {
  if ($_COOKIE['footer'] && !$_POST['id']) {
    $tweet = $_POST['tweet'] . ' ' . $_COOKIE['footer'];
  } else {
    $tweet = $_POST['tweet'];
  }
  $tweet = aa($tweet);
  $twitter->Tweet('tweet', array('tweet' => $tweet, 'id' => $_POST['id']));
} else if (isset($_GET['retweet'])) {
  $twitter->Tweet('retweet', array('id' => $_GET['retweet']));
} else if (isset($_GET['fav'])) {
  $twitter->Tweet('fav', array('id' => $_GET['fav']));
} else if (isset($_GET['fav_dest'])) {
  $twitter->Tweet('fav_dest', array('id' => $_GET['fav_dest']));
} else if (isset($_GET['destroy'])) {
  $twitter->Tweet('destroy', array('id' => $_GET['destroy']));
}
header('Location: ./');
?>
