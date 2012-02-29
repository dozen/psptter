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

//トレンドの取得
$status = $twitter->GetStatus('trends');
?>

<!DOCTYPE html>
<html>
  <head>
    <?php echo $page->Header() ?>
  </head>
  <body>
    <div id="header">
      <?php echo Page::MenuBar() ?>
    </div>
    <div class="normal" style="line-height:1.5em">
      <?php foreach ($status[0]->trends as $line) { echo Twitter::TrendsProcessing($line->name); } ?>
    </div>
    <div id="footer">
      <div style="text-align:right">
        <?php echo $stopwatch->Show() ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
      </div>
    </div>
  </body>
</html>