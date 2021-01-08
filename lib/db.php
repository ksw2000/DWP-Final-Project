<?php
class DB{
    public $conn;
    public $err;
    public $pre_command;
    function __construct(){
        $this->conn = new mysqli('localhost', 'admin', 'Prk8n1iDV2nyXL0z', 'main');
        if($this->conn->connect_error){
            die("Connect fail");
        }
        $this->conn->query('SET NAMES UTF8');
        return $this->conn;
    }

    public function query($command, ...$args){
        $command_list = explode("?", $command);
        $command = $command_list[0];

        if(count($command_list)-1 != count($args)) return "";
        $index = 1;
        foreach($args as $k => $v){
            if(gettype($v) === "string"){
                $command .= "\"".$this->conn->real_escape_string($v)."\"";
            }else{
                $command .= $this->conn->real_escape_string($v);
            }
            $command .= $command_list[$index++];
        }
        $this->pre_command = $command;
        $res = $this->conn->query($command);
        $this->err = $this->conn->error;
        return $res;
    }

    public function close(){
        $this->conn->close();
    }

    public function err(){
        return $this->err !== "";
    }

    public function success(){
        return $this->err === "";
    }
}
?>
