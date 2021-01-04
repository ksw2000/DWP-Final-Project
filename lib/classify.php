<?php
//分類看板
class Classify{
    const ADD = 0;
    const MODIFY = 1;

    private static $_error = '';

    public static function get_last_error(){
        return SELF::$_error;
    }

    public static function get_list(){ //set the navigation bar
        $db = new DB;
        $res = $db->query('SELECT ID, NAME_TW, NAME_CN, NAME_EN
                           FROM classify');
        return ($res->num_rows == 0)? null : $res;
    }

    public static function get_info_by_cid($cid){
        $db = new DB;
        $res = $db->query("SELECT ID, NAME_TW, NAME_CN, NAME_EN
                    FROM classify WHERE ID = ?", $cid);
        if($res->num_rows == 0) return null;

        $ret = $res->fetch_assoc();

        $moderator_list = array();
        $res = $db->query("SELECT USER FROM moderator WHERE CLASSIFY = ?", $cid);
        foreach ($res as $user) {
            array_push($moderator_list, $user['USER']);
        }
        $ret['MODERATOR'] = json_encode($moderator_list);
        return $ret;
    }

    public static function transfer_cid_to_cname($cid, $lang = 'zh-tw'){
        if($cid == 'all'){
            switch ($lang) {
                case 'zh-tw':
                    return '全部看板';
                case 'zh-cn':
                    return '全部看板';
                default:
                    return 'all';
            }
        }

        $db = new DB;
        $res = $db->query('SELECT NAME_TW as "zh-tw",
                                  NAME_CN as "zh-cn",
                                  NAME_EN as "en"
                           FROM classify WHERE ID=?', $cid);

        if($res->num_rows == 0) return "";
        return $res->fetch_assoc()[$lang];
    }

    // $moderator is an array
    public static function add($cid, $name ,$moderator, $modify = SELF::ADD){
        $db = new DB;

        if($modify == SELF::ADD){
            $res = $db->query('SELECT ID FROM classify WHERE ID= ?', $cid);
            if($res->num_rows > 0){
                SELF::$_error = 'ID 已經重複';
                return FALSE;
            }

            if($cid == 'all'){
                SELF::$_error = 'ID 無效';
                return FALSE;
            }
        }

        // check name is not repeat
        $res = $db->query('SELECT ID FROM classify
                           WHERE (NAME_TW = ? or NAME_CN = ? or NAME_EN = ?) and
                           ID <> ?',
                           $name['zh-tw'], $name['zh-cn'], $name['en'], $cid);

        if($res->num_rows > 0){
            SELF::$_error = '名稱重複';
            return FALSE;
        }

        // table classify
        if($modify == SELF::ADD){
            $res = $db->query('INSERT INTO classify(ID, NAME_TW,
                               NAME_CN, NAME_EN)
                               VALUES(?, ?, ?, ?, ?)', $cid, $name['zh-tw'],
                               $name['zh-cn'], $name['zh-en']);
        }else{
            $res = $db->query('UPDATE classify SET NAME_TW = ?, NAME_CN = ?,
                               NAME_EN = ? WHERE ID = ?',
                               $name['zh-tw'], $name['zh-cn'],
                               $name['en'], $cid);
        }
        if(!$res) return FALSE;

        // table moderator
        $res = $db->query('DELETE FROM moderator WHERE CLASSIFY = ?', $cid);

        if(!$res) return FALSE;

        foreach($moderator as $user) {
            $res = $db->query('INSERT INTO moderator(USER, CLASSIFY)
                               VALUES (?, ?)', $user, $cid);
        }
        return $res;
    }

    public static function delete_moderator_from_cid($user_id, $cid){
        $db = new DB;
        $db->query('DELETE FROM moderator WHERE USER = ? and CLASSIFY = ?',
                    $user_id, $cid);
    }

    public static function get_article_number_by_cid($cid){
        $db = new DB;
        $res = $db->query('SELECT COUNT(`SERIAL`) as num FROM article
                           WHERE CLASSIFY = ?', $cid);
        return $res->fetch_assoc()['num'];
    }

    public static function delete_empty($cid){
        $db = new DB;

        if(SELF::get_article_number_by_cid($cid) > 0){
            SELF::$_error = 'can only delete empty classify';
            return FALSE;
        }

        $res = $db->query('DELETE FROM classify WHERE ID=? ', $cid);
        if(!$res) return FALSE;
        $res = $db->query('DELETE FROM moderator WHERE CLASSIFY=? ', $cid);

        return $res;
    }

    public static function is_moderator($user_id, $cid){
        $db = new DB;
        $res = $db->query('SELECT COUNT(USER) as num FROM moderator WHERE
                           USER = ? and CLASSIFY = ?', $user_id, $cid);
        return $res->fetch_assoc()['num'] != 0;
    }

    public static function get_cid_managed_by($user_id){
        $db = new DB;
        $res = $db->query('SELECT CLASSIFY FROM moderator WHERE USER = ?', $user_id);
        if($res->num_rows == 0) return null;
        $list = array();
        foreach($res as $v){
            array_push($list, $v['CLASSIFY']);
        }
        return $list;
    }
}
?>
