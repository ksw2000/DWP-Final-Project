<?php
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

$reply = new Reply;

if($_GET['type'] == 'add'){
    if(empty($_POST['article_serial']) || empty($_POST['content'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $article_info = Article::get_info_by_serial($_POST['article_serial']);
    if(empty($article_info)){
        $data['Err'] = 'The article does not exist';
        echo json_encode($data);
        exit();
    }

    // 被水桶者不可以使用 reply 功能
    $is_banned = Punish::user_is_banned($_SESSION['login_id'], $article_info['CLASSIFY']);
    if($is_banned !== FALSE){
        $data['Err']  = '水桶期間不可於此板留言 ';
        $data['Err'] .= ($is_banned != Punish::FOREVER)? '直到 '.render_complete_time($is_banned).' 結束' : '直到管理員或版主允許';

        echo json_encode($data);
        exit();
    }

    $new_serial = Reply::add_reply($_POST['article_serial'], $_SESSION['login_id'], $_POST['tag'], $_POST['content']);
    // if $new_serial is 0, error occurred.
    if($new_serial == 0){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    Notice::reply($_SESSION['login_id'], $new_serial);
    if($_POST['tag'] != '0'){
        Notice::reply_to_your_comment($_SESSION['login_id'], $new_serial, $_POST['tag']);
    }

    $data['New_serial'] = $new_serial;
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'update'){
    if(empty($_POST['serial']) || empty($_POST['content'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    if($reply->get_reply_owner($_POST['serial']) == ''){
        $data['Err'] = 'Can NOT edit. The reply has been deleted already.';
        echo json_encode($data);
        exit();
    }

    if($reply->get_reply_owner($_POST['serial']) != $_SESSION['login_id']){
        $data['Err'] = 'Can NOT edit. The reply has been not belong to you.';
        echo json_encode($data);
        exit();
    }

    if(!$reply->update_reply($_POST['serial'], $_POST['content'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    echo json_encode($data);
    exit();

}else if($_GET['type'] == 'delete'){
    if(empty($_POST['serial'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $reply_info = Reply::get_reply_by_serial($_POST['serial']);
    if(empty($reply_info)){
        $data['Err'] = '無該回應';
        echo json_encode($data);
        exit();
    }

    $article_info = Article::get_info_by_serial($reply_info['ARTICLE_SERIAL']);

    if(empty($article_info)){
        $data['Err'] = '無該文';
        echo json_encode($data);
        exit();
    }

    $is_moderator = Classify::is_moderator($_SESSION['login_id'], $article_info['CLASSIFY']);

    if($reply_info['USER']['ID'] != $_SESSION['login_id'] && !User::is_manager($_SESSION['login_id'])
       && !$is_moderator){
        $data['Err'] = '非本人且非管理員且非板主';
        echo json_encode($data);
        exit();
    }

    if(!$reply->delete($_POST['serial'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    Notice::reply($_SESSION['login_id'], $_POST['serial'], Notice::DELETE);
    if($reply_info['TAG'] != 0){
        Notice::reply_to_your_comment($_SESSION['login_id'], $_POST['serial'], $reply_info['TAG'], Notice::DELETE);
    }

    echo json_encode($data);
    exit();
}

$data['Err'] = 'HTTP GET parameters err';
echo json_encode($data);
exit();
?>
