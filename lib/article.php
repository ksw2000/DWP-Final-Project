<?php
class Article{
    private $_serial;
    private $_next;
    private $_article_info_list;

    // 隨機取得空的流水號
    public function _get_new_serial(){
        do{
            $serial = random(8);
        }while(SELF::exist($serial));
        return $serial;
    }

    public static function exist($serial){
        $db = new DB;
        $res = $db->query('SELECT COUNT(`SERIAL`) as num FROM article
                           WHERE `SERIAL` = ?', $serial);

        return $res->fetch_assoc()['num'] > 0;
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
    // $attachment JSON
    public function publish($user, $title, $classify, $content, $attachment, $serial = -1){
        // STEP1: Handle database without attachment
        $add = ($serial == -1)? TRUE : FALSE;
        $db = new DB;

        if($add){
            $serial = $this->_get_new_serial();
        }
        $this->_serial = $serial;   // 標記回去(以方便後續讀取)

        if($add){
            $res = $db->query('INSERT INTO article(USER, SERIAL, PUBLISH,
                               TITLE, CLASSIFY, CONTENT)
                               VALUES(?, ?, ?, ?, ?, ?)', $user, $serial, time(),
                               $title, $classify, $content);
        }else{
            $res = $db->query('UPDATE article SET LAST_MODIFY = ?,
                               TITLE = ?, CLASSIFY = ?, CONTENT = ?
                               WHERE SERIAL = ? and USER = ?',
                               time(), $title, $classify, $content, $serial, $user);
        }

        if(!$res) return FALSE;

        $attachment_object = json_decode($attachment);

        // STEP2: Handle attachment
        // STEP 2-1: update column `link` (i.e. article_serial) in file db by server_name
        // STEP 2-2: delete the file that in file db but not in attachment list
        // STEP 2-3: clean cache

        if(json_last_error() === JSON_ERROR_NONE){
            // 需要特別處理
            // 這種情況可能是原先有很多檔案，最後刪到變沒有
            // 將所有與該文有連結的檔案刪除即可
            if(count($attachment_object->server_name) == 0){
                File::delete_file_by_article_serial($serial);
                return TRUE;
            }

            // STEP 2-1
            $link = 'article/'.$serial;
            foreach($attachment_object->server_name as $key => $server_name){
                $db->query('UPDATE file SET LINK = ?, CLIENT_NAME = ?
                            WHERE SERVER_NAME = ?',
                            $link, $attachment_object->client_name[$key],
                            $server_name);
            }

            // STEP 2-2
            $res = $db->query('SELECT SERVER_NAME
                               FROM file WHERE LINK = ?', $link);
            if($res->num_rows > 0){
                while($row = $res->fetch_assoc()){
                    if(!in_array($row['SERVER_NAME'], $attachment_object->server_name)){
                        // The file has not been in attachment ever
                        // Delete it!
                        File::delete($row['SERVER_NAME']);
                    }
                }
            }
        }

        FILE::delete_cache();
        return TRUE;
    }

    public function delete($serial){
        // CHECK this action will affect just one article
        // If user_id is non-null check owner
        // DELETE article in database article
        // DELETE article_star in article_star
        // DELETE interactive in database article_interactive
        // DELETE reply in database reply
        // use Reply::delete_by_article() which can delete reply_interactive simultaneously
        // DELETE attachments that the article links
        // DELETE notice by link

        // VERY DANGEROUS
        // CHECK SERIAL THAT THE DB QUERY ONLY AFFECT JUST ONE ARTICLE
        $db = new DB;
        $res = $db->query('SELECT `SERIAL` FROM article WHERE `SERIAL` = ?', $serial);

        if($res->num_rows == 0) return TRUE;    // have been deleted

        $res = $db->query('DELETE FROM article WHERE `SERIAL` = ?', $serial);
        if(!$res) return FALSE;

        $res = $db->query('DELETE FROM article_interactive WHERE `SERIAL` = ?', $serial);
        if(!$res) return FALSE;

        $res = $db->query('DELETE FROM article_star WHERE `SERIAL` = ?', $serial);
        if(!$res) return FALSE;

        if(!Reply::delete_by_article($serial)) return FALSE;
        if(!Notice::delete_by_article($serial)) return FALSE;

        File::delete_file_by_article_serial($serial);
        return TRUE;
    }

    public static function get_info_by_serial($serial){
        $db = new DB;
        $res = $db->query('SELECT USER, `SERIAL`, PUBLISH, LAST_MODIFY, TITLE,
                    CLASSIFY, TOP, CONTENT FROM article
                    WHERE `SERIAL` = ?', $serial);

        if($res->num_rows == 0) return null;

        $row          = $res->fetch_assoc();
        $row['USER']  = User::get_user_public_info($row['USER']);
        $row['TITLE'] = htmlentities($row['TITLE']);
        $row['ATTACHMENT'] = Article::get_attachment($serial);
        $row += (new Interactive)->get_article_interactive_num($serial);

        return $row;
    }

    public static function get_attachment($serial){
        $db = new DB;
        $res = $db->query('SELECT CLIENT_NAME, SERVER_NAME, FILE_TYPE as TYPE
                    FROM file WHERE LINK = ?', "article/".$serial);
        $ret = array("client_name" => array(),
                     "server_name" => array(),
                     "path" => array(),
                     "type" => array());
        while($row = $res->fetch_assoc()){
            array_push($ret['client_name'], ($row['CLIENT_NAME'] == "")?
                       $row['SERVER_NAME'] : $row['CLIENT_NAME']);
            array_push($ret['server_name'], $row['SERVER_NAME']);
            array_push($ret['path'],
                       "/assets/private/".$row['TYPE']."/".$row['SERVER_NAME']);
            array_push($ret['type'], $row['TYPE']);
        }
        return json_encode($ret);
    }

    // return article_list
    public function _generate_article_list($res){
        $article_list = array();

        foreach($res as $row){
            $row['USER'] = User::get_user_public_info($row['USER']);
            $inter = new Interactive;
            $row += $inter->get_article_interactive_num($row['SERIAL']);
            $row['REPLY_NUM'] = Reply::get_reply_num_by_article_serial($row['SERIAL']);
            $row['ATTACHMENT'] = SELF::get_attachment($row['SERIAL']);

            array_push($article_list, $row);
        }

        return $article_list;
    }

    // return article_list as well as writing results into $this->_article_info_list
    public function get_article_list_by_userid($user_id, $from = null, $num = null, $q = ''){
        if($from === null) $from = 0;
        if($num === null) $num = 20;
        $step = FALSE;

        $db = new DB;
    sql:
        if(!empty($q)){
            $res = $db->query('SELECT * FROM article WHERE USER = ?
                               and (TITLE LIKE ? or CONTENT LIKE ?)
                               ORDER BY PUBLISH DESC LIMIT ?, ?',
                               $user_id, "%$q%", "%$q%",
                               (int)$from, (int)$num);
        }else{
            $res = $db->query('SELECT * FROM article WHERE USER = ?
                               ORDER BY PUBLISH DESC LIMIT ?, ?',
                               $user_id, (int)$from, (int)$num);
        }

        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list($res);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($res->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    // return article_list as well as writing results into $this->_article_info_list
    public function get_article_list_by_userid_star($user_id, $from = null, $num = null, $q = ''){
        if($from === null) $from = 0;
        if($num === null) $num = 20;
        $step = FALSE;

        $db = new DB;

    sql:
        if(!empty($q)){
            $res = $db->query('SELECT a.* FROM article as a, article_star as b
                               WHERE b.USER = ? and a.SERIAL = b.SERIAL and
                               (a.TITLE LIKE ? or a.CONTENT LIKE ?)
                               ORDER BY b.TIME DESC LIMIT ?, ?',
                               $user_id,
                               "%$q%", "%$q%",
                               (int)$from, (int)$num);
        }else{
            $res = $db->query('SELECT a.* FROM article as a, article_star as b
                               WHERE b.USER = ? and a.SERIAL = b.SERIAL
                               ORDER BY b.TIME DESC LIMIT ?, ?',
                               $user_id, (int)$from, (int)$num);
        }

        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list($res);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($res->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    // 取得所有公開文章(全部看板)
    // return article_list as well as writing results into $this->_article_info_list
    // classify
    public function get_published_article_list($classify_id = null, $from = null, $num = null, $q = ''){
        if($from === null) $from = 0;
        if($num === null) $num = 20;
        $step = FALSE;

        $db = new DB;

    sql:
        if($classify_id != null){
            if(!empty($q)){
                $res = $db->query('SELECT * FROM article
                                   WHERE PUBLISH <> 0 and CLASSIFY = ?
                                   and (TITLE LIKE ? or CONTENT LIKE ?)
                                   ORDER BY TOP DESC, PUBLISH DESC
                                   LIMIT ?, ?',
                                   $classify_id,
                                   "%$q%", "%$q%",
                                   (int)$from, (int)$num);
            }else{
                $res = $db->query('SELECT * FROM article
                                   WHERE PUBLISH <> 0 and CLASSIFY = ?
                                   ORDER BY TOP DESC, PUBLISH DESC
                                   LIMIT ?, ?',
                                   $classify_id, (int)$from, (int)$num);
            }
        }else{
            if(!empty($q)){
                $res = $db->query('SELECT * FROM article
                                   WHERE PUBLISH <> 0
                                   and (TITLE LIKE ? or CONTENT LIKE ?)
                                   ORDER BY PUBLISH DESC LIMIT ?, ?',
                                   "%$q%", "%$q%",
                                   (int)$from, (int)$num);
            }else{
                $res = $db->query('SELECT * FROM article
                                   WHERE PUBLISH <> 0
                                   ORDER BY PUBLISH DESC LIMIT ?, ?',
                                   (int)$from, (int)$num);
            }
        }

        if($step) goto record;
        // generate list
        $this->_article_info_list = $this->_generate_article_list($res);

        // detect _next
        $num++;
        $step = TRUE;
        goto sql;

    record:
        $this->_next = ($res->num_rows == $num)? $from + $num - 1 : -1;
        return $this->_article_info_list;
    }

    public static function reset_top($classify){
        $db = new DB;
        $res = $db->query('UPDATE article SET TOP = 0 WHERE CLASSIFY = ?', $classify);
        return (bool)$res;
    }

    public static function set_top($serial, $classify){
        if(!SELF::reset_top($classify)) return FALSE;
        $db = new $db;
        $res = $db->query('UPDATE article SET TOP = 1 WHERE `SERIAL` = ?', $serial);
        return (bool)$res;
    }
}
?>
