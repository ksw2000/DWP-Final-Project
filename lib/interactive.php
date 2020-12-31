<?php
// like: 0 dislike: 1
class Interactive{
    const OTHER = 0;
    const LIKE = 1;
    const CANCEL_LIKE = 2;

    private static $_action = SELF::OTHER;

    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

    public static function get_last_action(){
        return SELF::$_action;
    }

    private static function _like_auto($user_id, $serial, $type, $db_name){
        $mysqli = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $serial  = $mysqli->real_escape_string($serial);
        $type    = $mysqli->real_escape_string($type);

        // Did the user interactive with this article
        // i.e. does the database have record?
        // if no INSERT
        // else UPDATE / DELETE (Toggle)
        $sql    = 'SELECT `TYPE` FROM `'.$db_name.'` WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
        $result = $mysqli->query($sql);
        if($result->num_rows == 0){
            if($type == 0) SELF::$_action = SELF::LIKE;
            // 新增記錄
            $values = 'VALUES ("'.$user_id.'", "'.$serial.'", "'.$type.'")';
            $sql = 'INSERT INTO `'.$db_name.'`(`USER`, `SERIAL`, `TYPE`) '.$values;
        }else{
            $row = $result->fetch_assoc();
            // TOGGLE
            // 比如原本是 0 而 type 也是 0 那就刪除
            // 原本是 0 而 type 是 1 那就更新
            if($row['TYPE'] == $type){
                if($type == 0) SELF::$_action = SELF::CANCEL_LIKE;
                // DELETE
                $sql = 'DELETE FROM `'.$db_name.'` WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
            }else{
                // UPDATE
                $sql = 'UPDATE `'.$db_name.'` SET `TYPE` = "'.$type.'" WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
            }
        }
        $mysqli->query($sql);
    }

    // 更動 推─虛 (自動偵測)
    // 回傳 推─虛 數量
    public static function like_article_auto($user_id, $article_serial, $type=0){
        SELF::_like_auto($user_id, $article_serial, $type, 'article_interactive');
        return SELF::get_article_interactive_num($article_serial);
    }

    public static function like_reply_auto($user_id, $reply_serial, $type=0){
        SELF::_like_auto($user_id, $reply_serial, $type, 'reply_interactive');
        return SELF::get_reply_interactive_num($reply_serial);
    }

    // return -1 if result 0
    private static function _get_user_interactive_with($user_id, $serial, $db_name){
        $mysqli  = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $sql     = 'SELECT `TYPE` FROM `'.$db_name.'` WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
        $result  = $mysqli->query($sql);
        if($result->num_rows != 0){
            return $result->fetch_assoc()['TYPE'];
        }
        return -1;
    }

    // return -1 if this user do not interactive with this article
    public static function get_user_interactive_with_the_article($user_id, $article_serial){
        return SELF::_get_user_interactive_with($user_id, $article_serial, 'article_interactive');
    }

    // return -1 if this user do not interactive with this reply
    public static function get_user_interactive_with_the_reply($user_id, $reply_serial){
        return SELF::_get_user_interactive_with($user_id, $reply_serial, 'reply_interactive');
    }

    private static function _get_interactive_num($serial, $db_name){
        $mysqli = SELF::conn();
        $ret    = array();
        $sql    = 'SELECT `TYPE` FROM `'.$db_name.'` WHERE `TYPE` = 0 and `SERIAL` = "'.$serial.'"';
        $ret['LIKE_NUM'] = $mysqli->query($sql)->num_rows;
        $sql    = 'SELECT `TYPE` FROM `'.$db_name.'` WHERE `TYPE` = 1 and `SERIAL` = "'.$serial.'"';
        $ret['DISLIKE_NUM'] = $mysqli->query($sql)->num_rows;
        return $ret;
    }

    public static function get_article_interactive_num($serial){
        return SELF::_get_interactive_num($serial, 'article_interactive');
    }

    public static function get_reply_interactive_num($serial){
        return SELF::_get_interactive_num($serial, 'reply_interactive');
    }

    // return new condition a.k.a database num_rows (0 or 1)
    // return -1 if database error
    public static function star_article_auto($user_id, $serial){
        $mysqli  = SELF::conn();
        $flag    = SELF::has_user_starred_the_article($user_id, $serial);
        $user_id = $mysqli->real_escape_string($user_id);
        $serial  = $mysqli->real_escape_string($serial);

        if($flag){
            $sql = 'DELETE FROM `article_star` WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
            $nums = 0;
        }else{
            $sql = 'INSERT INTO `article_star`(`USER`, `SERIAL`, `TIME`) VALUES ("'.$user_id.'", "'.$serial.'", '.time().')';
            $nums = 1;
        }

        return ($mysqli->query($sql))? $nums : -1;
    }

    public static function has_user_starred_the_article($user_id, $serial){
        $mysqli  = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $serial  = $mysqli->real_escape_string($serial);
        $sql     = 'SELECT `USER` FROM `article_star` WHERE `USER` = "'.$user_id.'" and `SERIAL` = "'.$serial.'"';
        return $mysqli->query($sql)->num_rows != 0;
    }
}

?>
