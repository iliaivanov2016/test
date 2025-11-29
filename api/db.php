<?php

class DB extends StdClass{
    
    protected $dbh, $sth;
    
    public function connect(){
        try {  
            $this->sth = false;
            $this->dbh = new PDO(
                "mysql:host=".TEST_DB_HOST.";dbname=".TEST_DB_NAME.";port=".TEST_DB_PORT, 
                TEST_DB_USER, TEST_DB_PASSWORD, [
                PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]);  
        }  
        catch(PDOException $e) {  
            if (defined("TEST_DEBUG_DB")) test_log("DB->connect ERROR: ".$e->getMessage());
            die ("Database error");
        }         
    }
    
    public static function query($sql, $data = false){
        try {  
            if (is_array($data)){
                $GLOBALS["TEST_DB"]->sth = $GLOBALS["TEST_DB"]->dbh->prepare($sql);
                @$GLOBALS["TEST_DB"]->sth->execute($data);
            } else {
                $GLOBALS["TEST_DB"]->dbh->exec($sql);
            }
            if (defined("TEST_DEBUG_DB")) test_log("DB->query OK: rows count = ".self::get_rows_affected()."\n".$sql."\n".print_r($data, true));            
        }  
        catch(PDOException $e) {  
            if (defined("TEST_DEBUG_DB")) test_log("DB->query ERROR: ".$e->getMessage()."\n".$sql."\n".print_r($data, true));            
            die ("Database error");
        }         
    }
    
    public static function select($sql, $data = false, $mode = "assoc"){
        try {
            $fetch_mode = ($mode == "assoc") ? PDO::FETCH_ASSOC : PDO::FETCH_NUM;
            if (is_array($data)){
                $GLOBALS["TEST_DB"]->sth = $GLOBALS["TEST_DB"]->dbh->prepare($sql);
                $GLOBALS["TEST_DB"]->sth->execute($data);
                $res = $GLOBALS["TEST_DB"]->sth->fetchAll($fetch_mode);
            } else {
                $GLOBALS["TEST_DB"]->sth = $GLOBALS["TEST_DB"]->dbh->query($sql);
                $res = $GLOBALS["TEST_DB"]->sth->fetchAll($fetch_mode);
            }
            if (defined("TEST_DEBUG_DB")) test_log("DB->query OK: select count = ".count($res)."\n".$sql."\n".print_r($data, true));            
            return $res;
        }  
        catch(PDOException $e) {  
            if (defined("TEST_DEBUG_DB")) test_log("DB->select ERROR: ".$e->getMessage()."\n".$sql."\n".print_r($data, true));            
            die ("Database error");
        }           
    }
    
    public static function get_rows_affected(){
        return ( $GLOBALS["TEST_DB"]->sth !== false) ? $GLOBALS["TEST_DB"]->sth->rowCount() : 0;
    }
    
    public static function get_last_insert_id(){
        return ( $GLOBALS["TEST_DB"]->sth !== false) ? $GLOBALS["TEST_DB"]->dbh->lastInsertId() : 0;
    }
        
}

$GLOBALS["TEST_DB"] = new DB();
$GLOBALS["TEST_DB"]->connect();

function test_log($msg){
    if (!defined("TEST_DEBUG")) {
        return;
    }
    $log_file = __DIR__ . "/log.txt";
    $cur_retry = 1;
    $dt_now = @date("Ymd_His");
    while ($cur_retry <= 100) {
        @$fh = @fopen($log_file, "a");
        if (!$fh) {
            $cur_retry++;
            continue;
        }
        $s = round(microtime(true) * 1000)  . "\t" . $msg . "\n";

        @fwrite($fh, $s);
        @fclose($fh);
        break;
    }
}

