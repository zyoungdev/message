<?php 
include "./helper.php";

class GetAvatar{
    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
    }
    public function __destruct()
    {
        closeDB($this->mongo["client"]);

    }
    public function userIsClean()
    {
        $length = mb_strlen($_POST["user"]);
        if (ctype_alnum($_POST["user"]) && $length <= 64)
            return true;
        else
            return false;
    }
    public function getAvatar()
    {
        $query = array("username" => $_POST["user"]);
        $res = $this->mongo["userspublic"]->findone($query)["avatar"];

        echo $res;
    }
}

function main()
{
    $av = new GetAvatar;
    $ret = new Returning;

    // if ($av->userIsClean())
    // {
    //     $ret->exitNow(0, "User is not clean");
    // }
    $av->getAvatar();
}

if ($_POST["user"])
{
    main();
}

?>