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
if ($_GET['status_id']) {
    $status = $twitter->GetTalk($_GET['status_id']);
} else {
    $status = $twitter->GetStatus($_GET['tm'], $_GET);
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
            <form name="post" method="post" action="/send.php">
                <input type="hidden" name="id">
                <textarea name="tweet" onkeydown="strCount()" onkeydown="strCount()" onkeyup="strCount()" onkeypress="strCount()" onChange="strCount()"></textarea>
                <input type="submit" class="button"<?php echo Page::tweetByLojax() ?> value="ツイート"> <span id="strcount">　</span>
            </form>
        </div>
        <?php
        foreach ($status as $line) {
            $line = Twitter::Retweet($line);
            ?>
            <div class="<?php echo $twitter->JudgeReply($line->text) ?>">
                <?php echo $page->IconStyle($line->user->profile_image_url, $line->user->protected) ?>
                <div class="<?php echo $page->textStyle() ?>">
                    <a href="/<?php echo $line->user->screen_name ?>/"><?php echo $line->user->screen_name ?></a> <span class="small"><?php echo $line->user->name ?>　<?php echo $line->source ?>から</span><br>
                    <?php echo Twitter::StatusProcessing($line->text) ?>
                </div>
                <div class="buttonbar">
                    <span class="small"><?php echo Twitter::RetweetStatus($line->retweet_count, $line->retweeted_user) ?><?php echo $twitter->time($line->created_at) ?></span>
                    <?php echo $twitter->ToolBar($line->user->screen_name, $line->favorited, $line->id, $line->text, $line->in_reply_to_status_id, $line->user->protected) ?>
                </div>
            </div>
        <?php } ?>
        <div id="footer">
            <div style="float:left">
                <?php echo Page::Navi($_GET['page'], "") ?>
            </div>
            <div class="go_top">
                <a href="#menu">トップへ戻る</a>
            </div>
            <div style="text-align:right">
                <a href="/help.html">HELP</a>
                <a href="/kumobbs/" target="blank">掲示板</a>
                <?php echo page::showStatus(); ?> <?php echo $stopwatch->Show() ?> <a href="/?logout">logout</a>
            </div>
        </div>
    </body>
</html>
