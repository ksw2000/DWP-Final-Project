<?php
class Notice{
    const COMMENT_TO_YOUR_ARTICLE = 1;
    const NEW_LIKE = 2;
    const REPLY_TO_YOUR_COMMENT = 3;
    const YOUR_ARTICLE_IS_DELETED = 4;
    const YOUR_COMMENT_IS_DELETED = 5;

    const CANCEL = TRUE;
    const DELETE = TRUE;

    public static function add($id_from, $id_to, $type, $link){
        if(gettype($type) !== 'integer') return FALSE;

        $db = new DB;
        $res = $db->query('INSERT INTO notice(ID_FROM, ID_TO, TYPE,
                           LINK, `TIME`) VALUES (?, ?, ?, ?, ?)',
                           $id_from, $id_to, $type, $link, time());
        return (bool)$res;
    }

    public static function delete($id_from, $id_to, $type, $link){
        if(gettype($type) !== 'integer') return FALSE;

        $db = new DB;
        $res = $db->query('DELETE FROM notice WHERE ID_FROM = ?
                           and ID_TO = ? and TYPE = ? and LINK = ?',
                           $id_from, $id_to, $type, $link);
        return (bool)$res;
    }

    public static function reply($id_from, $reply_serial, $delete = !SELF::DELETE){
        $article_serial = Reply::get_reply_by_serial($reply_serial)['ARTICLE_SERIAL'];
        $id_to = Article::get_info_by_serial($article_serial)['USER']['ID'];
        if($id_from == $id_to) return TRUE;
        $link = 'article/'.$article_serial.'?reply='.$reply_serial;
        if($delete === SELF::DELETE){
            return SELF::delete($id_from, $id_to, SELF::COMMENT_TO_YOUR_ARTICLE, $link);
        }

        return SELF::add($id_from, $id_to, SELF::COMMENT_TO_YOUR_ARTICLE, $link);
    }

    public static function like($id_from, $article_serial, $cancel = !SELF::CANCEL){
        $id_to = Article::get_info_by_serial($article_serial)['USER']['ID'];
        if($id_from == $id_to) return TRUE;

        $link = 'article/'.$article_serial;
        if($cancel === SELF::CANCEL){
            return SELF::delete($id_from, $id_to, SELF::NEW_LIKE, $link);
        }
        return SELF::add($id_from, $id_to, SELF::NEW_LIKE, $link);
    }

    public static function reply_to_your_comment($id_from, $reply_at, $reply_to_which_floor, $delete = !SELF::DELETE){
        $reply = new Reply;
        $article_serial = Reply::get_reply_by_serial($reply_at)['ARTICLE_SERIAL'];
        $reply_to = $reply->get_reply_serial_by_floor_and_article($reply_to_which_floor, $article_serial);
        $id_to = Reply::get_reply_by_serial($reply_to)['USER']['ID'];
        if($id_from == $id_to) return TRUE;
        $link = 'article/'.$article_serial.'?reply='.$reply_at.'&reply_to='.$reply_to;
        if($delete === SELF::DELETE){
            return SELF::delete($id_from, $id_to, SELF::REPLY_TO_YOUR_COMMENT, $link);
        }
        return SELF::add($id_from, $id_to, SELF::REPLY_TO_YOUR_COMMENT, $link);
    }

    public static function set_already_read_by_time($notice_serial){
        $db = new DB;
        $res = $db->query('UPDATE notice SET ALREADY_READ = 1
                           WHERE NOTICE_SERIAL = ?', $notice_serial);
        return (bool)$res;
    }

    public static function get_info_by_serial($serial){
        $db = new DB;
        $res = $db->query("SELECT * FROM notice WHERE NOTICE_SERIAL = ?", $serial);

        return ($res->num_rows == 0)? array() : $res->fetch_assoc();
    }

    public static function get_not_read_num($user_id){
        $db = new DB;
        $res = $db->query('SELECT `NOTICE_SERIAL` FROM `notice` WHERE `TIME` > ?',
                           User::get_user_public_info($user_id)['READTIME']);

        return $res->num_rows;
    }

    public static function delete_by_serial($serial){
        $db = new DB;
        $res = $db->query('DELETE FROM notice WHERE NOTICE_SERIAL = ?', $serial);
        return (bool)$res;
    }

    public static function delete_by_article($article_serial){
        $db = new DB;
        $res = $db->query("DELETE FROM `notice` WHERE `LINK` LIKE ?",
               'article/'.$article_serial.'%');
        return (bool)$res;
    }

    private $_notice_list;
    private $_next;

    public function has_next(){
        return ($this->_next != -1);
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_notice_list(){
        return $this->_notice_list;
    }

    public function get_notice_list_by_user_to($user_to, $from=null, $num=null){
        if($from == null) $from = 0;
        if($num == null) $num = 20;

        $db = new DB;
        $res = $db->query('SELECT NOTICE_SERIAL, ID_FROM, ID_TO,
                           TYPE, LINK, `TIME`, ALREADY_READ FROM notice
                           WHERE ID_TO = ? ORDER BY `TIME` DESC LIMIT ?, ?',
                           $user_to, $from, ++$num);

        $this->_notice_list = array();
        $this->_next = -1;

        if($res->num_rows != 0){
            $this->_next = ($res->num_rows == $num)? $from + $num - 1 : -1;
            $end = ($this->_next != -1)? $res->num_rows - 1 : $res->num_rows;
            for($i = 0; $i < $end; $i++){
                $this->_notice_list[$i] = $res->fetch_assoc();
            }
        }
        return $this->_notice_list;
    }
}
?>
