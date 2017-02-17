<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

class Info
{
    public $version;
    
    function __construct() {
       
    }
    
    public static function getInfo(){
        $info = new self();
        $query = "SELECT * FROM TINFO;";
        $info_result = db_select($query);
        foreach ($info_result as $result) {
            switch ($result["Type"]) {
                case "VERSION":
                    $info->setVersion($result["Detail"]);
                    break;
                default:
                    break;
            }
        }
        return $info;
    }
    
    function getVersion() {
        return $this->version ? $this->version : "";
    }

    function setVersion($version) {
        $this->version = $version;
    }
}