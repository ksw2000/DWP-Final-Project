<?php
class chatbox
{
    public $connection;
    private $query;
    public $msg_content;

    public function __construct()
    {
        $this->connection=new mysqli('localhost','admin','Prk8n1iDV2nyXL0z','main');
        if($this->connection->connect_error) die("Fatal error");
        $this->$query="SET NAMES UTF8";
        $this->connection->query($query);
        return $this->connection;
    }

    public function get_mysqli()
    {
        return $this->connection();
    }

    public function send_msg($ID,$name,$text,$time)
    {
        $statement=$connection->prepare("INSERT INTO chatbox (ID, name, chat, time) VALUES(?,?,?,?)");
        $statement->bind_param("ssss",$ID,$name,$text,$time);
        $statement->execute();
        $statement->close();
    }

    public function get_msg($ID,$name,$text,$time)
    {
        $this->msg_content=array();
        $this->query="SELECT name,chat,time FROM chat WHERE id='$ID'";
        $result=$connection->query($this->query);
        if(!$result)
            chat_with_new_user();
        else
        {
            $row=$fetch_array(MYSQLI_NUM);
            for($i=0;$i<$result->num_rows;$i++)
            {
                $this->msg_content=$row['$i'];
            }
        }
    }

    private function check_time($time)
    {
        $starttime=strtotime($time);
        $difftime=(($starttime-time())/60/60/24);
        if($difftime>0)
            return true;
        else
            return false;
    }

    public function chat_with_new_user()
    {
        echo <<<_END
        <div>Say Hi to new user!</div>
        _END;
    }

    public function show_msg()
    {
        echo <<<_END

        _END;
    }
}

?>