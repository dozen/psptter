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
            <?php
            foreach ($status[0]->trends as $line) {
                echo Twitter::TrendsProcessing($line->name);
            }
            ?>
        </div>
        <div class="go_top">
            <a href="#menu">トップへ戻る</a>
        </div>
        <div id="footer">
<<<<<<< HEAD
            <?php adsense() ?>
=======
>>>>>>> 9c9b5149c614186606761a0b01b000e98aca93aa
            <div style="text-align:right">
                <a href="/help.html">HELP</a>
                <a href="/kumobbs/" target="blank">掲示板</a>
                <?php echo $stopwatch->Show() ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
            </div>
        </div>
    </body>
</html>