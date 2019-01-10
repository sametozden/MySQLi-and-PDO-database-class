<?php

class sql {

    var $host = "";
    var $user = "";
    var $pass = "";
    var $db = "";
    var $attempt = null;
    var $take;
    var $transaction;
    var $cachestatus=false;
    var $cachetime = 60;
    var $cachefolder = "cachex";
    
    function __construct($host, $user, $pass, $db, $warn = "", $transaction = false) {

        $this->attempt = new mysqli($host, $user, $pass, $db);

        if (mysqli_connect_errno()) {
            exit($warn);
        }

        if ($transaction == true) {
            $this->attempt->query("SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE");
            $this->attempt->query('START TRANSACTION');
            $this->attempt->autocommit(FALSE);
        }

        $this->attempt->query("set names utf8");
        //$this->attempt->query("set sql_mode='';");
    }
    
    function set_cachefolder($folder){
        $this->cachefolder=$folder;
    }
    
    function get_cachefolder(){
        return $this->cachefolder;
    }
    
    function set_cachestatus($swt){
        $this->cachestatus=$swt;
    }
    
    function get_cachestatus(){
        return $this->cachestatus;
    }
    
    function set_cachetime($second){
        $this->cachetime=$second * 60;
    }
    
    function get_cachetime(){
        return $this->cachetime;
    }
    
    function query($qry) {
        $this->attempt->query($qry);
    }

    function select($table, $cells, $query2, $warn = "", $debug = false) {

        if ($debug == true) {
            print "<div style='display:none' class='debug'>select $cells from $table $query2</div>";
        }

        $take = $this->attempt->query("select $cells from $table $query2");

        if (!$take) {
            $this->error_log("select", $this->attempt->error);
            return $returnedvar = array(false, $warn);
        }
        else {
            return $take;
        }
    }

    function read($result, $ifjoin = false) {

        if (!$result) {
            $this->error_log("read", $this->attempt->error);
            return $returnedvar = array(false, $warn);
        }
        else {
            if ($ifjoin == false) {
                $readz = $result->fetch_assoc();
                return $returnedvar = array(true, $readz);
            }
            else {
                $readtemp = $result->fetch_assoc();
                $joininfo = $result->fetch_fields();
                foreach ($joininfo as $val) {
                    $readz[$val->table][$val->name] = $readtemp[$val->name];
                }
                return $returnedvar = array(true, $readz);
            }
        }
    }

    function readall($result, $ifjoin = false) {
        $readallz = array();

        if (!$result) {
            $this->error_log("readall", $this->attempt->error);
            return $returnedvar = array(false, $warn);
        }
        else {

            if ($ifjoin == false) {
                while ($reads = $result->fetch_assoc()) {
                    $readallz[] = $reads;
                }
                return $returnedvar = array(true, $readallz);
            }
            else {
                $joininfo = $result->fetch_fields();
                while ($reads = $result->fetch_assoc()) {

                    foreach ($joininfo as $val) {
                        $readz[$val->table][$val->name] = $reads[$val->name];
                    }

                    $readallz[] = $readz;
                }
                return $returnedvar = array(true, $readallz);
            }
        }
    }

    function insert($table, $val, $query2 = "", $warn = "", $debug = false) {

        $cells = "";
        $values = "";

        foreach ($val as $k => $v) {

            $cells.="$k,";
            if ($v != "null") {
                $values.="'$v',";
            }
            else {
                $values.="null,";
            }
        }


        $cells = substr($cells, 0, -1);
        $values = substr($values, 0, -1);

        if ($debug == true) {
            print "<div style='display:none' class='debug'>INSERT INTO $table ($cells) values ($values) $query2</div>";
        }
        else {
            $insert = $this->attempt->query("INSERT INTO $table ($cells) values ($values) $query2");
            if (!$insert) {
                $this->error_log("insert", $this->attempt->error);
                return $returnedvar = array(false, $warn);
            }
            else {
                return $returnedvar = array(true);
            }
        }
    }

    function update($table, $val, $query2 = "", $warn = "", $debug = false) {

        $cellvalues = "";

        foreach ($val as $k => $v) {

            if ($v != "null") {
                $cellvalues.="$k='$v',";
            }
            else {
                $cellvalues.="$k=null,";
            }
        }


        $cellvalues = substr($cellvalues, 0, -1);

        if ($debug == false) {
            $update = $this->attempt->query("update $table set $cellvalues $query2");
        }
        else {
            print "<div style='display:none' class='debug'>update $table set $cellvalues $query2</div>";
        }

        if (!$update) {
            $this->error_log("update", $this->attempt->error);
            return $returnedvar = array(false, $warn);
        }
        else {
            return $returnedvar = array(true);
        }
    }

    function delete($table, $query2, $warn = "", $debug = false) {

        if ($debug == false) {
            $delete = $this->attempt->query("delete from $table $query2");
        }
        else {
            print "<div style='display:none' class='debug'>delete from $table $query2</div>";
        }

        if (!$delete) {
            $this->error_log("delete", $this->attempt->error);
            return $returnedvar = array(false, $warn);
        }
        else {
            return $returnedvar = array(true);
        }
    }

    function error_log($type, $sql) {
        $d = date("d/m/Y - H:i:s");
        $exp = addslashes($sql);
        $path = addslashes($_SERVER["REQUEST_URI"]);
        $ip = $_SERVER["REMOTE_ADDR"];
        if(strpos($path, "favicon") == false) {
            $this->attempt->query("INSERT INTO sql_error (datex,ip,typex,esql,pathh) values ('$d','$ip','$type','$exp','$path')");
        }
    }

    
    function get_cache($table, $cells, $query2, $warn = "", $debug = false, $single = "read", $join = false, $filenameseperate = "", $timex = false, $forcecache = false) {

        global $cacheopen;
        global $settings;

        if ($debug === true) {
            print "<div style='display:none' class='debug'>select $cells from $table $query2</div>";
        }

        $queryz = "select $cells from $table $query2";
        $md5 = md5($queryz);
        $cachefile = "cachex/db-$filenameseperate-$md5.html";

        if ($forcecache == true) {
            $cacheop = 1;
        }
        else {
            $cacheop = $cacheopen;
        }

        if ($timex == false) {
            $cachet = $settings[1]['cachetime'] * 60; // minute
        }
        else {
            $cachet = $timex * 60; // minute
        }


        if ($cacheop == 1 && file_exists($cachefile) && (time() - $cachet < filemtime($cachefile))) {
            return unserialize(file_get_contents($cachefile));
        }
        else {
            $newquery = $this->attempt->query($queryz);
            if ($single == "read") {
                $newreads = $this->read($newquery, $join);
            }
            else if ($single == "readall") {
                $newreads = $this->readall($newquery, $join);
            }

            $fp = fopen($cachefile, 'w');
            fwrite($fp, serialize($newreads));
            fclose($fp);
            return $newreads;
        }
    }
    
    
    function close() {
        $this->attempt->close();
    }

    function rollback() {
        $this->attempt->rollback();
    }

    function commit() {
        $this->attempt->commit();
    }

    function free($freesql) {
        mysqli_free_result($freesql);
    }

    function last_id() {
        return $this->attempt->insert_id;
    }

    function rows_count($quer) {
        return $quer->num_rows;
    }

    function fetch_rows($quer) {
        return $quer->fetch_row();
    }

}

?>
