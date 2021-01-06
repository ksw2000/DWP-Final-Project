<?php
class File{
    const SERVER_DIR = './assets/private/';
    const CLIENT_DIR = '/assets/private/';
    const ALLOW_DIR = ['img', 'music', 'normal', 'profile', 'video'];

    private static $_file_list = array();

    private static function _get_extname($path){
        return pathinfo($path)['extension'];
    }

    public static function upload_form_data_file($file, $type, $user_id, $preset_filename=null){
        $ext_name = str_replace('image/', '', $file['type']);
        $files    = array();
        foreach ($file as $key => $value){
            $files[$key][0] = $value;
        }
        return SELF::upload_files($files, $type, $user_id, $preset_filename, $ext_name);
    }

    public static function server_name_repeat($server_name){
        $ret = FALSE;
        foreach(SELF::ALLOW_DIR as $dir){
            $ret = $ret or file_exists(SELF::SERVER_DIR.$dir.'/'.$server_name);
        }

        if($ret) return TRUE;

        $db = new DB;
        $res = $db->query('SELECT COUNT(SERVER_NAME) as num
                           FROM file WHERE SERVER_NAME = ?', $server_name);
        if($res->fetch_assoc()['num'] > 0) return TRUE;
        return FALSE;
    }

    // return array
    public static function upload_files($files, $type, $user_id='', $preset_filename=null, $preset_ext_name=null){
        $len = count($files['name']);
        for($i = 0; $i < $len; $i++){
            $ext_name = $preset_ext_name ?? SELF::_get_extname($files['name'][$i]);
            if($preset_filename == null){
                do{
                    $new_name = random(8, 'ALL'); // 2.8e14 280兆
                }while(SELF::server_name_repeat($new_name.'.'.$ext_name));
            }else{
                $new_name = $preset_filename;
            }

            // move
            // move_uploaded_file(src, dest)
            $server_path = SELF::SERVER_DIR.$type.'/'.$new_name.'.'.$ext_name;
            move_uploaded_file($files["tmp_name"][$i], $server_path);
            $client_path = SELF::CLIENT_DIR.$type.'/'.$new_name.'.'.$ext_name;

            // write these info to array
            $server_name = $new_name.'.'.$ext_name;
            SELF::$_file_list[$i]['Filename'] = $server_name;
            SELF::$_file_list[$i]['Server_path'] = $server_path;
            SELF::$_file_list[$i]['Client_path'] = $client_path;

            // write to database
            // 注意：database 中的 LINK
            // 1. 用來反向查尋 article 用的 以 article/ 開頭 於文章發佈時更新(這裡不需預設)
            // 2. 用來反向查尋 profile 用的 以 profile/ 為標記

            $db = new DB;
            $db->query('INSERT INTO `file`(`SERVER_NAME`, `FILE_TYPE`, `OWNER`)
                        VALUES (?, ?, ?)', $server_name, $type, $user_id);
        }
        return SELF::$_file_list;
    }

    public static function delete($server_name){
        $db = new DB;
        $res = $db->query('SELECT FILE_TYPE FROM file
                           WHERE SERVER_NAME = ?', $server_name);
        $file_type = $res->fetch_assoc()['FILE_TYPE'];
        unlink(SELF::SERVER_DIR.$file_type.'/'.$server_name);
        $res = $db->query('DELETE FROM file WHERE SERVER_NAME = ?', $server_name);
        return (bool)$res;
    }

    public static function delete_cache(){

    }

    public static function delete_profile_belong_to($user_id){
        $db = new DB;
        $res = $db->query('SELECT SERVER_NAME FROM file WHERE LINK = "profile/"
                           and OWNER = ?', $user_id);

        $success = TRUE;
        while($row = $res->fetch_assoc()){
            $success = $success and SELF::delete($row['SERVER_NAME']);
        }
        return $success;
    }

    // delete the file which connects to some specify article id
    public static function delete_file_by_article_serial($article_serial){
        $db = new DB;
        $res = $db->query('SELECT `SERVER_NAME` FROM `file` WHERE `LINK` = ?',
                           'article/'.$article_serial);
        $success = TRUE;
        while($row = $res->fetch_assoc()){
            $success = $success and SELF::delete($row['SERVER_NAME']);
        }
        return $success;
    }
}

class FileList{
    private $_mysqli;
    private $_next;
    private $_file_list = array();

    public function has_next(){
        return $this->_next != -1;
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_file_list(){
        return $this->_file_list;
    }

    public function get_all_file_list($file_type = null, $from = null, $num = null){
        $db = new DB;
        if($from == null) $from = 0;
        if($num == null) $num = 50;

        $num++;
        if($file_type == null){
            $res = $db->query('SELECT SERVER_NAME, FILE_TYPE, OWNER, LINK, UPLOAD_TIME
                               FROM file ORDER BY UPLOAD_TIME DESC LIMIT ?, ?',
                               $from, $num);
        }else{
            $res = $db->query('SELECT SERVER_NAME, FILE_TYPE, OWNER, LINK, UPLOAD_TIME
                               FROM file WHRE FILE_TYPE = ? ORDER BY UPLOAD_TIME DESC
                               LIMIT ?, ?',
                               $file_type, $from, $num);
        }

        $num_rows = $res->num_rows;
        $this->_next = ($num_rows == $num)? $from + $num -1 : -1;

        $ret = array();

        // 再還有下一篇的情況下最後一篇不要印出來
        if($this->_next != -1) --$num_rows;

        while($num_rows-- > 0){
            $row = $res->fetch_assoc();
            array_push($ret, $row);
        }
        $this->_file_list = $ret;
        return $ret;
    }

    public function find($keyword){
        $db = new DB;
        $db->query('SELECT SERVER_NAME, FILE_TYPE, OWNER, LINK, UPLOAD_TIME
                    FROM file WHERE SERVER_NAME LIKE ? LIMIT 0, 50', '%'.$keyword.'%');
        $this->_next = -1;
        $this->_file_list = $this->_mysqli->query($sql);
        return $this->_file_list;
    }
}
?>
