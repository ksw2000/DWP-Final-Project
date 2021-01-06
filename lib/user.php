<?php
class User{
    const MORE_INFO = TRUE;
    const MORE_INFO_COLUMN = array('BIRTHDAY', 'HOBBY', 'COME_FROM', 'LINK', 'BIO');
    private static $_error = '';
    private static $_online_number = -1;

    public static function hash($pwd, $salt){
        return hash('sha512', $pwd.$salt);
    }

    // check password format
    public static function check_pwd_fmt($pwd){
        // 只能有字母和數字混搭
        if(preg_match("/^[a-zA-Z0-9-_]{8,30}$/", $pwd, $hereArray) == 0){
            SELF::$_error = 'only[a-zA-Z0-9-_]{8,30}';
            return FALSE;
        }

        //要有英文字母
        if(preg_match("/^[0-9-_]{8,30}$/", $pwd, $hereArray) != 0){
            SELF::$_error = 'need-a-z';
            return FALSE;
        }

        //要有數字
        if(preg_match("/^[a-zA-Z-_]{8,30}$/", $pwd, $hereArray) != 0){
            SELF::$_error = 'need-0-9';
            return FALSE;
        }
        SELF::$_error = '';
        return TRUE;
    }

    // check user ID format
    public static function check_id_fmt($user_id){
        //只能有字母和數字混搭
        if(preg_match("/^[a-zA-Z0-9-_]{4,30}$/", $user_id, $hereArray) == 0){
            SELF::$_error = 'only[a-zA-Z0-9-_{4,30}]';
            return FALSE;
        }

        SELF::$_error = '';
        return TRUE;
    }

    public static function last_error(){
        return SELF::$_error;
    }

    // Has id already been existed?
    public static function id_existed($user_id){
        $db = new DB;
        $res = $db->query("SELECT COUNT(ID) as num FROM user WHERE ID = ?", $user_id);
        return $res->fetch_assoc()['num'] > 0;
    }

