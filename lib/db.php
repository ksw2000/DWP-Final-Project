<?php
class Conn_main_db{
    private $_mysqli;
    public function __construct(){
        $_mysqli = new mysqli('localhost', 'admin', 'Prk8n1iDV2nyXL0z', 'main');
        $_mysqli->query('SET NAMES UTF8');
        $this->_mysqli = $_mysqli;
    }

    public function get_mysqli(){
        return $this->_mysqli;
    }
}

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
        foreach($args as $k => $v){
            if(gettype($v) === "string"){
                $command = preg_replace("/\?/",
                "\"".$this->conn->real_escape_string($v)."\"", $command, 1);
            }else{
                $command = preg_replace("/\?/",
                $this->conn->real_escape_string($v), $command, 1);
            }
        }
        $this->pre_command = $command;
        $res = $this->conn->query($command);
        $this->err = $this->conn->error;
        return $res;
    }

    public function close(){
        $this->conn->close();
    }
}
?>
