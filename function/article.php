<?php
$data = array();
$data['Err'] = '';
$article = new Article;

if(empty($_GET['action'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

if($_GET['action'] == 'publish'){
    if(empty($_POST['title'])){
        $data['Err'] = text_r('標題不可為空', '标题不可为空','Title is required');
        echo json_encode($data);
        exit();
    }

    if($_POST['classify'] == ''){
        $data['Err'] = text_r('請選擇看板', '请选择看板','Please select a forum');
        echo json_encode($data);
        exit();
    }

    if(empty($_POST['content']) && empty($_POST['attachment'])){
        $data['Err'] = text_r('內容不可為空', '内容不可为空','Content could not be empty');
        echo json_encode($data);
        exit();
    }

    // if serial == -1 , then publish the article (i.e. INSERT)
    // otherwise, update the article
    if($_POST['serial'] != -1){
        // check the article owner
        $artinfo = Article::get_info_by_serial($_POST['serial']);
        if(empty($artinfo) || $artinfo['USER']['ID'] != $_SESSION['login_id']){
            $data['Err'] = text_r('非文章持有人', '非文章持有人','No authority');
            echo json_encode($data);
            exit();
        }
    }

    // 檢查是否有被禁止發文
    // $is_banned (mixed) FALSE or DEADLINE (timestamp)
    $is_banned = Punish::user_is_banned($_SESSION['login_id'], $_POST['classify']);
    if($is_banned !== FALSE){
        $data['Err']  = text_r('水桶期間不可於此板發文', '水桶期间不可于此板发文','Your account is currently banned');
        $data['Err'] .=($is_banned != Punish::FOREVER)? text_r('直到 ','直到 ','Until ').render_complete_time($is_banned).text_r(' 結束',' 结束',' Ends') : text_r('直到管理員或版主允許', '直到管理员或版主允许','Contact Administrator to get unbanned');

        echo json_encode($data);
        exit();
    }

    if(!$article->publish($_SESSION['login_id'], $_POST['title'], $_POST['classify'], $_POST['content'], $_POST['attachment'], $_POST['serial'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    $data['Serial'] = ($_POST['serial'] != -1)? $_POST['serial'] : $article->get_serial();
    echo json_encode($data);
    exit();
}else if($_GET['action'] == 'delete'){
    if(empty($_POST['serial'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $article_info = Article::get_info_by_serial($_POST['serial']);
    $is_moderator = Classify::is_moderator($_SESSION['login_id'], $article_info['CLASSIFY']);

    // 非本人且非管理員且非板主
    if($article_info['USER']['ID'] != $_SESSION['login_id'] && !User::is_manager($_SESSION['login_id'])
       && !$is_moderator){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if(!$article->delete($_POST['serial'])){
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
