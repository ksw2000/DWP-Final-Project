<?php
$data = array();
$data['Err'] = '';

if(!(empty($_POST['article_serial']) xor empty($_POST['reply_serial']))  || ($_POST['type'] != 0 && $_POST['type'] != 1)){
    $data['Err'] = 'HTTP POST parameters err';
    echo json_encode($data);
    exit();
}

if(!empty($_POST['article_serial'])){
    $row = Interactive::like_article_auto($_SESSION['login_id'], $_POST['article_serial'], $_POST['type']);
    $data['Num-like'] = $row['LIKE_NUM'];
    $data['Num-dislike'] = $row['DISLIKE_NUM'];

    if(Interactive::get_last_action() == Interactive::LIKE){
        Notice::like($_SESSION['login_id'], $_POST['article_serial']);
    }else if(Interactive::get_last_action() == Interactive::CANCEL_LIKE){
        Notice::like($_SESSION['login_id'], $_POST['article_serial'], Notice::CANCEL);
    }

    echo json_encode($data);
    exit();
}

if(!empty($_POST['reply_serial'])){
    $row = Interactive::like_reply_auto($_SESSION['login_id'], $_POST['reply_serial'], $_POST['type']);
    $data['Num-like'] = $row['LIKE_NUM'];
    $data['Num-dislike'] = $row['DISLIKE_NUM'];

    echo json_encode($data);
    exit();
}
?>
