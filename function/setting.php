<?php
// THESE FUNCTIONS CAN ONLY BE USED BY MANAGERS OR MODERATORS.
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

if($_GET['type'] == 'add_new_board' || $_GET['type'] == 'modify_board'){
    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if($_POST['board_id'] == '' || empty($_POST['board_name'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if($_POST['board_name']['zh-tw'] == '' || $_POST['board_name']['zh-cn'] == ''){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if(empty($_POST['moderator_list'])){
        $_POST['moderator_list'] = "{}";
    }
    $moderator_list = json_decode($_POST['moderator_list'], TRUE);

    if($_GET['type'] == 'add_new_board'){
        if(!Classify::add($_POST['board_id'], $_POST['board_name'], $moderator_list)){
            $data['Err'] = Classify::get_last_error();
            echo json_encode($data);
            exit();
        }
    }else if($_GET['type'] == 'modify_board'){
        if(!Classify::add($_POST['board_id'], $_POST['board_name'], $moderator_list, Classify::MODIFY)){
            $data['Err'] = Classify::get_last_error();
            echo json_encode($data);
            exit();
        }
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'delete_board'){
    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if(empty($_POST['board_id'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if(!Classify::delete_empty($_POST['board_id'])){
        $data['Err'] = Classify::get_last_error();
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'diving_mode'){
    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if($_POST['diving'] === null || ($_POST['diving'] !== 'true' && $_POST['diving'] !== 'false')){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if(!User::update_diving_mode($_SESSION['login_id'], ($_POST['diving'] === 'true') ? TRUE : FALSE)){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'delete_file'){
    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if(empty($_POST['server_name']) || empty($_POST['file_type'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    File::delete_by_name_and_type($_POST['server_name'], $_POST['file_type']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'update_user_permission'){
    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if($_POST['permission'] === null || $_POST['user_id'] === null){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $permission = (int)$_POST['permission'];
    if(!User::update_permission($_POST['user_id'], $permission)){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'check_the_user_can_be_moderator'){
    if($_POST['id'] === null){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if(!User::is_manager($_SESSION['login_id'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    $result = User::is_manager($_POST['id']);
    if(User::last_error() === 'user-not-found'){
        $data['Err'] = 'User not found';
        echo json_encode($data);
        exit();
    }

    if($result === TRUE){
        $data['Err'] = 'Is manager';
        echo json_encode($data);
        exit();
    }

    $data['Err'] = '';
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'set_top'){
    if($_POST['serial'] === null || empty($_POST['reset'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $article_info = Article::get_info_by_serial($_POST['serial']);
    if($article_info === null){
        $data['Err'] = 'Serial not found';
        echo json_encode($data);
        exit();
    }

    if(!User::is_manager($_SESSION['login_id']) && !Classify::is_moderator($_SESSION['login_id'], $article_info['CLASSIFY'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    $check = ($_POST['reset'] == "true")? Article::reset_top($article_info['CLASSIFY']) : Article::set_top($_POST['serial'], $article_info['CLASSIFY']);

    if(!$check){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'punish'){
    if(empty($_POST['id']) || empty($_POST['classify']) || empty($_POST['expired'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }

    if(!User::is_manager($_SESSION['login_id']) && !Classify::is_moderator($_SESSION['login_id'], $_POST['classify'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    $result = User::is_manager($_POST['id']);
    if(User::last_error() === 'user-not-found'){
        $data['Err'] = 'User not found';
        echo json_encode($data);
        exit();
    }

    if($_POST['classify'] != 'all' && Classify::get_info_by_cid($_POST['classify']) === null){
        $data['Err'] = 'Board not found';
        echo json_encode($data);
        exit();
    }

    if($_POST['expired'] != '-1' && $_POST['expired'] <= time()){
        $data['Err'] = 'Expire value is invalid';
        echo json_encode($data);
        exit();
    }

    if($_POST['expired'] == '-1') $_POST['expired'] = Punish::FOREVER;
    Punish::add($_POST['id'], $_POST['classify'], $_POST['expired']);

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'delete_punish'){


    if(empty($_POST['serial'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }

    $punish_info = Punish::get_by_serial($_POST['serial']);
    if($punish_info === null){
        // 已經刪除
        echo json_encode($data);
        exit();
    }

    if(!User::is_manager($_SESSION['login_id']) && !Classify::is_moderator($_SESSION['login_id'], $punish_info['CLASSIFY_ID'])){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if(!Punish::delete($_POST['serial'])){
        $data['Err'] = 'Error: Database';
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
