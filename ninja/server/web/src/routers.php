<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($page == 'post') {
    include("screens/post.php");
    return;
}
if ($page == 'community') {
    include("screens/community.php");
    return;
}
if ($page == 'download') {
    include("screens/download.php");
    return;
}
if ($page == 'ranking') {
    include("screens/ranking.php");
    return;
}
if ($page == 'giftcode') {
    include("screens/giftcode.php");
    return;
}
if ($user == null) {
    include("screens/home.php");
    return;
}

switch ($page) {
    case 'admin':
        if ($tab === 'home') {
            include("screens/admin/index.php");
        } elseif ($tab === 'member') {
            include("screens/admin/member/index.php");
        } elseif ($tab === 'edit') {
            include("screens/admin/member/detail.php");
        } elseif ($tab === 'code') {
            include("screens/admin/giftcode/index.php");
        } elseif ($tab === 'create') {
            include("screens/admin/giftcode/create.php");
        } elseif ($tab === 'edit-giftcode') {
            include("screens/admin/giftcode/detail.php");
        } elseif ($tab === 'articles') {
            include("screens/admin/articles/articles.php");
        } elseif ($tab === 'update-file') {
            include("screens/admin/update/file.php");
        } elseif ($tab === 'recharge') {
            include("screens/admin/recharge/index.php");
        } elseif ($tab === 'bot') {
            include("screens/admin/bot/index.php");
        } elseif ($tab === 'notice') {
            include("screens/admin/notice/index.php");
        } elseif ($tab === 'user') {
            include("screens/admin/user/index.php");
        } elseif ($tab === 'server') {
            include("screens/admin/server/index.php");
        } elseif ($tab === 'boss') {
            include("screens/admin/boss/index.php");
        } elseif ($tab === 'shop') {
            include("screens/admin/shop/index.php");
        } elseif ($tab === 'logs') {
            include("screens/admin/logs/index.php");
        } elseif ($tab === 'clan') {
            include("screens/admin/clan/index.php");
        } elseif ($tab === 'event') {
            include("screens/admin/event/index.php");
        } else {
            include("screens/admin/index.php");
        }
        break;
    case 'squad':
        if ($tab === 'squad') {
            include("screens/squad/views.php");
        } elseif ($tab === 'create') {
            include("screens/squad/create.php");
        } elseif ($tab === 'info') {
            include("screens/squad/info.php");
        }else {
            include("screens/squad/views.php");
        }
        break;
    case 'recharge':
        include("screens/recharge.php");
        break;
    case 'exchange':
        include("screens/exchange.php");
        break;
    case 'gift':
        include("screens/gift.php");
        break;
    case 'user':
        include("screens/user.php");
        break;
    case 'home':
        include("screens/home.php");
        break;       
    default:
        include('404.php');
        break;
}
?>
