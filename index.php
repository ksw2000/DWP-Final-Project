<?php
if(!empty($_COOKIE['session_id'])){
    session_id($_COOKIE['session_id']);
}
session_start();

$url_id = $_GET['id'] ?? 'index';

include './lib/vendor.php'; //put in functions
if($url_id == 'function' && !empty($_GET['type']) && $_GET['type'] == 'add_new_user'){
    include './function/'.$_GET['include'].'.php';
    exit();
}else if(empty($_SESSION['login_id'])){
    header('Location: /login');
    exit();
}

User::update_online($_SESSION['login_id']);

// 將 /user/[自己] 轉到 /user
if($url_id == 'user' && isset($_GET['visted-user']) && $_GET['visted-user'] == $_SESSION['login_id']){
    header('Location: /user');
    exit();
}

// 將 /function/(.*?) include function/(.*?).php
if($url_id == 'function'){
    include './function/'.$_GET['include'].'.php';
    exit();
}

if($url_id == 'private_assets'){
    $path = './assets/private/'.$_GET['url'];
    $ext  = pathinfo($path)['extension'];
    if(!file_exists($path)) exit();
    preg_match('/^(.*?)\/(.*?)$/', $_GET['url'], $matches);
    switch($matches[1]){
        case 'profile':
        case 'img':
            $type = 'image/';
            switch($ext){
                case "jfif":
                case "pjpeg":
                case "pjp":
                case "jpg":
                    $type .= "jpeg";
                    break;
                case "tif":
                    $type .= "tiff";
                    break;
                case "svg":
                    $type .= "svg+xml";
                    break;
                default:
                    $type .= $ext;
            }
            header('Content-type: '.$type);
            header('Cache-Control: private, max-age=8640000');
            readfile($path);
            exit();
            break;
        case 'music':
            $type = 'audio/';
            switch($ext){
                case "mp3":
                    $type .= 'mpeg';
                    break;
                default:
                    goto process_attachment;
            }
            header('Content-type: '.$type);
            header('Content-length: '.filesize($path));
            header('Content-Disposition: filename="'.basename($path));
            header('Content-Transfer-Encoding: binary');
            header('X-Pad: avoid browser bug');
            header('Cache-Control: private');
            header('Accept-ranges: bytes');
            readfile($path);
            exit();
            break;
    }

process_attachment:
    header('Content-type:application/octet-stream');
    header('Content-Disposition:attachment;filename = '.basename($path));
    header('Accept-ranges: bytes');
    header('Accept-length: '.filesize($path));
    readfile($path);
    exit();
}

$title = text_r('龍哥論壇', '龙哥论坛', 'Longer Forum');
$be_visited_user = $_GET['visted-user'] ?? $_SESSION['login_id'];

if($url_id == 'user' || $url_id == 'chat'){
    $user_info = User::get_user_public_info($be_visited_user, TRUE);
    $title = $user_info['NAME'].'::'.$title;
}else if($url_id == 'add'){
    if(isset($_GET['edit'])){
        $title = text_r('修改貼文', '修改贴文', 'Edit').'::'.$title;
    }else{
        $title = text_r('貼文', '贴文', 'Post').'::'.$title;
    }
}else if($url_id == 'setting'){
    $title = text_r('設定', '设置','Setting').'::'.$title;
}else if($url_id == 'article'){
    $serial = $_GET['serial'];
    if(empty($serial) || !Article::exist($serial)){
        header('Location: /');
    }
    $article_info = Article::get_info_by_serial($serial);
    $title = $article_info['TITLE'].'::'.$article_info['USER']['NAME'].'::'.$title;
}

?>

