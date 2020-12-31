<?php
class Email{
    const DOMAIN = 'him.had.name';

    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

    public static function send_forget_pwd($to, $token){
        $url     = 'https://'.SELF::DOMAIN.'/login?reset_pwd&token='.$token;
        $subject = '重設密碼';
        $msg     = '點選以下連結以重設密碼：'.$url.'';
        $headers = 'From: admin@'.SELF::DOMAIN;

        mail($to, $subject, $msg, $headers);
    }

    public static function change_email($id, $to){
        $mysqli = SELF::conn();
        //產生亂數
        do{
            $token = random(256);
            $sql = 'SELECT * FROM `user_change_email` WHERE `TOKEN` = "'.$token.'"';
        }while($mysqli->query($sql)->num_rows != 0);

        // 刪除過期 token
        $sql = 'DELETE FROM `user_change_email` WHERE `EXPIRE` < '.time();
        $mysqli->query($sql);

        // 新增
        $expire = time() + 20 * 60;
        $sql = 'INSERT INTO `user_change_email`(`TOKEN`, `ID`, `NEW_EMAIL`, `EXPIRE`) VALUES ("'.$token.'", "'.$id.'", "'.$to.'", '.$expire.')';
        $mysqli->query($sql);

        // 送出電郵
        $url     = 'https://'.SELF::DOMAIN.'/login?change_email&token='.$token;
        $subject = '重設郵件地址';
        $msg     = '點選以下連結以更新新郵件地址：'.$url.'';
        $headers = 'From: admin@'.SELF::DOMAIN;

        mail($to, $subject, $msg, $headers);
    }
}
?>
