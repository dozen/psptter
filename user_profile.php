<?php
require 'class.php';
$stopwatch = new Timer();
try {
    $twitter = new Twitter();
} catch (Exception $e) {
    //ログインしてなかったときの処理
    header('Location: ../');
    end;
}
if ($_GET['screen_name']) {
    $status = $twitter->GetStatus('', $_GET);
    $twitter->UserProfile($_GET['screen_name']);
    if (isset($_GET['debug'])) {
        echo '<pre>';
        print_r($twitter);
        echo '</pre>';
    }
} else {
    header('Location: ../');
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
            <form name="post" method="post" action="<?php echo Config::ROOT_ADDRESS ?>send.php">
                <input type="hidden" name="id">
                <textarea name="tweet" onkeydown="strCount()" onChange="strCount()"></textarea>
                <input type="submit" class="button"  value="ツイート"> <span id="strcount">　</span>
            </form>
            <div class="profile">
                <div class="icon"><img src="<?php echo $twitter->profile->profile_image_url ?>" class="icon"></div>
                <div class="text">
                    <span style="font-size:x-large"><?php echo $twitter->profile->screen_name . ' / ' . $twitter->profile->name ?>　</span><?php echo $twitter->profile->location ?><br>
                    ツイート: <?php echo $twitter->profile->statuses_count ?>　フォロー: <a href="<?php echo Config::ROOT_ADDRESS . 'friends/' . $twitter->profile->screen_name ?>/"><?php echo $twitter->profile->friends_count ?></a>　フォロワー: <a href="<?php echo Config::ROOT_ADDRESS . 'followers/' . $twitter->profile->screen_name ?>/"><?php echo $twitter->profile->followers_count ?></a>　<?php echo $twitter->Follow($twitter->profile->id, $twitter->profile->following) ?>　<a href="/lists/<?php echo $twitter->profile->screen_name ?>">リスト</a><br>
                    <?php echo Twitter::StatusProcessing($twitter->profile->description) ?>
                </div>
                <div style="clear:both"></div>
            </div>
        </div>
        <?php foreach ($status as $line) { ?>
            <div class="normal">
                <?php echo $page->IconStyle($line->user->profile_image_url, $line->user->protected) ?>
                <div class="<?php echo $page->textStyle() ?>">
                    <a href="<?php echo Config::ROOT_ADDRESS . $line->user->screen_name ?>/"><?php echo $line->user->screen_name ?></a> <span class="small"><?php echo $line->user->name ?>　<?php echo $line->source ?>から</span><br>
                    <?php echo Twitter::StatusProcessing($line->text) ?>
                </div>
                <div class="buttonbar">
                    <span class="small"><?php echo Twitter::RetweetStatus($line->retweet_count, $line->retweeted_user) ?><?php echo $twitter->time($line->created_at) ?></span>
                    <?php echo $twitter->ToolBar($line->user->screen_name, $line->favorited, $line->id, $line->text, $line->in_reply_to_status_id, $line->user->protected) ?>
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
