<?php
require 'class.php';
$stopwatch = new Timer();

//認証
if (isset($_GET['redirect'])) {
    Authering::Redirect();
} else if (isset($_REQUEST['oauth_token'])) {
    Authering::Callback();
}

//ログアウト
if (isset($_GET['logout'])) {
    Authering::Logout();
}

//インスタンス作成
try {
    $twitter = new Twitter();
} catch (Exception $e) {
//ログインしてなかったときの処理
    include 'entrance.html';
    exit();
}

//TLの取得
$status = $twitter->GetStatus('lists', $_GET);
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
        <?php foreach ($status as $line) { ?>
            <div class="normal">
                <?php echo $page->IconStyle($line->user->profile_image_url, $line->user->protected) ?>
                <div class="text">
                    <a href="<?php echo Config::ROOT_ADDRESS . 'list/' . $line->id ?>/"><?php echo $line->name ?></a> <span class="small"><a href="<?php echo Config::ROOT_ADDRESS . $line->user->screen_name ?>/"><?php echo $line->user->screen_name ?></a>が作成</span><br>
                    <?php echo $line->description ?>
                </div>
                <div class="buttonbar">
                    <span class="small"><?php echo $line->member_count ?>人が追加されています <?php echo $line->subscriber_count ?>人が保存</span>
                </div>
            </div>
        <?php } ?>

        <div id="footer">
            <?php adsense() ?>
            <div style="float:left">
                <?php echo Page::Navi($_GET['page'], "") ?>
            </div>
            <div class="go_top">
                <a href="#menu">トップへ戻る</a>
            </div>
            <div style="text-align:right">
                <a href="/help.html">HELP</a>
                <a href="/kumobbs/" target="blank">掲示板</a>
                <?php echo page::showStatus(); ?> <?php echo $stopwatch->Show() ?> <a href="<?php echo Config::ROOT_ADDRESS ?>?logout">logout</a>
            </div>
        </div>
        <?php
        $googleAnalyticsImageUrl = googleAnalyticsGetImageUrl();
        echo '<img src="' . $googleAnalyticsImageUrl . '" />';
        ?>
    </body>
</html>