<?php
require 'class.php';

if (is_numeric($_POST['count'])) {
  $count = $_POST['count'];
  setcookie('count', $count, time() + 60 * 60 * 24 * 30, '/');
} else if ($_COOKIE['count']) {
  $count = $_COOKIE['count'];
} else {
  $count = 10;
}

if (isset($_POST['footer'])) {
  $footer = $_POST['footer'];
  if ($footer) {
    setcookie('footer', $footer, time() + 60 * 60 * 24 * 30, '/');
  } else {
    setcookie('footer', '', time() - 24800, '/');
  }
} else if ($_COOKIE['footer']) {
  $footer = $_COOKIE['footer'];
}

if (isset($_POST['lojax'])) {
  $lojax = $_POST['lojax'];
  if ($lojax == "disable" || $lojax == "enable") {
    setcookie('lojax', $lojax, time() + 60 * 60 * 24 * 30, '/');
  } else {
    $lojax = 'enable';
  }
} else if ($_COOKIE['lojax']) {
  $lojax = $_COOKIE['lojax'];
}

$lojax_radio[$lojax] = "checked";

if (isset($_POST['icon'])) {
  $icon = $_POST['icon'];
  if ($icon == "disable" || $icon == "middle" || $icon == 'small' || $icon == 'normal') {
    setcookie('icon', $icon, time() + 60 * 60 * 24 * 30, '/');
  } else {
    $icon = 'normal';
  }
} else if ($_COOKIE['icon']) {
  $icon = $_COOKIE['icon'];
}

$icon_radio[$icon] = 'checked';
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
      <?php echo Page::MenuBar() ?>
    </div>
    <div class="normal">
      <form method="post" action="./">
        <p>
          1ページに表示するツイート(最大200)
          <input type="text" size="3" name="count" value="<?php echo $count ?>">
        </p>
        <p>
          フッター
          <input type="text" size="20" name="footer" value="<?php echo htmlspecialchars($footer) ?>">
        </p>
        <p>
          アイコン
          <input type="radio" name="icon" value="normal" <?php echo $icon_radio['normal'] ?>> 普通(48px)
          <input type="radio" name="icon" value="middle" <?php echo $icon_radio['middle'] ?>> 中(32px)
          <input type="radio" name="icon" value="small" <?php echo $icon_radio['small'] ?>> 小(16px)
          <input type="radio" name="icon" value="disable" <?php echo $icon_radio['disable'] ?>> なし
        </p>
        <p>
          Lojax
          <input type="radio" name="lojax" value="enable" <?php echo $lojax_radio['enable'] ?>> 有効
          <input type="radio" name="lojax" value="disable" <?php echo $lojax_radio['disable'] ?>> 無効
        </p>
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
