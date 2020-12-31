<?php
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

if($_GET['type'] == 'update_read_time'){
    User::update_read_time($_SESSION['login_id']);
    exit();
}else if($_GET['type'] == 'load_inbox_not_read_num'){
    $data['Num'] = Notice::get_not_read_num($_SESSION['login_id']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'set_already_read'){
    if(empty($_POST['serial'])){
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
    }
    $info = Notice::get_info_by_serial($_POST['serial']);
    if(!empty($info)){
        if($info['ID_TO'] != $_SESSION['login_id']){
            $data['Err'] = 'Permission denied';
            echo json_encode($data);
            exit();
        }
    }

    Notice::set_already_read_by_time($_POST['serial']);
    echo json_encode($data);
    exit();
}else if($_GET['type'] == 'delete'){
    $info = Notice::get_info_by_serial($_POST['serial']);
    if(empty($info)){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    if($info['ID_TO'] != $_SESSION['login_id']){
        $data['Err'] = 'Permission denied';
        echo json_encode($data);
        exit();
    }

    Notice::delete_by_serial($_POST['serial']);
    echo json_encode($data);
    exit();
}

$data['Err'] = 'HTTP GET parameters err';
echo json_encode($data);
exit();
?>
