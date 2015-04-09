<?php 
include_once("globals.php");
include "./helper.php";

class CheckSession{
    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
    }
    public function __destruct()
    {
        closeDB($this->mongo["client"]);
    }
    public function check()
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
}

function main()
{
    $sess = new CheckSession;
    $ret = new Returning;

    if ($sess->check())
        $ret->exitNow(1, "Welcome back " . $_SESSION["user"]["username"]);
    else
        echo json_encode(array("nope" => "nope nope"));
}

main();

?>