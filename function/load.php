<?php
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

if($_GET['type'] == 'load-reply'){
    if(empty($_POST['serial'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }

    $reply_list_item = Reply::get_reply_by_serial($_POST['serial']);
    $data['Render_reply'] = render_reply($reply_list_item, $_SESSION['user_info']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-attachment-list-editable'){
    if(empty($_POST['attachment'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }
    $data['Result'] = render_attachment_list($_POST['attachment'], TRUE);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-article-list-by-user-id'){
    if(empty($_GET['user_id'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }

    $is_star   = isset($_GET['star']);
    $from      = (empty($_GET['from']))? null : $_GET['from'];
    $num       = (empty($_GET['num']))? null : $_GET['num'];
    $view_mode = (empty($_GET['view_mode']))? 0 : (($_GET['view_mode'] == 'list')? RENDER_LIST_MODE : 0);
    $query     = (empty($_GET['query']))? '' : $_GET['query'];
    $article   = new Article;

    if($is_star){
        $article->get_article_list_by_userid_star($_GET['user_id'], $from, $num, $query);
    }else{
        $article->get_article_list_by_userid($_GET['user_id'], $from, $num, $query);
    }
    $flags  = ($from === null || $from === "0")? RENDER_FISRT_LIST : 0;
    $flags |= ($is_star && $_GET['user_id'] == $_SESSION['login_id'])? RENDER_STAR_MODE : 0;
    $flags |= (!empty($query))? RENDER_QUERY_MODE : 0;
    $flags |= $view_mode;
    $data['Render_result'] = render_article_list($article, $_SESSION['login_id'], $flags);
    $data['Next_from'] = $article->get_next();
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-published-article-list'){
    $from      = (empty($_GET['from']))? null : $_GET['from'];
    $num       = (empty($_GET['num']))? null : $_GET['num'];
    $classify  = (empty($_GET['classify']))? null : $_GET['classify'];
    $view_mode = (empty($_GET['view_mode']))? 0 : (($_GET['view_mode'] == 'list')? RENDER_LIST_MODE : 0);
    $query     = (empty($_GET['query']))? '' : $_GET['query'];
    $flags     = ($classify === null)? 0 : RENDER_CLASSIFY_TOP_BUTTON;
    $flags    |= ($from === null || $from === "0")? RENDER_FISRT_LIST : 0;
    $flags    |= (!empty($query))? RENDER_QUERY_MODE : 0;
    $flags    |= $view_mode;
    $article   = new Article;
    $article->get_published_article_list($classify, $from, $num, $query);
    $data['Render_result'] = render_article_list($article, $_SESSION['login_id'], $flags);
    $data['Next_from'] = $article->get_next();
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'load-reply-list'){
    if(empty($_GET['article_serial'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }
    $from = (empty($_GET['from']))? null : $_GET['from'];
    $num = (empty($_GET['num']))? null : $_GET['num'];

    $reply = new Reply;
    $reply->get_reply_list_by_article_serial($_GET['article_serial'], $from, $num);
    $data['Render_result'] = render_reply_list($reply, $_SESSION['user_info']);
    $data['Next_from'] = $reply->get_next();

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'load-render-board-list'){
    $data['Render_result'] = render_board_list();
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'load-board-info'){
    if(empty($_GET['cid'])){
        $data['Err'] = 'HTTP GET parameters err';
        echo json_encode($data);
        exit();
    }

    $data['Info'] = Classify::get_info_by_cid($_GET['cid']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-file-list'){
    $from = (empty($_GET['from']))? null : $_GET['from'];
    $num  = (empty($_GET['num']))? null : $_GET['num'];
    $find = isset($_GET['q']);
    $fl   = new FileList;

    $search_result_is_empty = FALSE;
    if($find && !empty($_GET['q'])){
        if($fl->find($_GET['q'])->num_rows == 0){
            $search_result_is_empty = TRUE;
        }
    }else{
        $fl->get_all_file_list(null, $from, $num);
    }

    if($search_result_is_empty){
        $data['Render_result'] = text_r('搜尋無結果', '搜寻无结果','No result');
    }else{
        $data['Render_result'] = render_file_list($fl);
    }

    $data['Next_from'] = $fl->get_next();

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'load-render-user-list'){
    $from = (empty($_GET['from']))? null : $_GET['from'];
    $num  = (empty($_GET['num']))? null : $_GET['num'];
    $find = isset($_GET['q']);
    $ul   = new UserList($_SESSION['login_id']);

    if($find && !empty($_GET['q'])){
        $user_info = $ul->find($_GET['q']);
    }else{
        $user_info = $ul->get_list_exclude_self($from, $num);
    }

    $data['User_info_list'] = array();
    $hash_str = '';
    if($user_info !== null){
        foreach($user_info as $value){
            $data['User_info_list'][$value['ID']] = $value;
            $hash_str .= $value['ID'];
        }
    }
    if($user_info->num_rows == 0 && !empty($_GET['q'])){
        $data['Render_result'] = text_r('搜尋無結果', '搜寻无结果','No result');
    }else{
        $data['Render_result'] = redner_user_list($ul);
    }

    $data['Next_from'] = $ul->get_next();
    $data['Hash'] = md5($hash_str);

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-punishment-list'){
    $from     = (empty($_GET['from']))? null : $_GET['from'];
    $num      = (empty($_GET['num']))? null : $_GET['num'];

    $is_manager = User::is_manager($_SESSION['login_id']);
    $cid_managed_by_moderator = Classify::get_cid_managed_by($_SESSION['login_id']);
    $is_moderator = ($cid_managed_by_moderator != null)? TRUE : FALSE;

    if(!$is_manager && !$is_moderator){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    $punishList = new PunishList;
    $punishList->get_punish_list($cid_managed_by_moderator, $from, $num);

    $data['Render_result'] = render_punishment_list($punishList);
    $data['Next_from'] = $punishList->get_next();

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-inbox'){
    $from     = (empty($_GET['from']))? null : $_GET['from'];
    $num      = (empty($_GET['num']))? null : $_GET['num'];

    $notice = new Notice;
    $notice_list = $notice->get_notice_list_by_user_to($_SESSION['login_id'], $from, $num);

    if($from === 0){
        $data['Render_result'] = render_notice_list($notice);
    }else{
        $data['Render_result'] = render_notice_list($notice, RENDER_FISRT_LIST);
    }
    $data['Next_from'] = $notice->get_next();

    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-online-list'){
    $data['Render_result'] = render_online_list();
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render-complete-article'){
    $article_info = Article::get_info_by_serial($_GET['serial']);
    $data = render_complete_article($article_info, $_SESSION['login_id']);
    /*
        $data['Render_result']
        $data['Next_reply']
    */
    $data['Classify'] = $article_info['CLASSIFY'];
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render_bio'){
    if(empty($_GET['be_visited_user'])){
        $data['Render_result'] = 'ERROR';
        echo json_encode($data);
        exit();
    }
    $data['Render_result'] = render_bio(User::get_user_public_info($_GET['be_visited_user'], User::MORE_INFO), $_GET['be_visited_user'] == $_SESSION['login_id']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'render_global_info'){
    $data['Render_result'] = render_global_info();
    $data['Title'] = "";
    echo json_encode($data);
    exit();
}

$data['Err'] = 'HTTP GET parameters err';
echo json_encode($data);
exit();
?>
