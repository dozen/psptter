<?php
require 'class.php';

if (is_numeric($_POST['count'])) {
  $count = $_POST['count'];
  Cookie::set(array('count' => $count));
} else if (Cookie::get('count')) {
  $count = Cookie::get('count');
} else {
  $count = 10;
}

if (isset($_POST['footer'])) {
  $footer = $_POST['footer'];
  if ($footer) {
    Cookie::set(array('footer' => $footer));
  } else {
    Cookie::clear('footer');
  }
} else if (Cookie::get('footer')) {
  $footer = Cookie::get('footer');
}

if (isset($_POST['lojax'])) {
  $lojax = $_POST['lojax'];
  if ($lojax == "disable" || $lojax == "enable") {
    Cookie::set(array('lojax' => $lojax));
  } else {
    $lojax = 'enable';
  }
} else if (Cookie::get('lojax')) {
  $lojax = Cookie::get('lojax');
} else {
  Cookie::set(array('lojax' => 'enable'));
  $lojax = 'enable';
}

$lojax_radio[$lojax] = "checked";

if (isset($_POST['icon'])) {
  $icon = $_POST['icon'];
  if ($icon == "disable" || $icon == "middle" || $icon == 'small' || $icon == 'normal') {
    Cookie::set(array('icon' => $icon));
  } else {
    $icon = 'normal';
  }
} else if (Cookie::get('icon')) {
  $icon = Cookie::get('icon');
} else {
  Cookie::set(array('icon' => 'normal'));
  $icon = 'normal';
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
