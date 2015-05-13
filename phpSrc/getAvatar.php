<?php 
include_once("globals.php");
include "./helper.php";

class GetAvatar{
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
    private function userIsClean()
    {
        $length = mb_strlen($_POST["user"]);
        if (ctype_alnum($_POST["user"]) && $length <= 64)
            return true;
        else
            return false;
    }
    private function getAvatar()
    {
        $query = array("username" => $_POST["user"]);
        $res = $this->mongo["userspublic"]->findone($query)["avatar"];

        echo $res;
    }
    public function main()
    {
        $ret = new Returning;

        // if ($av->userIsClean())
        // {
        //     $ret->exitNow(0, "User is not clean");
        // }
        $this->getAvatar();
    }
}

$av = new GetAvatar;

if ($_POST["user"])
{
    $av->main();
}

?>