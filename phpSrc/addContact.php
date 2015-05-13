<?php 
include_once("globals.php");
include "./helper.php";

class AddContact{
    private $contact;
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
        session_write_close();
        closeDB($this->mongo["client"]);
    }
    private function contactIsClean()
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
    private function userExists()
    {
        if ($this->recipient = $this->mongo["userspublic"]->findone(
                array("username" => $_POST["contact"])))
        {
            $this->contact["username"] = $this->recipient["username"];
            $this->contact["public"] = $this->recipient["key"]["public"];

            $this->contact["displayName"] = $this->mongo["usersprivate"]->findone(
                array("username" => $_POST["contact"]))["settings"]["displayName"];
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    private function addContact()
    {
        $user = $this->contact["username"];
        $details = array("displayName" => $this->contact["displayName"]);
        if (isset($user["contact"]))
            unset($user["contact"]);

        $query = array('username' => $_SESSION["user"]["username"]);
        $update = array('$set' => array("contacts.$user" => $details));

        if ($this->mongo["usersprivate"]->update($query, $update))
            return 1;
        else
            return 0;
    }
    private function send()
    {
        echo $this->contact["public"];
    }
    public function main()
    {
        $return = new Returning;

        if (!$this->contactIsClean())
        {
            $return->exitNow(0, "The username you provided contains spaces or symbols. Usernames can only contain letters and numbers.\n");
        }
        if (!$this->userExists())
        {
            $return->exitNow(0, "The username you provided does not exist.\n");
        }
        if (!$this->addContact())
        {
            $return->exitNow(0, "The username could not be added at this time.\n");
        }
        $return->exitNow(1, "Contact has been added.");
    }
}

$add = new AddContact;

if ($_POST["contact"])
{
    $add->main();
}

?>