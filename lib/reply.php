<?php
class Reply{
    private $_next;
    private $_reply_list;

    private function _get_next_floor($article_serial){
        $db = new DB;
        $res = $db->query("SELECT COUNT(ARTICLE_SERIAL) as num from reply
                    WHERE ARTICLE_SERIAL = ?", $article_serial);

        return 1 + $res->fetch_assoc()['num'];
    }

    public function has_next(){
        return $this->_next != -1;
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_reply_list(){
        return $this->_reply_list;
    }

    // return new serial number
    public function add_reply($article_serial, $user_id, $tag, $content){
        $floor   = $this->_get_next_floor($article_serial);

        $db = new DB;
        $res = $db->query("INSERT INTO `reply`(`ARTICLE_SERIAL`, `USER`,
                    `FLOOR`, `TAG`, `CONTENT`, `TIME`) VALUES (?, ?, ?, ?, ?, ?)",
                $article_serial, $user_id, $floor, $tag, $content, time());

        if($res){
            $res = $db->query("SELECT `SERIAL` FROM reply WHERE ARTICLE_SERIAL = ?
                        and USER = ? and FLOOR = ?", $article_serial, $user_id, $floor);

            return $res->fetch_assoc()['SERIAL'];
        }
        return 0;
    }

    // please check user in another layer
    public function update_reply($serial, $content){
        $db = new DB;
        return $db->query("UPDATE reply SET CONTENT = ?, LAST_MODIFY = ?
                           WHERE SERIAL = ?", $content, time(), $serial);
    }

    // please check user in another layer
    public function delete($serial){
        $db = new DB;
        // STEP1 Del interactive
        $res = $db->query("DELETE FROM reply_interactive WHERE SERIAL = ?", $serial);

        if(!$res) return FALSE;   // error

        // STEP2 empty content
        $res = $db->query('UPDATE reply SET USER = "", TAG = "", CONTENT = "",
                    TIME = "", LIKE_LIST = "" WHERE SERIAL= ?', $serial);

        if(!$res) return FALSE;   // error
        return TRUE;
    }

    public function delete_by_article($article_serial){
        $db = new DB;
        $res = $db->query("SELECT `SERIAL` FROM reply WHERE ARTICLE_SERIAL = ?",
                    $article_serial);

        if($res->num_rows == 0) return TRUE;

        $success = TRUE;
        while($row = $res->fetch_assoc()){
            $res2 = $db->query("DELETE FROM reply_interactive
                               WHERE SERIAL = ?", $row['SERIAL']);
            $success = $success and $res2;
        }

        if(!$success) return FALSE;

        return $db->query('DELETE FROM reply WHERE ARTICLE_SERIAL = ?', $article_serial);
    }

    public function get_reply_list_by_article_serial($article_serial, $from=null, $num=null){
        if($from == null) $from = 0;
        if($num == null) $num = 20;

        $db = new DB;
        $res = $db->query("SELECT * FROM reply WHERE ARTICLE_SERIAL = ?
                    ORDER BY FLOOR ASC LIMIT ?, ?", $article_serial, $from, $num);
        $reply_list = array();

        if($res->num_rows > 0) {
            while($row = $res->fetch_assoc()){
                $row['USER'] = User::get_user_public_info($row['USER']);
                $inter = new Interactive;
                $row += $inter->get_reply_interactive_num($row['SERIAL']);

                array_push($reply_list, $row);
            }
        }

        // detect next
        $num++;
        $res = $db->query("SELECT COUNT(`FLOOR`) as num FROM reply
                           WHERE ARTICLE_SERIAL = ? ORDER BY `FLOOR` ASC LIMIT ?, ?", $article_serial, $from, $num);
        $row = $res->fetch_assoc();

        $this->_next = ($row['num'] == $num)? $from + $num - 1 : -1;
        $this->_reply_list = $reply_list;
        return $this->_reply_list;
    }

    public function get_reply_by_serial($serial){
        $db = new DB;
        $res = $db->query("SELECT * FROM reply WHERE `SERIAL` = ?", $serial);

        if($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $row['USER'] = User::get_user_public_info($row['USER']);
            $inter = new Interactive;
            $row += $inter->get_reply_interactive_num($serial);
            return $row;
        }
        return array();
    }

    public function get_reply_num_by_article_serial($article_serial){
        $db = new DB;
        $res = $db->query("SELECT ARTICLE_SERIAL FROM reply
                           WHERE ARTICLE_SERIAL = ?", $article_serial);
        return $res->num_rows;
    }

    public function get_reply_owner($serial){
        $db = new DB;
        $res = $db->query("SELECT CONUT(USER) as num, USER FROM reply
                           WHERE `SERIAL` = ?", $serial);
        $row = $res->fetch_assoc();
        if($row['num'] == 0) return '';
        return $row['USER'];
    }

    // return -1 if error occurred
    public function get_reply_serial_by_floor_and_article($floor, $article_serial){
        $db = new DB;
        $res = $db->query("SELECT `SERIAL` FROM reply
                    WHERE ARTICLE_SERIAL = ? and FLOOR = ?", $article_serial, $floor);

        if($res->num_rows == 0) return -1;
        return $res->fetch_assoc()['SERIAL'];
    }
}
?>
