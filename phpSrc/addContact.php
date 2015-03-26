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
        $return->exitNow(0, "Contact is not clean\n");
    }
    if (!$add->userExists())
    {
        $return->exitNow(0, "User Doesn't Exist\n");
    }
    if (!$add->addContact())
    {
        $return->exitNow(0, "Could not add the contact\n");
    }
}

if ($_POST["contact"])
{
    main();
}

?>