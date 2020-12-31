<?php
//分類看板
class Classify{
    const ADD = 0;
    const MODIFY = 1;

    private static $_error = '';

    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

    public static function get_last_error(){
        return SELF::$_error;
    }

    public static function get_list(){ //set the navigation bar
        $mysqli = SELF::conn();
        $sql    = 'SELECT `ID`, `NAME_TW`, `NAME_CN`, `NAME_EN`, `MODERATOR` FROM `classify`';
        $result = $mysqli->query($sql);
        if($result->num_rows == 0) return null;
        return $result;
    }

    public static function get_info_by_cid($cid){
        $db = new DB;
        $res = $db->query("SELECT ID, NAME_TW, NAME_CN, NAME_EN, MODERATOR
                    FROM classify WHERE ID = ?", $cid);

        if($res->num_rows == 0) return null;
        return $res->fetch_assoc();
    }

    public static function transfer_cid_to_cname($cid, $lang = 'zh-tw'){
        if($cid == 'all'){
            return ($lang == 'zh-tw')? '全部看板' : '全部看板';
        }
        $mysqli = SELF::conn();
        $cid    = $mysqli->real_escape_string($cid);
        $sql    = 'SELECT `NAME_TW`, `NAME_CN` FROM `classify` WHERE `ID`="'.$cid.'"';
        $result = $mysqli->query($sql);
        if($result->num_rows == 0) return "";
        return $result->fetch_assoc()[($lang == 'zh-tw')? 'NAME_TW' : 'NAME_CN'];
    }

    // $moderator is an array
    public static function add($id, $name ,$moderator, $modify = SELF::ADD){
        $mysqli = SELF::conn();
        $id     = $mysqli->real_escape_string($id);
        $name['zh-tw'] = $mysqli->real_escape_string($name['zh-tw']);
        $name['zh-cn'] = $mysqli->real_escape_string($name['zh-cn']);

        if($modify == SELF::ADD){
            $sql = 'SELECT `ID` FROM `classify` WHERE `ID`="'.$id.'"';
            if($mysqli->query($sql)->num_rows > 0){
                SELF::$_error = 'ID 已經重複';
                return FALSE;
            }

            if($id == 'all'){
                SELF::$_error = 'ID 無效';
                return FALSE;
            }
        }

        $sql  = "SELECT `ID` FROM `classify` WHERE (`NAME_TW`=\"{$name['zh-tw']}\" or `NAME_CN`=\"{$name['zh-cn']}\") ";
        $sql .= "and `ID`<>\"{$id}\"";

        if($mysqli->query($sql)->num_rows > 0){
            SELF::$_error = '中文名稱已經重複';
            return FALSE;
        }

        $moderator = json_encode($moderator);
        $moderator = $mysqli->real_escape_string($moderator);

        if($modify == SELF::ADD){
            $sql  = 'INSERT INTO `classify`(`ID`, `NAME_TW`, `NAME_CN`, `MODERATOR`) ';
            $sql .= 'VALUES ("'.$id.'", "'.$name['zh-tw'].'", "'.$name['zh-cn'].'", "'.$moderator.'")';
        }else{
            $sql = 'UPDATE `classify` SET `NAME_TW`="'.$name['zh-tw'].'",`NAME_CN`="'.$name['zh-cn'].'",`MODERATOR`="'.$moderator.'" WHERE `ID`="'.$id.'"';
        }
        if(!$mysqli->query($sql)) return FALSE;
        return TRUE;
    }

    public static function delete_moderator_from_cid($user_id, $cid){
        $mysqli = SELF::conn();
        $cid    = $mysqli->real_escape_string($cid);
        $sql    = 'SELECT `MODERATOR` FROM `classify` WHERE `ID` = "'.$cid.'"';
        $result = $mysqli->query($sql);
        if($result->num_rows == 0) return TRUE;
        $moderator = $result->fetch_assoc()['MODERATOR'];
        $moderator = json_decode($moderator, TRUE);
        if(empty($moderator)) return TRUE;
        foreach($moderator as $k => $v){
            if($v == $user_id){
                unset($moderator[$k]);
                break;
            }
        }
        $moderator = $mysqli->real_escape_string((!empty($moderator))? json_encode($moderator) : "[]");
        $sql = 'UPDATE `classify` SET `MODERATOR`="'.$moderator.'" WHERE ID="'.$cid.'"';
        $mysqli->query($sql);
    }

    public static function get_article_number_by_cid($cid){
        $mysqli = SELF::conn();
        $cid    = $mysqli->real_escape_string($cid);
        $sql    = 'SELECT `SERIAL` FROM `article` WHERE CLASSIFY = "'.$cid.'"';
        return $mysqli->query($sql)->num_rows;
    }

    public static function delete_empty($cid){
        $mysqli = SELF::conn();
        if(SELF::get_article_number_by_cid($cid) > 0){
            SELF::$_error = 'can only delete empty classify';
            return FALSE;
        }
        $cid = $mysqli->real_escape_string($cid);
        $sql = 'DELETE FROM `classify` WHERE `ID`="'.$cid.'"';
        if(!$mysqli->query($sql)) return FALSE;
        return TRUE;
    }

    public static function is_moderator($user_id, $cid){
        $info = SELF::get_info_by_cid($cid);
        if(empty($info['MODERATOR'])) return FALSE;
        $moderator_list = json_decode($info['MODERATOR'], TRUE);
        if(empty($moderator_list)) return FALSE;
        foreach ($moderator_list as $v) {
            if($v == $user_id) return TRUE;
        }
        return FALSE;
    }

    public static function get_cid_managed_by($user_id){
        $mysqli  = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $sql     = 'SELECT `ID` FROM `classify` WHERE `MODERATOR` LIKE "%\"'.$user_id.'\"%"';
        $result  = $mysqli->query($sql);
        if($result->num_rows == 0) return null;
        $list = array();
        foreach($result as $v){
            array_push($list, $v['ID']);
        }
        return $list;
    }
}
?>
