<?php
class Database{
    
    //Get Heroku ClearDB connection information
private $cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
private $cleardb_server = $cleardb_url["host"];
private $cleardb_username = $cleardb_url["user"];
private $cleardb_password = $cleardb_url["pass"];
private $cleardb_db = substr($cleardb_url["path"],1);
private $active_group = 'default';
private $query_builder = TRUE;
    
    public function dbConnection(){
        
        try{
            $conn = new PDO('mysql:host='.$this->cleardb_server.';dbname='.$this->cleardb_db,$this->cleardb_username,$this->cleardb_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e){
            echo "Connection error ".$e->getMessage(); 
            exit;
        }
          
    }
}
?>