<?php
function text_r($tw, $cn, $en = null){
    if($en == null){
        $en = "英譯：".$tw;
    }

    global $_SESSION;
    global $_COOKIE;

    if(empty($_SESSION['user_info'])){
        if(isset($_COOKIE['lang'])){
            $lang = $_COOKIE['lang'];
        }else{
            $lang = 2;
        }
    }else{
        $lang = $_SESSION['user_info']['LANGUAGE'];
    }
    if($lang == 0) return $tw; //If language sets to 0 return as traditional Chinese
    if($lang == 1) return $cn; //If language sets to 1 return as simplified Chinese
    if($lang == 2) return $en; //If language sets to 2 return as English
}

function text($tw, $cn, $en = null){  //get text_r to return the type of text
    echo text_r($tw, $cn, $en);
}

function random($length, $type="B"){ //creating a random string
    switch ($type) {
        case 'S':
            $characters='0123456789';
            break;
        case 'B':
            $characters='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'ALL':
        default:
            $characters='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@~';
            break;
    }
    $charactersLength=strlen($characters);
    $randomString='';
    for($i=0;$i<$length;$i++){
        $randomString .= $characters[rand(0, $charactersLength-1)];
    }
    return $randomString;
}

function console_log($text){
    $myfile = fopen("./log.txt", "a+");
    fwrite($myfile, $text."\n");
}

function cut_content($raw_str, $num){
    $raw_str = strip_tags($raw_str);
    $sub_str = mb_substr($raw_str, 0, $num, 'UTF-8'); //擷取子字串
    if (strlen($raw_str) > strlen($sub_str)){
        $sub_str .= '...';
    }
    return $sub_str;
}
?>
