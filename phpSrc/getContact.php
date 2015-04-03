<?php 
include "./helper.php";

class GetContact{
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
    public function userIsClean()
    {
        if (ctype_alnum($_POST["user"]))
            return true;
        else
            return false;
    }
    public function userExists()
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
    public function send()
    {
        echo json_encode($this->user);
    }
}

function main()
{
    $get = new GetContact;
    $ret = new Returning;

    if (!$get->userIsClean())
    {
        $ret->exitNow(0, "The username is not clean");
    }
    if (!$get->userExists())
    {
        $ret->exitNow(0, "The user does not exist.");
    }
    $get->send();
}

if (isset($_POST["user"]))
    main();

?>