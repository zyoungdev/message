<?php 
include "./helper.php";

class DeleteContact{
    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
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
        $return->exitNow(0, "Username is not clean\n");
    }
    if (!$del->deleteContact())
    {
        $return->exitNow(0, "Could  not delete contact\n");
    }
}

if ($_POST["username"])
{
    main();
}


?>