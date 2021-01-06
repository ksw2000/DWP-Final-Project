<?php
class Chatbox{
    public $query;
    public $msg_list;

    public function send_msg($ID,$text,$time,$target){
        $db=new DB;
        $seen=0;
        $result=$db->query('INSERT INTO chatbox (ID, chat, date,target,seen) VALUES(?,?,?,?,?)', $ID, $text, $time, $target,$seen);
        if(!$result) die("Fatal Error");
        return $text;
    }

    public function print_msg($text,$time){
        $text='<div class="time">'.$time.'</div>
                <div class="text">'.Chatbox::chat_filter($text).'</div>';
        return $text;
    }

    public function get_msg($ID='',$target='')
    {
        $db=new DB;
        $msg='';
        $result=$db->query("SELECT ID,chat,date FROM chatbox WHERE (id=? AND target=?) OR (id=? AND target=?) ORDER BY date ASC",$ID,$target,$target,$ID);
        if($result->num_rows==0)
            $msg.=$this->chat_with_new_user();
        else
        {
            for($i=0;$i<$result->num_rows;$i++)
            {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                if($row['ID']==$ID)
                    $msg.='<div class="row"><div class="right">';
                else
                    $msg.='<div class="row"><div class="left">';

                $msg.=$this->print_msg($row['chat'],$row['date']);
                $msg.='</div></div>';
            }
        }
        return $msg;

    }

    public static function chat_with_new_user()
    {
        return '<div class="new">'.text_r("和對方說聲Hi吧！","和对方说声Hi吧！","Say Hi to New User!").'</div>';
    }

    public static function chat_filter($text)
    {
        if(preg_match_all('/fuck/i',$text))
            return preg_filter('/fuck/i','****',$text);
        else
            return $text;
    }
}
?>
