<?php
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

if($_GET['type'] == 'edit_bio'){
    if(empty($_POST['name'])){
        // 該條錯誤訊息有綁定前端，不可更改
        $data['Err'] = 'name con be none-empty';
        echo json_encode($data);
        exit();
    }

    if(empty($_POST['more_info'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    User::update_name($_SESSION['login_id'], $_POST['name']);
    User::update_more_info($_SESSION['login_id'], $_POST['more_info']);

    $user_info = User::get_user_public_info($_SESSION['login_id'], TRUE);
    $data['render'] = render_bio($user_info, TRUE);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'change_password'){
    if(empty($_POST['ori']) || empty($_POST['new']) || empty($_POST['retype'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }

    if(!User::check_id_pwd($_SESSION['login_id'], $_POST['ori'])){
        // 該條錯誤訊息有綁定前端，不可更改
        $data['Err'] = 'password-error';
        echo json_encode($data);
        exit();
    }

    if($_POST['new'] != $_POST['retype']){
        $data['Err'] = 'confirm-password-error';
        echo json_encode($data);
        exit();
    }

    if(!User::check_pwd_fmt($_POST['new'])){
        $data['Err'] = User::last_error();
        echo json_encode($data);
        exit();
    }

    if(!User::change_pwd($_SESSION['login_id'], $_POST['new'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'change_email'){
    if(empty($_POST['email'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    Email::change_email($_SESSION['login_id'], $_POST['email']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'ensure_change_email'){
    if(empty($_POST['pwd']) || empty($_POST['token'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $id_and_email = User::get_id_and_email_by_user_change_email_token($_POST['token']);
    if($id_and_email == NULL){
        $data['Err'] = 'wrong token';
        sleep(5);
        echo json_encode($data);
        exit();
    }

    if(!User::check_id_pwd($id_and_email['ID'], $_POST['pwd'])){
        $data['Err'] = 'wrong pwd';
        sleep(5);
        echo json_encode($data);
        exit();
    }

    if(!User::update_email($id_and_email['ID'], $id_and_email['NEW_EMAIL'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    $_SESSION['user_info']['EMAIL'] = $id_and_email['NEW_EMAIL'];

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'change_language'){
    if(empty($_POST['lang']) || ($_POST['lang'] != 'zh-tw' && $_POST['lang'] != 'zh-cn')){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    /*0: 繁中 1: 簡中*/
    $lang_code = ($_POST['lang'] == 'zh-tw')? 0 : 1;
    if(!User::change_language($_SESSION['login_id'], $lang_code)){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    $_SESSION['user_info']['LANGUAGE'] = $lang_code;

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'add_new_user'){
    if(empty($_POST['id']) || empty($_POST['pwd']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['lang'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }
    
    if(User::id_existed($_POST['id'])){
        $data['Err'] = 'ID existed';    // 已綁定前端
        echo json_encode($data);
        exit();
    }

    if(!User::check_id_fmt($_POST['id'])){
        $data['Err'] = User::last_error();
        echo json_encode($data);
        exit();
    }

    if(!User::check_pwd_fmt($_POST['pwd'])){
        $data['Err'] = User::last_error();
        echo json_encode($data);
        exit();
    }

    if(!User::new($_POST['id'], $_POST['pwd'], $_POST['name'], $_POST['email'], $_POST['lang'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'delete_account'){
    if(empty($_POST['pwd'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    User::delete($_SESSION['login_id'], $_POST['pwd']);
    if(User::last_error() != ''){
        $data['Err'] = User::last_error();
        echo json_encode($data);
        exit();
    }
    echo json_encode($data);
    exit();
}

$data['Err'] = 'HTTP GET parameters err';
echo json_encode($data);
exit();
?>
