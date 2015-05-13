<?php 
include_once("globals.php");
include "./helper.php";

class GetContact{
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
        closeDB($this->mongo["client"]);
    }
    private function userIsClean()
    {
        if (ctype_alnum($_POST["user"]))
            return true;
        else
            return false;
    }
    private function userExists()
    {
        $query = array("username" => $_POST["user"]);

        if ($res = $this->mongo["userspublic"]->findone($query))
        {
            $this->user = $res;
            $this->user["displayName"] = $this->mongo["usersprivate"]->findone($query)["settings"]["displayName"];
            return 1;
        }
        else
        {
            return 0;
        }
    }
    private function send()
    {
        echo json_encode($this->user);
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->userIsClean())
        {
            $ret->exitNow(0, "The username is not clean");
        }
        if (!$this->userExists())
        {
            $ret->exitNow(0, "The user does not exist.");
        }
        $this->send();
    }
}
$get = new GetContact;

if (isset($_POST["user"]))
    $get->main();

?>