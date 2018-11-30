<?php 

/* Source: http://www.php-einfach.de/codeschnipsel_6619.php /*

error_reporting(E_ALL); 

/* 
    @ PHP5 Mysql-klasse  
    @ Copyright by Web Communication World (www.wccw.in) 
    @ Diese Klasse darf frei unter diesem Vermerk eingesetzt, verändert und weitergegeben werden 
    @ Weitere Klassen, sind auf www.wccw.in Kostenlos erhältlich 

*/ 








class mysql 
{ 
    private $host     = '';  
    private $user     = ''; 
    private $passwort     = '';  
    private $dbname     = ''; 
    private $last_injection = ''; 
    private $conn_id = null; 
     
     
     
     
    public function __construct() 
    { 
        $this->host     = DB_HOST.":".DB_PORT; 
        $this->user     = DB_USER; 
        $this->passwort = DB_PASS; 
        $this->dbname     = DB_NAME; 
        $this->connect_mysql(); 
        return($this->conn_id); 
    } 
     
     
     
    private function connect_mysql() 
    { 
        $this->conn_id = new mysqli($this->host,$this->user,$this->passwort, $this->dbname); 
         
        if($this->conn_id->connect_errno) 
        { 
            $message  = "Verbindung zur Datenbank nicht m&ouml;glich.<br />\n"; 
            $message .= "Mysql-fehlermeldung: <br />\n"; 
            $message .= $this->conn_id->connect_error; 
             
            trigger_error($message); 
        }  
    } 
     
     
     
    public function query($sqlcode) 
    { 
        $this->last_injection = $this->conn_id->query($sqlcode); 
         
            if($this->last_injection === false) 
            { 
                $message  = "Fehler bei dem Ausf&uuml;hren eines Mysql-codes!<br />\n"; 
                $message .= "Mysql-Code: " . htmlspecialchars($sqlcode, ENT_QUOTES) . "<br />\n"; 
                $message .= "Mysql-fehlermeldung:<br />\n"; 
                $message .= mysqli_error(); 
                trigger_error($message); 
            } 
             
        return($this->last_injection); 
    } 
     
     
     
    public function array_result($sql = NULL, &$row = '') 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $row = mysqli_fetch_array($inc); 
         
        return($row); 
    } 
     
     
     
    public function row_result($sql = NULL, &$row = '') 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $row = mysqli_fetch_row($inc); 
         
        return($row); 
    } 
     
     
     
    public function object_result($sql = NULL, &$row = '') 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $row = mysqli_fetch_object($inc); 
         
        return($row); 
    } 
     
     
     
    public function assoc_result($sql = NULL, &$row = '') 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $row = mysqli_fetch_assoc($inc); 
         
        return($row); 
    } 
     
     
     
    public function num_result($sql = NULL) 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $num = mysqli_num_rows($inc); 
         
        return($num); 
    } 
     
     
     
    public function sql_string($string) 
    { 
        return(mysqli_real_escape_string($string)); 
    } 
     
     
     
    public function free_result($sql = NULL) 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        mysqli_free_result($inc); 
    } 
     
     
     
    public function result($set = 0, $field = 0, $sql = NULL, &$row = '') 
    { 
        $inc = ''; 
        if($sql === NULL) 
        { 
            $inc = $this->last_injection; 
            } else { 
            $inc = $sql; 
        } 
         
        $row = mysqli_result($result, $set, $field); 
         
        return($row); 
    } 
     
     
     
    public function insert_id(&$row = '') 
    { 
     
        $row = mysqli_insert_id(); 
         
        return($row); 
    } 
     
     
     
    public function close_connect() 
    { 
        mysqli_close($this->conn_id); 
    } 
} 


?>
