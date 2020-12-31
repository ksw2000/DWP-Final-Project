<?php
class Email{
    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

    public static function send_forget_pwd($to, $token){

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
    }
}
?>