    // return boolean
    // do not check password format
    // do not check if repeat id
    public static function new($id, $password, $name, $email, $lang, $permission=0){
        $db = new DB;
        $salt = random(32);
        $res = $db->query("INSERT INTO user(ID, SALT, PASSWORD, NAME, EMAIL,
                           LANGUAGE, PERMISSION, READTIME)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        $id, $salt, SELF::hash($password, $salt), htmlentities($name), $email,
        (int)$lang, (int)$permission, time(), "{}");
        return (!$res)? FALSE : TRUE;
    }

    // return boolean
    public static function check_id_pwd($user_id, $password){
        $db  = new DB;
        $res = $db->query("SELECT COUNT(ID) as num, ID, SALT, PASSWORD
                           FROM user WHERE ID = ?", $user_id);
        $row = $res->fetch_assoc();
        // ID not found & for case sensitivity
        if($row['num'] == 0 || $row['ID'] != $user_id) return FALSE;
        $db->close();
        return SELF::hash($password, $row['SALT']) === $row['PASSWORD'];
    }

    public static function change_pwd($user_id, $raw_pwd){
        $db  = new DB;
        $salt = random(32);

        $res = $db->query("SELECT count(ID) as num FROM user WHERE ID = ?", $user_id);
        $row = $res->fetch_assoc();

        if($row['num'] == 0) return FALSE;

        $res = $db->query("UPDATE user SET SALT = ?, PASSWORD = ? WHERE ID = ?",
                           $salt, SELF::hash($raw_pwd, $salt), $user_id);

        return $db->err === "";
    }

    /* 0: 繁中 1: 簡中 2: 英文 */
    public static function change_language($user_id, $lang){
        $db = new DB;
        $res = $db->query("UPDATE user SET LANGUAGE = ? WHERE ID = ?",
        (int)$lang, $user_id);

        return $db->err === "";
    }

    public static function get_permission($user_id){
        $db = new DB;
        $res = $db->query("SELECT COUNT(PERMISSION) as num,  PERMISSION
                    FROM user WHERE `ID` = ?", $user_id);
        $row = $res->fetch_assoc();

        return ($row['num'] > 0)? $row['PERMISSION'] : null;
        // $row->['PERMISSION']: string
    }

    // check if user is manager
    public static function is_manager($user_id){
        return SELF::get_permission($user_id) === '1';
    }

    // 取得公開該用戶的公開資料
    public static function get_user_public_info($user_id, $more_info = !SELF::MORE_INFO){
        $db = new DB;
        $res = $db->query('SELECT ID, NAME, PROFILE, EMAIL, PERMISSION,
                           ONLINE, DIVING, READTIME, LANGUAGE
                           FROM user WHERE ID = ?', $user_id);
        $row = $res->fetch_assoc();

        if($more_info === !SELF::MORE_INFO) return $row;


        $res = $db->query('SELECT BIRTHDAY, HOBBY, COME_FROM, LINK, BIO
                           FROM user_more_info WHERE ID = ?', $user_id);
        $row['MORE_INFO'] = array();
        $row['MORE_INFO_HTMLENTITIES'] = array();
        if($res->num_rows != 0){
            foreach($res->fetch_assoc() as $k => $v){
                $row['MORE_INFO'][$k] = $v;
                $row['MORE_INFO_HTMLENTITIES'][$k] = htmlentities($v);
            }
        }else{
            // give default value
            foreach(SELF::MORE_INFO_COLUMN as $v){
                $row['MORE_INFO'][$v] = '';
                $row['MORE_INFO_HTMLENTITIES'][$v] = '';
            }
        }

        return $row;
    }

    // 設定新的頭貼
    public static function set_new_profile($user_id, $profile, $profile_server_name){
        $db = new DB;
        $db->query("UPDATE user SET PROFILE = ? WHERE ID = ?", $profile, $user_id);
        if($db->err !== "") return FALSE;

        // update file database
        // STEP1: 刪除所有自己以前的照片(透過 LINK)
        // STEP2: 並在 db file 將新上傳的檔案 LINK 設定 profile/
        File::delete_profile_belong_to($user_id);
        $db->query('UPDATE file SET link = "profile/" WHERE SERVER_NAME = ?',
        $profile_server_name);

        return $db->err === "";
    }

    public static function update_name($user_id, $name){
        $db = new DB;
        $db->query("UPDATE user SET NAME = ? WHERE ID = ?",
        htmlentities($name), $user_id);

        return $db->err === "";
    }

    public static function update_more_info($user_id, $more_info){
        $db = new DB;
        $more_info = json_decode($more_info, TRUE);

        if(json_last_error() === JSON_ERROR_NONE){
            $input = array();
            foreach (SELF::MORE_INFO_COLUMN as $v) {
                $input[$v] = $more_info[$v] ?? '';
            }

            $res = $db->query("SELECT COUNT(ID) as num FROM user_more_info
                               WHERE ID = ?", $user_id);
            if($res->fetch_assoc()['num'] > 0){
                $db->query('UPDATE user_more_info SET BIRTHDAY = ?, HOBBY = ?,
                            COME_FROM = ?, LINK = ?, BIO = ? WHERE ID = ?',
                            $input['BIRTHDAY'], $input['HOBBY'],
                            $input['COME_FROM'], $input['LINK'], $input['BIO'],
                            $user_id);
            }else{
                $db->query('INSERT INTO user_more_info(ID, BIRTHDAY, HOBBY,
                            COME_FROM, LINK, BIO) VALUES (?, ?, ?, ?, ?, ?)',
                            $user_id, $input['BIRTHDAY'], $input['HOBBY'],
                            $input['COME_FROM'], $input['LINK'], $input['BIO']);
            }

            return $db->err === "";
        }
    }

    public static function update_online($user_id, $time = null){
        $time = $time ?? time();
        $db = new DB;
        $db->query("UPDATE user SET ONLINE = ? WHERE ID = ?", $time, $user_id);
        return $db->err === "";
    }

    public static function is_online($user_id){
        $ago = time() - 90;    // 1.5min 1.5*60 = 90
        $db = new DB;
        $res = $db->query("SELECT COUNT(ONLINE) as num FROM user
                    WHERE ID = ? and ONLINE > ? and DIVING = 0", $user_id, $ago);
        return $res->fetch_assoc()['num'] > 0;
    }

    public static function get_online_list(){
        $ago = time() - 90;    // 1.5min 1.5*60 = 90
        $db = new DB;
        $res = $db->query("SELECT ID FROM user WHERE ONLINE > ? and DIVING = 0", $ago);

        SELF::$_online_number = $res->num_rows;
        $ret = array();
        if(SELF::$_online_number > 0){
            while($row = $res->fetch_assoc()){
                array_push($ret, $row['ID']);
            }
            return $ret;
        }
        return $ret;
    }

    public static function get_online_number($refresh = FALSE){
        if(SELF::$_online_number != -1 && !$refresh) return SELF::$_online_number;
        SELF::get_online_list();
        return SELF::$_online_number;
    }

    public static function get_profile($user_id){
        $db = new DB;
        $res = $db->query("SELECT COUNT(PROFILE) as num, PROFILE
        FROM user WHERE ID = ?", $user_id);
        $row = $res->fetch_assoc();
        return $row['num'] == 0 ? '' : $row['PROFILE'];
    }

    public static function update_diving_mode($user_id, $diving){
        if(gettype($diving) !== 'boolean') return FALSE;

        $db = new DB;
        $db->query("UPDATE user SET DIVING = ? WHERE ID = ?",
        ($diving)? 1 : 0, $user_id);

        return $db->err === "";
    }

    public static function update_permission($user_id, $permission){
        if(gettype($permission) !== "integer") return FALSE;

        $db = new DB;
        $db->query("UPDATE user SET PERMISSION = ? WHERE ID = ?", $permission, $user_id);
        return $db->err === "";
    }

    public static function update_read_time($user_id){
        $db = new DB;
        $db->query("UPDATE user SET READTIME =? WHERE ID = ?", time(), $user_id);

        return $db->err === "";
    }

    public static function update_email($user_id, $email){
        $db = new DB;
        $db->query('UPDATE user SET EMAIL = ? WHERE ID = ?', $email, $user_id);

        return $db->err === "";
    }

    public static function delete($user_id, $password){
        SELF::$_error = '';
        if(!SELF::check_id_pwd($user_id, $password)){
            SELF::$_error = 'pwd wrong';
            return FALSE;
        }

        $db = new DB;
        $res = $db->query("SELECT `SERIAL` FROM `article` WHERE `USER` = ?", $user_id);

        // DELETE ARTICLE
        $article = new Article;
        foreach($res as $row){
            $article->delete($row['SERIAL']);
        }

        // DELETE INTERACTIVE
        $db->query('DELETE FROM article_interactive WHERE USER = ?', $user_id);
        $db->query('DELETE FROM reply_interactive WHERE USER = ?', $user_id);

        // DELETE ARTICLE_STAR
        $db->query('DELETE FROM article_star WHERE USER = ?', $user_id);

        // REMOVE FROM MANAGER LIST
        $result = Classify::get_cid_managed_by($user_id);
        foreach($result as $cid){
            Classify::delete_moderator_from_cid($user_id, $cid);
        }

        // DELETE REPLY
        $res = $db->query('SELECT `SERIAL` FROM `reply` WHERE USER = ?', $user_id);
        $reply   = new Reply;
        foreach($res as $row){
            $reply->delete($row['SERIAL']);
        }

        // DELETE FILE
        $res = $db->query('SELECT SERVER_NAME, FILE_TYPE
                           FROM file WHERE OWNER = ?', $user_id);
        foreach($res as $row){
            File::delete($row['SERVER_NAME']);
        }

        // DELETE NOTICE
        $res = $db->query('SELECT NOTICE_SERIAL
                           FROM notice WHERE ID_FROM = ? or ID_TO = ?',
                           $user_id, $user_id);
        foreach($res as $row){
            Notice::delete_by_serial($row['NOTICE_SERIAL']);
        }

        // DELETE PUNISHMENT
        $res = $db->query('SELECT `SERIAL` FROM punishment WHERE ID = ?', $user_id);
        foreach($res as $row){
            Punish::delete($row['SERIAL']);
        }

        // DELETE USER
        $db->query('DELETE FROM user WHERE ID = ?', $user_id);

        // RESET SESSION
        session_destroy();
    }

    // return FALSE if not found
    public static function forget_pwd($email_or_id){
        $db = new DB;
        $res = $db->query("SELECT COUNT('ID') as num, EMAIL, ID FROM user
                           WHERE ID = ? and EMAIL = ?", $email_or_id, $email_or_id);

        $row = $res->fetch_assoc();
        if($row['num'] == 0) return FALSE;

        // for case sensitivity
        if(!($row['EMAIL'] == $email_or_id || $row['ID'] == $email_or_id)) return FALSE;

        do{
            $token = random(256);
            $db->query("SELECT COUNT(TOKEN) as num FROM user_forget_pwd
                        WHERE TOKEN = ?", $token);
            $row = $res->fetch_assoc();
        }while($row['num'] != 0);

        // delete expired token
        $db->query("DELETE FROM user_forget_pwd WHERE EXPIRE < ?", time());

        // add token
        $expire = time() + 20 * 60;
        $db->query("INSERT INTO `user_forget_pwd`(`TOKEN`, `ID`, `EXPIRE`)
                    VALUES (?, ?, ?)", $token, $row['ID'], $expire);

        Email::send_forget_pwd($row['EMAIL'], $token);
        $db->close();
        return TRUE;
    }

    public static function get_id_by_user_forget_pwd_token($token){
        $db = new DB;
        $res = $db->query("SELECT COUNT(ID) as num, ID FROM user_forget_pwd
                    WHERE TOKEN = ? and EXPIRE > ", $token, time());
        $row = $res->fetch_assoc();
        return ($row['num'] == 0)? NULL : $row['ID'];
    }

    public static function get_id_and_email_by_user_change_email_token($token){
        $db = new DB;
        $res = $db->query("SELECT NEW_EMAIL, ID FROM user_change_email
                           WHERE TOKEN = ? and EXPIRE > ?", $token, time());

        return ($res->num_rows == 0)? NULL : $res->fetch_assoc();
    }
}

class UserList{
    private $_user_id;
    private $_user_list;
    private $_next;

    public function __construct($user_id){
        $this->_user_id = $user_id;
    }

    public function get_user_list(){
        return $this->_user_list;
    }

    public function has_next(){
        return $this->_next != -1;
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_list_exclude_self($from = null, $num = null){
        if($from === null) $from = 0;
        if($num === null) $num = 25;

        $db = new DB;
        $this->_user_list = $db->query("SELECT ID, NAME, PROFILE,
                    PERMISSION, ONLINE, DIVING
                    FROM user WHERE ID <> ? LIMIT ?, ?",
                    $this->_user_id, $from, $num);

        $num++;
        $res2 = $db->query("SELECT COUNT(ID) as num FROM user WHERE ID <> ? LIMIT ?, ?", $this->_user_id, $from, $num);
        $row2 = $res2->fetch_assoc();

        $this->_next = ($row2['num'] == $num)? $from + $num - 1 : -1;

        return $this->_user_list;
    }

    public function find($keyword){
        $keyword = '%'.htmlentities($keyword).'%';

        $db = new DB;
        $this->_user_list = $db->query("SELECT ID, NAME, PROFILE,
                    PERMISSION, ONLINE, DIVING
                    FROM user WHERE ID LIKE ? OR NAME LIKE ? LIMIT 0, 50",
                    $keyword, $keyword);

        $this->_next = -1;
        return $this->_user_list;
    }
}
?>
