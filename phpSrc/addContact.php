<?php 
include "./helper.php";

class AddContact{
    public $contact;

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
    public function contactIsClean()
    {
        $length = mb_strlen($_POST["contact"]);
        if (ctype_alnum($_POST["contact"]) && $length <= 64)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function userExists()
    {
        if ($this->recipient = $this->mongo["userspublic"]->findone(
                array("username" => $_POST["contact"])))
        {
            $this->contact["username"] = $this->recipient["username"];
            $this->contact["public"] = $this->recipient["key"]["public"];
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    public function addContact()
    {
        $user = $this->contact["username"];
        if (isset($user["contact"]))
            unset($user["contact"]);

        $query = array('username' => $_SESSION["user"]["username"]);
        $update = array('$set' => array("contacts.$user" => array("public" => $this->contact["public"])));

        if ($this->mongo["usersprivate"]->update($query, $update))
            return 1;
        else
            return 0;
    }
    public function send()
    {
        echo $this->contact["public"];
    }
}

function main()
{
    $add = new AddContact;
    $return = new Returning;

    if (!$add->contactIsClean())
    {
        $return->exitNow(0, "The username you provided contains spaces or symbols. Usernames can only contain letters and numbers.\n");
    }
    if (!$add->userExists())
    {
        $return->exitNow(0, "The username you provided does not exist.\n");
    }
    if (!$add->addContact())
    {
        $return->exitNow(0, "The username could not be added at this time.\n");
    }
    $return->exitNow(1, "Contact has been added.");
}

if ($_POST["contact"])
{
    main();
}

?>