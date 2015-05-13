<?php 
include_once("globals.php");
include "./helper.php";

class ChangeAvatar{
    private $mongo;
    
    public function __construct()
    {
        session_start();

        $this->mongo = openDB();

        if (!challengeIsDecrypted($this->mongo))
        {
            $ret = new Returning;
            $ret->exitNow(-1, "Challenge could not be decrypted");
        }
    }
    public function __destruct()
    {
        // session_write_close();
        closeDB($this->mongo["client"]);
    }
    private function updateAvatar()
    {
        $image = $_POST["avatar"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("avatar" => $image));

        if ($this->mongo["userspublic"]->update($query, $projection))
            return 1;
        else
            return 0;
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->updateAvatar())
        {
            $ret->exitNow(0, "Could not change avatar");
        }
        $ret->exitNow(1, "Avatar Changed");
    }
}

$change = new ChangeAvatar;

if ($_POST["avatar"])
{
    $change->main();
}
?>