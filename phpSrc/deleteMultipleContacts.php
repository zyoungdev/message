<?php 
include "./helper.php";

class DeleteMultipleContacts{
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
    public function contactsAreClean()
    {
        foreach ($_POST as $key => $value) {
            if (!ctype_alnum($key))
                return 0;
        }
        return 1;
    }
    public function updateContacts()
    {
        $contacts = json_decode($_POST["contacts"]);

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("contacts" => $contacts));

        if ($this->mongo["usersprivate"]->update($query, $projection))
            return 1;
        else
            return 0;            
    }
}

function main()
{
    $del = new DeleteMultipleContacts;
    $ret = new Returning;

    if (!$del->contactsAreClean())
    {
        $ret->exitNow(0, "The contacts your provided contain spaces or symbols. Usernames can only contain letters and numbers.");
    }
    if (!$del->updateContacts())
    {
        $ret->exitNow(0, "The contacts could not be deleted at this time.");
    }
    $ret->exitNow(1, "Contacts Removed Successfully");
}

if ($_POST["contacts"])
{
    main();
}

?>