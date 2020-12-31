<?php
class File{
    const SERVER_DIR = './assets/private/';
    const CLIENT_DIR = '/assets/private/';
    private $_mysqli;
    private static $_file_list = array();

    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

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

    // return array
    public static function upload_files($files, $type, $user_id='', $preset_filename=null, $preset_ext_name=null){
        $len = count($files['name']);
        for($i = 0; $i < $len; $i++){
            $ext_name = $preset_ext_name ?? SELF::_get_extname($files['name'][$i]);
            if($preset_filename == null){
                do{
                    $new_name = random(8, 'ALL'); // 2.8e14 280兆
                }while(file_exists(SELF::SERVER_DIR.$type.'/'.$new_name.'.'.$ext_name));
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

            $mysqli      = SELF::conn();
            $server_name = $mysqli->real_escape_string($server_name);
            $user_id     = $mysqli->real_escape_string($user_id);
            $values      = 'VALUES ("'.$server_name.'", "'.$type.'", "'.$user_id.'", "")';
            $sql         = 'INSERT INTO `file`(`SERVER_NAME`, `FILE_TYPE`, `OWNER`, `LINK`) '.$values;
            $mysqli->query($sql);
        }
        return SELF::$_file_list;
    }

    public static function delete_by_name_and_type($server_name, $file_type){
        unlink(SELF::SERVER_DIR.$file_type.'/'.$server_name);
        $mysqli      = SELF::conn();
        $server_name = $mysqli->real_escape_string($server_name);
        $file_type   = $mysqli->real_escape_string($file_type);
        $delete      = 'DELETE FROM `file` WHERE `SERVER_NAME` = "'.$server_name.'" and `FILE_TYPE` = "'.$file_type.'"';
        if(!$mysqli->query($delete)) return FALSE;
        return TRUE;
    }

    private static function _delete_by_sql_query($query){
        $mysqli = SELF::conn();
        $result = $mysqli->query($query);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                SELF::delete_by_name_and_type($row['SERVER_NAME'], $row['FILE_TYPE']);
            }
        }
    }

    public static function delete_empty_link_but_belong_to($user_id){
        $mysqli  = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $query   = 'SELECT `SERVER_NAME`, `FILE_TYPE` FROM `file` WHERE `LINK` = "" and `OWNER` = "'.$user_id.'"';
        SELF::_delete_by_sql_query($query);
    }

    public static function delete_profile_belong_to($user_id){
        $mysqli  = SELF::conn();
        $user_id = $mysqli->real_escape_string($user_id);
        $query = 'SELECT `SERVER_NAME`, `FILE_TYPE` FROM `file` WHERE `LINK` = "profile/" and `OWNER` = "'.$user_id.'"';
        SELF::_delete_by_sql_query($query);
    }

    // delete the file which connects to some specify article id
    public static function delete_file_by_article_serial($article_serial){
        $mysqli = SELF::conn();
        $link   = 'article/'.$article_serial;
        $link   = $mysqli->real_escape_string($link);
        $sql    = 'SELECT `SERVER_NAME`, `FILE_TYPE` FROM `file` WHERE `LINK` = "'.$link.'"';
        SELF::_delete_by_sql_query($sql);
    }
}

class FileList{
    private $_mysqli;
    private $_next;
    private $_file_list = array();

    public function __construct(){
        $db = new Conn_main_db();
        $this->_mysqli = $db->get_mysqli();
    }

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
        if($from == null) $from = 0;
        if($num == null) $num = 50;
        if($file_type == null){
            $sql = 'SELECT `SERVER_NAME`, `FILE_TYPE`, `OWNER`, `LINK`, `UPLOAD_TIME` FROM `file` ORDER BY `UPLOAD_TIME` DESC ';
        }else{
            $sql = 'SELECT `SERVER_NAME`, `FILE_TYPE`, `OWNER`, `LINK`, `UPLOAD_TIME` FROM `file` WHERE `FILE_TYPE` = "'.$file_type.'" ORDER BY `UPLOAD_TIME` DESC ';
        }

        $num++;
        $sql .= "LIMIT $from, $num";
        $result = $this->_mysqli->query($sql);
        $num_rows = $result->num_rows;
        $this->_next = ($num_rows == $num)? $from + $num -1 : -1;

        $ret = array();
        // 再還有下一篇的情況下最後一篇不要印出來
        if($this->_next != -1) --$num_rows;

        while($num_rows-- > 0){
            $row = $result->fetch_assoc();
            array_push($ret, $row);
        }
        $this->_file_list = $ret;
        return $ret;
    }

    public function find($keyword){
        $like = '%'.$keyword.'%';
        $sql  = 'SELECT `SERVER_NAME`, `FILE_TYPE`, `OWNER`, `LINK`, `UPLOAD_TIME` FROM `file` WHERE `SERVER_NAME` LIKE "'.$like.'" LIMIT 0, 50';
        $this->_next = -1;
        $this->_file_list = $this->_mysqli->query($sql);
        return $this->_file_list;
    }
}
?>
