<?php 
include_once("globals.php");
include "./helper.php";

class DeleteContact{
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
        session_write_close();
        closeDB($this->mongo["client"]);
    }
    public function isClean()
    {
        if (ctype_alnum($_POST["username"]))
            return 1;
        else
            return 0;
    }
    public function deleteContact()
    {
        $user = $_POST["username"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$unset' => array("contacts.$user" => ""));

        if ($this->mongo["usersprivate"]->update($query, $projection))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
}

function main()
{
    $del = new DeleteContact;
    $return = new Returning;

    if (!$del->isClean())
    {
        $return->exitNow(0, "The username you provided contains spaces or symbols. Username can only contain letters and numbers.\n");
    }
    if (!$del->deleteContact())
    {
        $return->exitNow(0, "The contact could not be deleted at this time.\n");
    }
    $return->exitNow(1, "Contact removed.");
}

if ($_POST["username"])
{
    main();
}

?>