<?php
    include './lib/vendor.php';
    $id  = $_POST['id'] ?? '';
    $pwd = $_POST['pwd'] ?? '';
    $logout = isset($_GET['logout']);
    $reg = isset($_GET['reg']);
    $forget_pwd = isset($_GET['forget']);
    $wait_email = isset($_GET['wait_email']);
    $function_forget_pwd = isset($_GET['function_forget_pwd']);
    $reset_pwd = isset($_GET['reset_pwd']);
    $function_reset_pwd = isset($_GET['function_reset_pwd']);
    $change_email = isset($_GET['change_email']);
    $normal = !($logout || $forget_pwd || $function_forget_pwd || $wait_email || $reset_pwd || $function_reset_pwd || $change_email ||$reg);
    session_start();

    // 處理登入
    if($id != '' && $pwd != ''){
        $data = array();
        $data['Err'] = '';
        if(User::check_id_pwd($id, $pwd)){
            $_SESSION['login'] = TRUE;
            $_SESSION['login_id'] = $id;
            $_SESSION['user_info'] = User::get_user_public_info($id);
            User::update_online($id);
        }else{
            $data['Err'] = '帳號密碼輸入錯誤';
            sleep(5);
        }
        echo json_encode($data);
        exit();
    }

    // 處理登出
    if($logout){
        User::update_online($_SESSION['login_id'], 0);
        session_destroy();
        header('Location: /');
        exit();
    }

    // 處理忘記密碼
    if($function_forget_pwd){
        $data = array();
        $data['Err'] = '';
        if(empty($_POST['id_or_email'])){
            $data['Err'] = 'HTTP POST parameters err';
            echo json_encode($data);
            exit();
        }

        if(!User::forget_pwd($_POST['id_or_email'])){
            $data['Err'] = '找不到該 ID 及 郵件地址';
            // 延遲
            sleep(5);
            echo json_encode($data);
            exit();
        }

        echo json_encode($data);
        exit();
    }

    // 處理重設密碼
    if($function_reset_pwd){
        $data = array();
        $data['Err'] = '';
        if(empty($_POST['token']) || empty($_POST['new']) || empty($_POST['retype'])){
            $data['Err'] = 'HTTP GET parameters err';
            echo json_encode($data);
            exit();
        }

        // 透過 token 找 ID
        $user_id = User::get_id_by_user_forget_pwd_token($_POST['token']);
        if($user_id === NULL){
            $data['Err'] = '錯誤：驗證無效，請回到：<a class="light" href="/login?forget">忘記密碼</a> 重新步驟';
            sleep(5);
            echo json_encode($data);
            exit();
        }

        if($_POST['new'] != $_POST['retype']){
            $data['Err'] = '新密碼與確認密碼不符';
            echo json_encode($data);
            exit();
        }

        if(!User::check_pwd_fmt($_POST['new'])){
            $data['Err'] = User::last_error();
            echo json_encode($data);
            exit();
        }

        if(!User::change_pwd($user_id, $_POST['new'])){
            $data['Err'] = 'Error: Database';
            echo json_encode($data);
            exit();
        }

        echo json_encode($data);
        exit();
    }

    // 重設密碼頁面
    if($reset_pwd || $change_email){
        if(empty($_GET['token'])){
            die("TOKEN CANNOT FOUND");
            exit();
        }
        if($change_email){
            $id_and_email = User::get_id_and_email_by_user_change_email_token($_GET['token']);
            if($id_and_email == NULL){
                sleep(5);
                die("ERROR, TRY AGAIN INVALID TOKEN");
                exit();
            }else{
                $user_info = User::get_user_public_info($id_and_email['ID']);
                $new_email = $id_and_email['NEW_EMAIL'];
            }
        }
    }

    // 如果已經登入不顯示畫面，跳轉開
    // 更換 email 所顯示的輸入密碼窗不在此限
    if($_SESSION && $_SESSION['login'] && !$change_email){
        header('Location: /');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="zh-tw" style="height:100%;">
<head>
    <title>音樂論壇</title>
    <!--<link rel="shortcut icon" type="image/x-icon" href="/img/favicon.png">-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="/assets/css/for-login.css?<?php echo time();?>">
    <!--<link rel="shortcut icon" href="/favicon.ico">-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="theme-color" content="#e8e8e8"/>
    <script src="/assets/js/jquery-3.5.1.js"></script>
</head>
<body>
<div id="notice">
    <div id="notice-content"></div>
    <div id="notice-close"><i class="material-icons" onclick="notice_close()">close</i></div>
</div>
<div id="login-parent">
    <div id="login">
    <?php if($normal):?>
        <h4 style="text-align:center;font-size:50px;">登入</h4>
        <div class="col">
            <input class="light" autocomplete="off" type="text" placeholder="帳號" id="id" onkeydown="key_enter(this, login)" autofocus/>
        </div>
        <div class="col">
            <input class="light" autocomplete="off" type="password" placeholder="密碼" id="pwd" onkeydown="key_enter(this, login)"/>
        </div>
        <div class="col">
            <span id="error"></span>
        </div>
        <div class="col" id="loading" style="display:none;">
            <div class="preloader-wrapper small active loader-center">
                <div class="spinner-layer spinner-green-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                    <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <center><a class="waves-effect waves-light btn" onclick="login()">登入</a></center>
        </div>
        <div class="col">
            <div class="block">
                <a class="light" href="/login?forget">忘記密碼？</a>
                <a class="light" href="/login?reg">新增帳號</a>
            </div>
        </div>
    <?php elseif($reg):?> <!-- Create a new account -->
        <div id="add-new-user">
            <div class="col">
                <span style="font-size: 50px; margin-left: 5%;">建立新帳號</span>
            </div>
            <div class="col">
                <label for="add-user-id">帳號</label>
                <input class="light required" id="add-user-id" type="text" placeholder="4 ~ 30" autofocus>
            </div>
            <div class="col">
                <label for="add-user-pwd">密碼</label>
                <input class="light required" id="add-user-pwd" autocomplete="off" type="password" placeholder="8 ~ 30">
            </div>
            <div class="col">
                <label for="add-user-pwd-2">確認密碼</label>
                <input class="light required" id="add-user-pwd-2" autocomplete="off" type="password">
            </div>
            <div class="col">
                <label for="add-user-name">暱稱</label>
                <input class="light required" id="add-user-name" type="text" placeholder="">
            </div>
            <div class="col">
                <label for="add-user-email">電子郵件</label>
                <input class="light required" id="add-user-email" type="email" placeholder="sakura@example.com">
            </div>
            <div class="col">
                <label for="select-preset-language">預設語言</label>
                <div class="col">
                <select id="select-preset-language" class="browser-default">
                    <option value="zh-tw">繁體中文</option>
                    <option value="zh-cn">簡體中文</option>
                    <option value="en">English</option>
                </select>
                </div>
            </div>
            <div class="col">
                <center><a class="waves-effect waves-light btn" onclick="add_new_user()">註冊</a></center>
            </div>
            <div class='col'>
                <center><div class="block"><a href="/login" class="light">返回首頁</a></div></center>
            </div>
        </div>
    <?php elseif($forget_pwd):?>
        <h4 style="text-align:center;">忘記密碼？</h4>
        <div class="col">
            <input class="light" autocomplete="off" type="text" placeholder="帳號 或 Email" id="id_or_email" onkeydown="key_enter(this, forget_pwd)" autofocus/>
        </div>
        <div class="col">
            <span id="error"></span>
        </div>
        <div class="col" id="loading" style="display:none;">
            <div class="preloader-wrapper small active loader-center">
                <div class="spinner-layer spinner-green-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                    <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <center><a class="waves-effect waves-light btn" onclick="forget_pwd()">要求寄發驗證信</a></center>
        </div>
        <div class="col">
            <div class="block"><a class="light" href="/login">返回登入頁</a></div>
        </div>
    <?php elseif($wait_email):?>
        <h4 style="text-align:center;">請至郵件箱等候驗證信！</h4>
        <div class="col">
            <center><a class="light" href="/login?forget">重新寄發</a> | <a class="light" href="/login">返回登入頁</a></center>
        </div>
    <?php elseif($reset_pwd):?>
        <h4 style="text-align:center;">輸入新密碼！</h4>
        <div class="col">
            <input class="light" autocomplete="off" type="password" placeholder="新密碼" id="new-pwd" onkeydown="key_enter(this, reset_pwd)"/>
        </div>
        <div class="col">
            <input class="light" autocomplete="off" type="password" placeholder="確認新密碼" id="retype-pwd" onkeydown="key_enter(this, reset_pwd)"/>
        </div>
        <div class="col">
            <span id="error"></span>
        </div>
        <div class="col" id="loading" style="display:none;">
            <div class="preloader-wrapper small active loader-center">
                <div class="spinner-layer spinner-green-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                    <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <center><a class="waves-effect waves-light btn" onclick="reset_pwd()">更改密碼</a></center>
        </div>
        <div class="col">
            <center><a class="light" href="/login?forget">重新寄發</a> | <a class="light" href="/login">返回登入頁</a></center>
        </div>
    <?php elseif($change_email):?><!--change email-->
        <h4 style="text-align:center;">欲變更電郵請輸入密碼！</h4>
        <div class="col">
            <p style="text-align: center;"><?php echo $user_info['EMAIL'].' -> '.$new_email;?></p>
        </div>
        <div class="col">
            <input class="light" autocomplete="off" type="password" placeholder="密碼" id="pwd" onkeydown="key_enter(this, ensure_change_email)"/>
        </div>
        <div class="col">
            <span id="error"></span>
        </div>
        <div class="col" id="loading" style="display:none;">
            <div class="preloader-wrapper small active loader-center">
                <div class="spinner-layer spinner-green-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                    <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <center><a class="waves-effect waves-light btn" onclick="ensure_change_email()">更改密碼</a></center>
        </div>
    <?php endif;?>

</div>
<script>
window.lock = false;
function login(){
    if(window.lock === true) return;
    $("#login #error").text("");
    $("#loading").fadeIn();
    window.lock = true;
    var id  = $("#login #id").val();
    var pwd = $("#login #pwd").val();
    if(id === "" || pwd === ""){
        $("#loading").fadeOut(function(){
            $("#login #error").text("帳號或密碼不可為空！");
        });
        window.lock = false;
        return;
    }
    $.post('/login.php',{
        id: id,
        pwd: pwd
    },function(data){
        window.lock = false;
        $("#loading").fadeOut(function(){
            if(data["Err"]){
                $("#login #error").text(data["Err"]);
            }else{
                window.location.href="/";
            }
        });
    },'json');
}

function key_enter(e, f){
    var keycode = (window.event)? window.event.keyCode : e.which;
    if(keycode === 13) f();
}

function forget_pwd(){
    $("#login #error").text("");
    $("#loading").fadeIn();
    if(window.lock === true) return;
    window.lock = true;
    var id_or_email = $("#login #id_or_email").val();
    if(id_or_email == ''){
        $("#loading").fadeOut(function(){
            $("#login #error").text("欄位不可為空！");
        });
        window.lock = false;
        return;
    }

    $.post('/login?function_forget_pwd',{
        "id_or_email": id_or_email
    },function(data){
        console.log(data);
        window.lock = false;
        $("#loading").fadeOut(function(){
            if(data['Err']){
                $("#login #error").text(data['Err']);
            }else{
                window.location.href="/login?wait_email";
            }
        });
    }, 'json');
}

function reset_pwd(){
    if(window.lock === true) return;
    window.lock = true;
    var token = window.location.href.match(/[\?&]token=(.*)$/, '');
    token = (token === null)? '': token[1];

    var new_pwd = $("#new-pwd").val();
    var retype_pwd = $("#retype-pwd").val();

    if(new_pwd === '' || retype_pwd === ''){
        $("#login #error").text('新密碼與確認密碼不可為空');
        window.lock = false;
        return;
    }

    if(new_pwd != retype_pwd){
        $("#login #error").text('新密碼與確認密碼不符');
        window.lock = false;
        return;
    }

    $("#login #error").html("");
    $("#loading").fadeIn();
    $.post('/login?function_reset_pwd',{
        token: token,
        new: new_pwd,
        retype: retype_pwd
    },function(data){
        window.lock = false;
        $("#loading").fadeOut(function(){
            if(data['Err'] === 'only[a-zA-Z0-9-_]{8,30}'){
                msg = '新密碼只能由「字母、數字、-、_」組成且介於8~30字';
            }else if(data['Err'] === 'need-0-9'){
                msg = '新密碼必需要含有數字';
            }else if(data['Err'] === 'need-a-z'){
                msg = '新密碼必需要含有英文';
            }else{
                msg = data['Err'];
            }

            if(data['Err']){
                $("#login #error").html(msg);
            }else{
                window.location.href="/login";
            }
        });
    }, 'json');
}

function ensure_change_email(){
    if(window.lock === true) return;
    window.lock = true;
    var token = window.location.href.match(/[\?&]token=(.*)$/, '');
    token = (token === null)? '': token[1];

    if($("#pwd").val() == ''){
        $("#login #error").text('密碼欄位不可為空');
        window.lock = false;
        return;
    }

    $("#login #error").html("");
    $("#loading").fadeIn();
    $.post('/function/user-setting?type=ensure_change_email',{
        pwd: $("#pwd").val(),
        token: token
    },function(data){
        window.lock = false;
        console.log(data);

        $("#loading").fadeOut(function(){
            if(data['Err'] === 'wrong token'){
                data['Err'] = '網址過期，請重新操作';
            }else if(data['Err'] === 'wrong pwd'){
                data['Err'] = '密碼錯誤';
            }else{
                $("#login #error").html('<span style="color: #00ff0f;">變更成功</span>');
                window.location.href="/login";
                return;
            }

            $("#login #error").html(data['Err']);
        });
    }, 'json');
}

function add_new_user(){
    var id = $("#add-new-user #add-user-id").val();
    var pwd = $("#add-new-user #add-user-pwd").val();
    var pwd2 = $("#add-new-user #add-user-pwd-2").val();
    var name = $("#add-new-user #add-user-name").val();
    var email = $("#add-new-user #add-user-email").val();
    var lang = $("#add-new-user #select-preset-language").val();
    var msg='';

    //Report Error
    for(var i = 0, err = false; i<$("#add-new-user .required").length; i++){
        if($("#add-new-user .required").eq(i).val() === ''
        || typeof($("#add-new-user .required").eq(i).val()) === 'undefined' ){
            err = true;
            switch(i){
                case 0:
                    msg += '請填寫帳號<br>';
                    break;
                case 1:
                    msg += '請填寫密碼<br>';
                    break;
                case 2:
                    msg += '請填寫確認密碼<br>';
                    break;
                case 3:
                    msg += '請填寫暱稱<br>';
                    break;
                case 4:
                    msg += '請填寫電子郵件<br>';
                    break;
                case 5:
                    msg += '請選擇語言<br>';
                    break;
                default:
                    break;
            }
            $("#add-new-user .required").eq(i).addClass('err');
        }else{
            $("#add-new-user .required").removeClass('err');
        }
    }

    if(err){
        notice(msg);
        return;
    }

    //Send data to back-end to check input validity
    $.post('/function/user-setting?type=add_new_user', {
        'id': id,
        'pwd': pwd,
        'pwd2': pwd2,
        'name': name,
        'email': email,
        'lang': lang
    }, function(data){
        if(data['Err']){
            if(data['Err'] === 'only[a-zA-Z0-9-_]{8,30}'){
                msg = '<?php text('密碼只能由「字母、數字、-、_」組成且介於8~30字', '密码只能由「字母、数字、-、_」组成且介于8~30字','Must be 8-30 characters, characters are letter,number,-,_ ')?>';
            }else if(data['Err'] === 'need-0-9'){
                msg = '<?php text('密碼必需要含有數字', '密码必需要含有数字','Need at least one number')?>';
            }else if(data['Err'] === 'need-a-z'){
                msg = '<?php text('密碼必需要含有英文', '密码必需要含有英文','Need at least one letter')?>';
            }else if(data['Err'] === 'only[a-zA-Z0-9-_{4,30}]'){
                msg = '<?php text('帳號只能由「字母、數字、-、_」組成且介於4~30字', '帐号只能由「字母、数字、-、_」组成且介于4~30字','Must be 4-30 characters, characters are letter,numbers,-,_ ')?>';
            }else if(data['Err'] === 'ID existed'){
                msg = '<?php text('帳號已經存在', '帐号已经存在','Account exists')?>';
            }else if(data['Err'] === 'pwd is not equal pwd2'){
                msg = '<?php text('密碼與確認密碼不符', '密码与确认密码不符', '密码与确认密码不符')?>';
            }else{
                notice(data['Err']);
                return;
            }
            notice(msg);
        }else{
            window.location.href="/login";
        }
    }, 'json');
}

function notice_close(){
    $("#notice").slideUp(500);
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
</script>
</body>
</html>
