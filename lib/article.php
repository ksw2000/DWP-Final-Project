<?php
class Article{
    private $_serial;
    private $_mysqli;
    private $_next;
    private $_article_info_list;

    public static function conn(){
        return (new Conn_main_db())->get_mysqli();
    }

    public function __construct(){
        $db = new Conn_main_db();
        $this->_mysqli = $db->get_mysqli();
    }

    // 隨機取得空的流水號
    public function _get_new_serial(){
        do{
            $serial = random(8);
        }while(SELF::exist($serial));
        return $serial;
    }

    public static function exist($serial){
        $mysqli = SELF::conn();
        $serial = $mysqli->real_escape_string($serial);
        $sql    = 'SELECT `SERIAL` FROM `article` WHERE `SERIAL` = "'.$serial.'"';
        return $mysqli->query($sql)->num_rows > 0;
    }

    public function get_serial(){
        return $this->_serial;
    }

    public function has_next(){
        return $this->_next != -1;
    }

    public function get_next(){
        return $this->_next;
    }

    public function get_article_info_list(){
        return $this->_article_info_list;
    }

    // 發佈新文章或更新
    // 時間、流水號自動產生
    // $serial = -1 自動新增，非 -1 則為更新
    public function publish($user, $title, $classify, $content, $attachment, $serial = -1){
        $add = ($serial == -1)? TRUE : FALSE;
        $attachment_object = json_decode($attachment);

        $user       = $this->_mysqli->real_escape_string($user);
        $title      = $this->_mysqli->real_escape_string($title);
        $classify   = $this->_mysqli->real_escape_string($classify);
        $content    = $this->_mysqli->real_escape_string($content);
        $attachment = $this->_mysqli->real_escape_string($attachment);
        $publish    = time();

        if($add){
            $serial = $this->_get_new_serial();
        }
        $this->_serial = $serial;   // 標記回去(以方便後續讀取)

        if($add){
            $values = 'VALUES ("'.$user.'","'.$serial.'","'.$publish.'", "'.$title.'", "'.$classify.'", "'.$content.'", "'.$attachment.'")';
            $sql = 'INSERT INTO `article`(`USER`, `SERIAL`, `PUBLISH`, `TITLE`, `CLASSIFY`, `CONTENT`, `ATTACHMENT`)'.$values;
        }else{
            $sql  = 'UPDATE `article` SET `LAST_MODIFY`="'.time().'",`TITLE`="'.$title.'",`CLASSIFY`="'.$classify.'",`CONTENT`="'.$content.'",`ATTACHMENT`="'.$attachment.'"';
            $sql .= ' WHERE `SERIAL` = "'.$serial.'" and `USER` = "'.$user.'"';
        }

        if(!$this->_mysqli->query($sql)){
            return FALSE;
        }

        // deal with attachments
        // STEP1: update column `link` (i.e. article_serial) in file db by server_name
        // STEP2: delete the file that in file db but not in attachment list
        // STEP3: delete the file that in file db and is belong to you but the `link` column is empty
        // STEP1: 標記這篇文用了哪些檔案
        // STEP2: 文章編輯時刪除了某些檔案，但這些資訊沒有更新回伺服器
        // STEP3: 刪除那些上傳但未送出的「無主」檔案，前提是這些檔案必需是你的，以免刪到其他人的

        if(json_last_error() === JSON_ERROR_NONE){
            //需要特別處理
            //這種情況可能是原先有很多檔案，最後刪到變沒有
            //將所有與該文有連結的檔案刪除即可
            if(count($attachment_object->server_name) == 0){
                File::delete_file_by_article_serial($serial);
                return TRUE;
            }
            // STEP1
            $link = $this->_mysqli->real_escape_string('article/'.$serial);
            foreach($attachment_object->server_name as $key => $server_name){
                $server_name = $this->_mysqli->real_escape_string($server_name);
                $update = 'UPDATE `file` SET `LINK` = "'.$link.'" WHERE `SERVER_NAME` = "'.$server_name.'"';
                $this->_mysqli->query($update);
            }

            // STEP2 (尚未測試)
            // TODO NEED TO TEST
            $select_by_link = 'SELECT `SERVER_NAME`, `FILE_TYPE` FROM `file` WHERE `LINK` = "'.$link.'"';
            $result = $this->_mysqli->query($select_by_link);
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    if(!in_array($row['SERVER_NAME'], $attachment_object->server_name)){
                        // The file has not been in attachment already
                        // Delete it!
                        File::delete_by_name_and_type($row['SERVER_NAME'], $row['FILE_TYPE']);
                    }
                }
            }
        }

        //STEP3 (成功)
        FILE::delete_empty_link_but_belong_to($user);
        return TRUE;
    }

    public function delete($serial){
        // CHECK this action will affect just one article
        // If user_id is non-null check owner
        // DELETE article in database article
        // DELETE article_star in article_star
        // DELETE interactive in database article_interactive
        // DELETE reply in database reply
        // use new Reply->delete_by_article() which can delete reply_interactive simultaneously
        // DELETE attachments that the article links
        // DELETE notice by link

        // VERY DANGEROUS
        // CHECK SERIAL THAT THE DB QUERY ONLY AFFECT JUST ONE ARTICLE
        $serial = $this->_mysqli->real_escape_string($serial);
        $select = 'SELECT `USER` FROM `article` WHERE `SERIAL` = "'.$serial.'"';
        $result = $this->_mysqli->query($select);

        if($result->num_rows == 0){
            return TRUE;    // have been deleted
        }

        $delete_article = 'DELETE FROM `article` WHERE `SERIAL` = "'.$serial.'"';
        if(!$this->_mysqli->query($delete_article)){
            return FALSE;   // 錯誤
        }

        $delete_article_interactive = 'DELETE FROM `article_interactive` WHERE `SERIAL` = "'.$serial.'"';
        if(!$this->_mysqli->query($delete_article_interactive)){
            return FALSE;   // 錯誤
        }

        $delete_article_star = 'DELETE FROM `article_star` WHERE `SERIAL` = "'.$serial.'"';
        if(!$this->_mysqli->query($delete_article_star)){
            return FALSE;   // 錯誤
        }

        $reply = new Reply;
        if(!$reply->delete_by_article($serial)){
            return FALSE;
        }

        if(!Notice::delete_by_article($serial)){
            return FALSE;
        }

        File::delete_file_by_article_serial($serial);
        return TRUE;
    }

    public static function get_info_by_serial($serial){
        $mysqli = SELF::conn();
        $serial = $mysqli->real_escape_string($serial);
        $sql    = 'SELECT * FROM `article` WHERE `SERIAL` = "'.$serial.'"';
        $result = $mysqli->query($sql);
        if($result->num_rows == 0) return null;
        $row          = $result->fetch_assoc();
        $row['USER']  = User::get_user_public_info($row['USER']);
        $row['TITLE'] = htmlentities($row['TITLE']);
        $row += (new Interactive)->get_article_interactive_num($serial);

        return $row;
    }

    // return article_list
    public function _generate_article_list_by_db_query($sql){
        $article_list = array();
        $result = $this->_mysqli->query($sql);
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $row['USER'] = User::get_user_public_info($row['USER']);
                $inter = new Interactive;
                $row += $inter->get_article_interactive_num($row['SERIAL']);
                $reply = new Reply;
                $row['REPLY_NUM'] = $reply->get_reply_num_by_article_serial($row['SERIAL']);

                array_push($article_list, $row);
            }
        }

        return $article_list;
    }

    // return article_list as well as writing results into $this->_article_info_list
    public function get_article_list_by_userid($user_id, $from = null, $num = null, $q = ''){
        $user_id = $this->_mysqli->real_escape_string($user_id);
        $from    = ($from == null)? 0 : $this->_mysqli->real_escape_string($from);
        $num     = ($num == null)? 20: $this->_mysqli->real_escape_string($num);
        $q       = $this->_mysqli->real_escape_string($q);
        $step    = FALSE;

    sql:
        $sql     = 'SELECT * FROM `article` WHERE `USER` = "'.$user_id.'"';
        $sql    .= (!empty($q)) ? ' and (`TITLE` LIKE "%'.$q.'%" or `CONTENT` LIKE "%'.$q.'%")' : '';
        $sql    .= " ORDER BY `PUBLISH` DESC LIMIT $from, $num";
        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list_by_db_query($sql);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($this->_mysqli->query($sql)->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    // return article_list as well as writing results into $this->_article_info_list
    public function get_article_list_by_userid_star($user_id, $from = null, $num = null, $q = ''){
        $from    = ($from == null)? 0 : $this->_mysqli->real_escape_string($from);
        $num     = ($num == null)? 20: $this->_mysqli->real_escape_string($num);
        $user_id = $this->_mysqli->real_escape_string($user_id);
        $q       = $this->_mysqli->real_escape_string($q);
        $step    = FALSE;

    sql:
        $sql  = 'SELECT a.* FROM article as a, article_star as b WHERE b.USER = "'.$user_id.'" AND a.SERIAL = b.SERIAL';
        $sql .= (!empty($q)) ? ' and (a.TITLE LIKE "%'.$q.'%" or a.CONTENT LIKE "%'.$q.'%")' : '';
        $sql .= " ORDER BY b.TIME DESC LIMIT $from, $num";
        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list_by_db_query($sql);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($this->_mysqli->query($sql)->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    // get the article that have attachments
    public function get_article_list_by_userid_attachments($user_id, $from = null, $num = null){
        if($from == null) $from = 0;
        if($num == null) $num = 20;
        $user_id = $this->_mysqli->real_escape_string($user_id);
        $sql  = 'SELECT * FROM `article` WHERE `USER` = "'.$user_id.'" and `ATTACHMENT` NOT LIKE "{\"client_name\":\[\]%" ORDER BY `PUBLISH` DESC ';
        $sql .= "LIMIT $from, $num";

        // generate list
        $this->_article_info_list = $this->_generate_article_list_by_db_query($sql);

        // detect _next
        $sql  = 'SELECT * FROM `article` WHERE `USER` = "'.$user_id.'" and `ATTACHMENT` NOT LIKE "{\"client_name\":\[\]%" ORDER BY `PUBLISH` DESC ';
        $num++;
        $sql .= "LIMIT $from, $num";

        // record
        $this->_next = ($this->_mysqli->query($sql)->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    // 取得所有公開文章(全部看板)
    // return article_list as well as writing results into $this->_article_info_list
    // classify
    public function get_published_article_list($classify_id = null, $from = null, $num = null, $q = ''){
        $from  = ($from == null)? 0 : $this->_mysqli->real_escape_string($from);
        $num   = ($num == null)? 20 : $this->_mysqli->real_escape_string($num);
        $q     = $this->_mysqli->real_escape_string($q);
        $sql_q = (!empty($q)) ? ' and (`TITLE` LIKE "%'.$q.'%" or `CONTENT` LIKE "%'.$q.'%")' : '';
        $step  = FALSE;

    sql:
        if($classify_id != null){
            $sql  = 'SELECT * FROM `article` WHERE `PUBLISH` <> 0
                     and `CLASSIFY` = "'.$classify_id.'"'.$sql_q;
            $sql .= " ORDER BY `TOP` DESC,`PUBLISH` DESC LIMIT $from, $num";
        }else{
            $sql  = 'SELECT * FROM `article` WHERE `PUBLISH` <> 0'.$sql_q;
            $sql .= " ORDER BY `PUBLISH` DESC LIMIT $from, $num";
        }

        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list_by_db_query($sql);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($this->_mysqli->query($sql)->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    public static function reset_top($classify){
        // 先讓先前置頂的文都取消
        $mysqli   = SELF::conn();
        $classify = $mysqli->real_escape_string($classify);
        $sql      = 'UPDATE `article` SET `TOP` = 0 WHERE CLASSIFY = "'.$classify.'"';
        if(!$mysqli->query($sql)) return FALSE;
        return TRUE;
    }

    public static function set_top($serial, $classify){
        if(!SELF::reset_top($classify)) return FALSE;
        $mysqli = SELF::conn();
        $serial = $mysqli->real_escape_string($serial);
        $sql    = 'UPDATE `article` SET `TOP` = 1 WHERE `SERIAL` = "'.$serial.'"';
        if(!$mysqli->query($sql)) return FALSE;
        return TRUE;
    }
}
?>
