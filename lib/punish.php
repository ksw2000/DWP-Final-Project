<?php
class Punish{
    const FOREVER = 9.2e18;

    public static function add($id, $classify, $expired){
        $db  = new DB;
        return $db->query('INSERT INTO `punishment`(`ID`, `CLASSIFY_ID`, `DEADLINE`)
                           VALUES (?, ?, ?)', $id, $classify, $expired);;
    }

    public static function get_by_serial($serial){
        $db = new DB;
        $res = $db->query("SELECT * FROM punishment WHERE `SERIAL` = ?", $serial);
        return ($res->num_rows == 0)? null : $res->fetch_assoc();
    }

    public static function delete($serial){
        $db = new DB;
        return $db->query("DELETE FROM punishment WHERE `SERIAL` = ?", $serial);
    }

    // delete expired records.
    public static function auto_clean(){
        $db = new DB;
        return $db->query("DELETE FROM punishment WHERE DEADLINE < ?", time());
    }

    public static function user_is_banned($user_id, $classify_id){
        $db = new DB;
        $res = $db->query('SELECT DEADLINE FROM punishment WHERE ID = ?
                    and (`CLASSIFY_ID` = "all" or `CLASSIFY_ID` = ?)
                    ORDER BY `DEADLINE` DESC', $user_id, $classify_id);

        return ($res->num_rows == 0)? FALSE : $res->fetch_assoc()['DEADLINE'];
    }
}

class PunishList{
    private $_serial;
    private $_next;
    private $_punishment_list;

    public function get_list(){
        return $this->_punishment_list;
    }

    public function has_next(){
        return $this->_next != -1;
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_punish_list($limit_cid = null, $from = null, $num = null){
        if($from === null) $from = 0;
        if($num === null) $num = 25;

        $where = '';
        if($limit_cid !== null){
            $where = 'WHERE `CLASSIFY_ID` IN ("' .implode('","', $limit_cid).'")';
        }
        $db = new DB;
        $this->_punishment_list = $db->query('SELECT * FROM `punishment` '.$where.'
                   ORDER BY `SERIAL` DESC LIMIT ?, ?', $from, $num);

        $num++;
        $res = $db->query('SELECT COUNT(SERIAL) as num
                           FROM `punishment` '.$where.'
                           ORDER BY `SERIAL` DESC LIMIT ?, ?', $from, $num);

        $this->_next = ($res->fetch_assoc()['num'] == $num)? $from + $num - 1 : -1;
        $db->close();
        return $this->_punishment_list;
    }
}
?>
