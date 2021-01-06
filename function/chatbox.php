<?php
$time_to_chat=false;
$chat_check=false;
(!empty($_POST['chat_id']))?$chat_id=$_POST['chat_id']:$chat_id='';
(!empty($_POST['chat_text']))?$chat_text=$_POST['chat_text']:$chat_text='';
(!empty($_POST['chat_target']))?$chat_target=$_POST['chat_target']:$chat_target='';
(!empty($_POST['chat_time']))?$chat_time=$_POST['chat_time']:$chat_time='';
(!empty($_POST['time_to_chat']))?$time_to_chat=$_POST['time_to_chat']:$time_to_chat='';

(!empty($_GET['chat_check']))?$chat_check=$_GET['chat_check']:$chat_check='';
(!empty($_GET['chat_check_new']))?$chat_check_new=$_GET['chat_check_new']:$chat_check_new='';
(!empty($_GET['find_ID']))?$find_ID=$_GET['find_ID']:$find_ID='';
(!empty($_GET['find_target']))?$find_target=$_GET['find_target']:$find_target='';

$chatbox = new Chatbox;

if($time_to_chat)
{
    echo '<div class="row"><div class="right"><div class="time">'.$chat_time.'</div><div class="text">'.$chatbox->chat_filter(($chatbox->send_msg($chat_id,$chat_text,$chat_time,$chat_target))).'</div></div></div>';
}
else if($chat_check)
{
    echo get_msg($chat_check_new,$find_ID,$find_target);
}


function get_msg($chat_check_new,$ID,$target)
{
    $db=new DB;
    $chatbox=new Chatbox;
    $msg='';
    $result=$db->query("SELECT ID,chat,date,seen FROM chatbox WHERE id=? AND target=? AND seen=? ORDER BY date ASC",$target,$ID,0);
    $rows=$result->num_rows;
    for($i=0;$i<$rows;$i++)
    {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $msg.='<div class="row"><div class="left">';
        $msg.=$chatbox->print_msg($row['chat'],$row['date']);
        $msg.='</div></div>';
        $db->query("UPDATE chatbox SET seen=1 WHERE ID=? AND target=? AND seen=?",$target,$ID,$row['seen']);
    }
    return $msg;
}




