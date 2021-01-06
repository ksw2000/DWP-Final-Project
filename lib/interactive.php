<?php
// like: 0 dislike: 1
class Interactive{
    const OTHER = 0;
    const LIKE = 1;
    const CANCEL_LIKE = 2;

    private static $_action = SELF::OTHER;

    public static function get_last_action(){
        return SELF::$_action;
    }

    private static function _like_auto($user_id, $serial, $type, $db_name){
        // Did the user interactive with this article
        // i.e. does the database have record?
        // if no INSERT
        // else UPDATE / DELETE (Toggle)
        $db = new DB;
        $res = $db->query('SELECT COUNT(TYPE) as num, TYPE FROM `'.$db_name.'`
                           WHERE USER = ? and `SERIAL` = ?',
                           $user_id, $serial);
        $row = $res->fetch_assoc();

        if($row['num'] == 0){
            if($type == 0) SELF::$_action = SELF::LIKE;
            // add new record
            $db->query('INSERT INTO `'.$db_name.'`(USER, `SERIAL`, TYPE)
                        VALUES (?, ?, ?)', $user_id, $serial, $type);
            return;
        }

        // TOGGLE 0->1 1->0
        if($row['TYPE'] == $type){
            if($type == 0) SELF::$_action = SELF::CANCEL_LIKE;
            // DELETE
            $db->query('DELETE FROM `'.$db_name.'`
                        WHERE USER = ? and `SERIAL` = ?', $user_id, $serial);
            return;
        }

        // UPDATE
        $db->query('UPDATE `'.$db_name.'` SET TYPE = ?
                    WHERE USER = ? and `SERIAL` = ?',
                    $type, $user_id, $serial);
        return;
    }

    // Audo update like-dislike & return the number of likes or dislikes
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
        $db = new db;
        $res = $db->query('SELECT TYPE FROM `'.$db_name.'`
                           WHERE `USER` = ? and `SERIAL` = ?',
                           $user_id, $serial);
        return ($res->num_rows)? $res->fetch_assoc()['TYPE'] : -1;
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
        $db = new DB;
        $res1 = $db->query('SELECT COUNT(TYPE) as num FROM `'.$db_name.'`
                    WHERE TYPE = 0 and `SERIAL` = ?', $serial);
        $res2 = $db->query('SELECT COUNT(TYPE) as num FROM `'.$db_name.'`
                    WHERE TYPE = 1 and `SERIAL` = ?', $serial);
        return array("LIKE_NUM" => $res1->fetch_assoc()['num'],
                     "DISLIKE_NUM" => $res2->fetch_assoc()['num']);
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
        $db = new DB;
        if(SELF::has_user_starred_the_article($user_id, $serial)){
            $res = $db->query('DELETE FROM article_star
                               WHERE USER = ? and `SERIAL` = ?', $user_id, $serial);
            $nums = 0;
        }else{
            $res = $db->query('INSERT INTO article_star(USER, `SERIAL`, `TIME`)
                               VALUES (?, ?, ?)', $user_id, $serial, time());
            $nums = 1;
        }

        return ($res)? $nums : -1;
    }

    public static function has_user_starred_the_article($user_id, $serial){
        $db = new DB;
        $res = $db->query('SELECT COUNT(USER) as num FROM article_star
                           WHERE USER = ? and `SERIAL` = ?',
                           $user_id, $serial);
        return $res->fetch_assoc()['num'] != 0;
    }
}

?>
