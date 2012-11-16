<?php
require 'class.php';
$data = new OAuthData();

//現在のアカウントを確認するため。
$data->accountget();

$config = $data->configGet();
$accountlist = $data->accountlist();
if (is_numeric($_POST['count'])) {
    $count = $_POST['count'];
    $data->configput('count', $count);
} else {
    $count = $config['count'];
}

if (isset($_POST['footer'])) {
    $footer = $_POST['footer'];
    if ($footer) {
        $data->configput('footer', $footer);
    } else {
        $data->configput('footer', '');
    }
} else if ($config['footer']) {
    $footer = $config['footer'];
}

if (isset($_POST['lojax'])) {
    $lojax = $_POST['lojax'];
    if ($lojax == "disable" || $lojax == "enable") {
        $data->configput('lojax', $lojax);
    } else {
        $lojax = 'disable';
    }
} else if ($config['lojax']) {
    $lojax = $config['lojax'];
}

$lojax_radio[$lojax] = "checked";

if (isset($_POST['icon'])) {
    $icon = $_POST['icon'];
    if ($icon == "disable" || $icon == "middle" || $icon == 'small' || $icon == 'normal') {
        $data->configput('icon', $icon);
    } else {
        $icon = 'normal';
    }
} else if ($config['icon']) {
    $icon = $config['icon'];
}

$icon_radio[$icon] = 'checked';

if ($_POST['account']) {
    $targetaccount = $_POST['account'];
    if ($_POST['accountcontrol'] == 'delete') {
        $data->accountclear($targetaccount);
        $disableaccount = array_keys($accountlist, $targetaccount);
        unset($accountlist[$disableaccount]);
        $accountlist = array_values($accountlist);
        if ($targetaccount == $config['current_account']) {
            $config['current_account'] = $accountlist[0];
            $data->configput('current_account', $config['current_account']);
        }
    } else {
        $data->configput('current_account', $targetaccount);
        $config['current_account'] = $targetaccount;
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>設定 | PSPったー</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="/style.css" rel="stylesheet" type="text/css">
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
                    <input type="text" size="20" name="footer" value="<?php echo htmlspecialchars($footer) ?>"> <a href="/help.html#footer">?</a>
                </p>
                <p>
                    アイコン
                    <input type="radio" name="icon" value="normal" <?php echo $icon_radio['normal'] ?>>普通(48px)
                    <input type="radio" name="icon" value="middle" <?php echo $icon_radio['middle'] ?>>中(32px)
                    <input type="radio" name="icon" value="small" <?php echo $icon_radio['small'] ?>>小(16px)
                    <input type="radio" name="icon" value="disable" <?php echo $icon_radio['disable'] ?>>なし
                </p>
                <p>
                    LoJAX
                    <input type="radio" name="lojax" value="enable" <?php echo $lojax_radio['enable'] ?>>有効
                    <input type="radio" name="lojax" value="disable" <?php echo $lojax_radio['disable'] ?>>無効 <a href="/>help.html#lojax">?</a>
                </p>
                <p>
                    <select name="account">
                        <?php foreach ($accountlist as $account) { ?>
                            <?php if ($account == $config['current_account']) { ?>
                                <option value="<?php echo $account ?>" selected><?php echo $account ?></option>
                            <?php } else { ?>
                                <option value="<?php echo $account ?>"><?php echo $account ?></option>
                            <?php
                            }
                        }
                        ?>
                    </select>
                    <input type="checkbox" name="accountcontrol" value="delete">削除
                    <a href="/?redirect">アカウントの追加</a>
                </p>
                <input type="submit" value="設定終了">
            </form>
        </div>
        <div class="normal">
            <form action="/profile_image.php" method="post" enctype="multipart/form-data">
                <input type="file" name="image">
                <input type="submit" value="アイコンを変更">
            </form>
            ※時間がかかります＞＜；
        </div>
    </body>
</html>