<!DOCTYPE html>
<html lang="zh-tw" style="height:100%;">
<head>
    <title><?php echo $title;?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/main.css?<?php echo time();?>">
    <link rel="stylesheet" href="/assets/css/article-list.css?<?php echo time();?>">
    <link rel="stylesheet" href="/assets/css/bio.css?<?php echo time();?>">
    <link rel="stylesheet" href="/assets/css/article-container.css?<?php echo time();?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="/assets/css/cropper.min.css">
    <?php if($url_id == 'index' || $url_id == 'article' ||
             $url_id == 'user' || $url_id == 'chat'):?>
    <link rel="stylesheet" href="/assets/css/for-index-content.css?<?php echo time();?>">
    <?php elseif($url_id == 'setting'):?>
    <link rel="stylesheet" href="/assets/css/for-setting.css?<?php echo time();?>">
    <?php endif;?>
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="theme-color" content="#e8e8e8"/>
    <script src="/assets/js/jquery-3.5.1.js"></script>
    <?php if($url_id != 'add'):?>
    <script src="/assets/js/ckeditor5-inline/build/ckeditor.js"></script>
    <?php endif;?>
    <script type="text/javascript">
    var isIE = navigator.userAgent.search("Trident") > -1;

    if(isIE){
        var r = confirm("<?php text('因為安全性問題我們已不支援 IE 瀏覽器點擊確定已下載 Google Chrome', '因为安全性问题我们已不支援 IE 浏览器点击确定已下载 Google Chrome');?>");
        if(r){
            document.location.href="https://www.google.com/chrome/";
        }

        if(confirm("Press OK to download Opera")==true){
            document.location.href="https://www.opera.com/";
        }

        if(confirm("Press OK to download Firefox")==true){
            document.location.href="https://www.mozilla.org/firefox/new/";
        }

        alert("You are as stubborm as IE. Everybody don't like you.");

        document.location.href="https://www.google.com/chrome/";
    }


    function close_expand_more(obj){
        $(obj).children('ul.more-tool-list').fadeOut();
    }

    function open_expand_more(obj){
        $(obj).children('ul.more-tool-list').slideDown();
    }

    function notice_close(){
        $("#notice").slideUp(500);
    }

    function set_top(serial, reset){
        $.post('/function/setting?type=set_top', {
            'serial': serial,
            'reset': reset
        }, function(data){
            if(data['Err']){
                console.log(data['Err']);
                notice(data['Err']);
                return;
            }else{
                if(typeof window.refresh_function === 'function'){
                    window.refresh_function();
                }
                window.scrollTo({ top : 0, behavior: 'smooth'});
            }
        }, 'json');
    }

    function notice(msg){
        $("#notice #notice-content").html(msg);
        $("#notice").slideDown({
            start: function(){
                $(this).css({
                    display: "flex"
                })
            },
            complete: function(){
                setTimeout(function(){
                    notice_close();
                }, 5000);
            }
        });
    }

    function Inbox(){
        $('.continue-load-notice-button').hide('fast');
        var self = this;
        this.next = 0;
        this.load = function(from){
            $.get('/function/load', {
                'type': 'render-inbox',
                'from': from
            }, function(data){
                if(data['Err']){
                    console.log(data['Err']);
                }else{
                    self.next = data['Next_from'];
                    if(from === 0){
                        $("#inbox-list").html(data['Render_result']);
                    }else{
                        $("#inbox-list").append(data['Render_result']);
                    }
                    self.lock_continue_load_notice = false;
                }
            }, 'json');
        }

        this.continue_load = function(){
            $('.continue-load-notice-button').hide('fast');
            self.load(self.next);
        }

        this.update_read_time = function(){
            $.get('/function/notice?type=update_read_time', {}, function(){
                $("#new-message").fadeOut();
            });
        }

        this.goto_notice = function(serial, url){
            $.post('/function/notice?type=set_already_read', {
                'serial': serial
            },function(data){
                console.log(data);
                location.href = '/'+url;
            });
        }

        this.check_new_notice = function(){
            $.get('/function/notice?type=load_inbox_not_read_num', {},
            function(data){
                if(data['Num'] > 0){
                    $("#new-message").fadeIn();
                }else{
                    $("#new-message").fadeOut();
                }
            }, 'json');
        }

        this.delete_notice = function(serial){
            $.post('/function/notice?type=delete', {
                'serial': serial
            }, function(data){
                if(data['Err']){
                    console.log(data['Err']);
                }else{
                    $(".list[data-serial='" + serial + "']").slideUp();
                }
            }, 'json');
        }

        // constructor
        self.check_new_notice();
        setInterval(function(){
            self.check_new_notice();
        }, 40000);
        self.load(0);
    }

    function toggleInbox(){
        if($('#inbox').css('display') === 'none'){
            if(typeof window.inbox === 'undefined'){
                window.inbox = new Inbox();
            }
            window.inbox.update_read_time();
            $("#notifications-icon").text("close");
        }else{
            $("#notifications-icon").text("notifications");
        }
        $("#inbox").slideToggle();
    }

    function key_enter(e, call){
        var keycode;
        if(window.event){
            keycode = window.event.keyCode;
        }else if(e){
            keycode = e.which;
        }
        if(keycode == 13){
            call();
        }
    }

    window.onload = function(){
        window.inbox = new Inbox();
        setInterval(function(){
            $.get('/function/online',{});
        }, 40000);
    }
    </script>
    <script src="/assets/js/article.js?<?php echo time();?>"></script>
</head>
<body>
    <div id="notice">
        <div id="notice-content"></div>
        <div id="notice-close"><i class="material-icons" onclick="notice_close()">close</i></div>
    </div>
    <div id="inbox" class="article-container">
        <div id="inbox-list" class="article-list">
        </div>
    </div>
    <nav>
    <?php if($url_id == 'index' || $url_id == 'article' || $url_id == 'user'):?>
        <div><a href="/user" onclick="goto_user_by_user_id('<?php echo $be_visited_user?>'); return false;"><i class="material-icons" style="vertical-align: middle;">person</i></a></div>
        <div><a href="/" onclick="goto_index_by_tab('all'); return false;"><i class="material-icons" style="vertical-align: middle;">home</i></a></div>
    <?php else:?>
        <div><a href="/user"><i class="material-icons" style="vertical-align: middle;">person</i></a></div>
        <div><a href="/"><i class="material-icons" style="vertical-align: middle;">home</i></a></div>
    <?php endif;?>
        <div><a href="/add"><i class="material-icons" style="vertical-align: middle;">add_circle</i></a></div>
        <div><a href="javascript:void(0);" onclick="toggleInbox()"><i class="material-icons" style="vertical-align: middle;" id="notifications-icon">notifications</i><div id="new-message" class="lamp" style="display:none;"></div></a></div>
        <div><a href="/setting"><i class="material-icons" style="vertical-align: middle;">settings</i></a></div>
    </nav>
<?php
switch($url_id){
    case 'index':
    case 'article':
    case 'user':
        include './include/index-content.php';
        break;
    case 'inbox':
    case 'add':
    case 'setting':
    case 'chat':
        include './include/'.$url_id.'.php';
    default:
        // code...
        break;
}
?>
</body>
</html>
