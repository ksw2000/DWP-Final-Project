<?php
class Email{
    const DOMAIN = 'longer.had.name';

    public static function send_forget_pwd($to, $token){
        $url     = 'https://'.SELF::DOMAIN.'/login?reset_pwd&token='.$token;
        $subject = '重設密碼';
        $msg     = '點選以下連結以重設密碼：'.$url;
        $headers = 'From: admin@'.SELF::DOMAIN;

        mail($to, $subject, $msg, $headers);
    }

    public static function change_email($id, $to){
        $db = new DB;
        // generate random number
        do{
            $token = random(256);
            $res = $db->query('SELECT COUNT(TOKEN) as num FROM user_change_email
                               WHERE TOKEN = ?', $token);
        }while($res->fetch_assoc()['num'] > 0);

        // delete expire token
        $db->query('DELETE FROM user_change_email WHERE EXPIRE < ?', time());

        // add record
        $expire = time() + 20 * 60;
        $db ->query('INSERT INTO user_change_email(TOKEN, ID, NEW_EMAIL, EXPIRE)
                     VALUES (?, ?, ?, ?)', $token, $id, $to, $expire);

        // 送出電郵
        $url     = 'https://'.SELF::DOMAIN.'/login?change_email&token='.$token;
        $subject = '重設郵件地址';
        $msg     = '點選以下連結以更新新郵件地址：'.$url.'';
        $headers = 'From: admin@'.SELF::DOMAIN;

        mail($to, $subject, $msg, $headers);
    }
}
?>
