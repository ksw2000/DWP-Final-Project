<?php
$data = array();
$data['Err'] = '';

if(empty($_GET['type'])){
    $data['Err'] = 'HTTP GET parameters err';
    echo json_encode($data);
    exit();
}

switch($_GET['type']){
    case 'img':
    case 'music':
    case 'video':
    case 'normal':
    case 'profile':
        break;
    default:
        $data['Err'] = 'HTTP POST parameters err';
        echo json_encode($data);
        exit();
}

// if $_GET['type'] is profile use user_name aka $_SESSION['login_id'] as filename
if($_GET['type'] == 'profile'){
    $file_list = File::upload_form_data_file($_FILES['profile'], $_GET['type'], $_SESSION['login_id']);
    $data['File'] = $file_list;
    if(empty($file_list)){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    if(!User::set_new_profile($_SESSION['login_id'], $file_list[0]['Client_path'], $file_list[0]['Filename'])){
        $data['Err'] = 'Error: Database';
        echo json_encode($data);
        exit();
    }

    // after uploading
    // update now session user info
    $_SESSION['user_info']['PROFILE'] = $file_list[0]['Client_path'];
    echo json_encode($data);
    exit();
}

$data['File_list'] = File::upload_files($_FILES['files'], $_GET['type'], $_SESSION['login_id']);
echo json_encode($data);
?>
