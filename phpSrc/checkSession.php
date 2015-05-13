<?php 
include_once("globals.php");
include "./helper.php";

class CheckSession{
    private $mongo;
    
    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
    }
    public function __destruct()
    {
        closeDB($this->mongo["client"]);
    }
    private function check()
    {
        if (isset($_SESSION["user"]["key"]["challengeKey"]))
        {
            if (challengeIsDecrypted($this->mongo))
            {
                session_regenerate_id();
                return 1;
            }
        }
        session_destroy();
        return 0;
    }
    public function main()
    {
        $ret = new Returning;

        if ($this->check())
            $ret->exitNow(1, "Welcome back " . $_SESSION["user"]["username"]);
        else
            echo json_encode(array("nope" => "nope nope"));
    }
}

$sess = new CheckSession;

$sess->main();

?>