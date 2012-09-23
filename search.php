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

//検索結果の取得
if ($_GET['s']) {
    $status = $twitter->GetSearch($_GET)->results;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <?php echo $page->Header() ?>
    </head>
    <body>
        <div id="header">
            <?php echo Page::MenuBar() ?>
            <form method="get" action="<?php echo Config::ROOT_ADDRESS ?>search/">
                <textarea name="s"></textarea>
                <input type="submit" class="button" value="検索">
            </form>
        </div>
        <?php
        foreach ($status as $line) {
            $line = Twitter::Retweet($line);
            ?>
            <div class="normal">
                <?php echo $page->IconStyle($line->profile_image_url, $line->protected) ?>
                <div class="<?php echo $page->textStyle() ?>">
                    <a href="<?php echo Config::ROOT_ADDRESS . $line->from_user ?>/"><?php echo $line->from_user ?></a> <span class="small"><?php echo $line->from_user_name ?></span><br>
                    <?php echo nl2br(Twitter::StatusProcessing($line->text)) ?>
                </div>
                <div class="buttonbar">
                    <span class="small"><?php echo $twitter->time($line->created_at) ?></span>
                </div>
            </div>
        <?php } ?>
        <div id="footer">
            <div style="float:left">
                <?php echo Page::Navi($_GET['page'], $_GET['s']) ?>
            </div>
            <div style="text-align:right">
                <?php echo $stopwatch->Show() ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
            </div>
        </div>
    </body>
</html>