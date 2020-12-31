<?php
$data = array();
$data['Err'] = '';

if(empty($_POST['serial'])){
    $data['Err'] = 'HTTP POST parameters err';
    echo json_encode($data);
    exit();
}

$result = Interactive::star_article_auto($_SESSION['login_id'], $_POST['serial']);
if($result == -1){
    $data['Err'] = 'Error: Database';
    echo json_encode($data);
    exit();
}

$data['Num'] = $result;
echo json_encode($data);
?>
