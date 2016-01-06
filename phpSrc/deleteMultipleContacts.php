<?php 

class DeleteMultipleContacts{
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function contactsAreClean()
    {
        foreach ($_POST as $key => $value) {
            if (!ctype_alnum($key))
                return 0;
        }
        return 1;
    }
    private function updateContacts()
    {
        global $globalMongo;
        $contacts = json_decode($_POST["contacts"]);

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("contacts" => $contacts));

        if ($globalMongo["usersprivate"]->updateOne($query, $projection))
            return 1;
        else
            return 0;            
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->contactsAreClean())
        {
            $ret->exitNow(0, "The contacts your provided contain spaces or symbols. Usernames can only contain letters and numbers.");
        }
        if (!$this->updateContacts())
        {
            $ret->exitNow(0, "The contacts could not be deleted at this time.");
        }
        $ret->exitNow(1, "Contacts Removed Successfully");
    }
}

?>