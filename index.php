<?php

$stopwatch = microtime(true);
require 'class.php';
if (isset($_GET['clear'])) {
  session_destroy();
}
//認証
if (isset($_GET['redirect'])) {
  Authering::Redirect();
} else if (isset($_GET['callback'])) {
  Authering::Callback();
}
if (!isset($_GET['d'])) {
  header('content-type:application/json');
}
try {
  $tl = new Twitter();
} catch (Exception $e) {
  //ログインしてなかったときの処理
}

if ($_GET['tm'] == 'search' && isset($_GET['q'])) {
  $timeline = $tl->Get_Status($_GET['tm'], array('q' => $_GET['q'], 'page' => $options['page']));
} else {
  $timeline = $tl->Get_Status($_GET['tm'], array());
}
  print_r(json_encode($timeline));
?>
