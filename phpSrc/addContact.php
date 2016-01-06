<?php 

class AddContact{
    private $contact;

    public function __construct()
    {
    }
    public function __destruct()
    {
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
        global $globalMongo;
        if ($this->recipient = $globalMongo["userspublic"]->findone(
                array("username" => $_POST["contact"])))
        {
            $this->recipient = classToArray($this->recipient);

            $this->contact["username"] = $this->recipient["username"];
            $this->contact["public"] = $this->recipient["key"]["public"];

            $this->contact["displayName"] = classToArray($globalMongo["usersprivate"]->findone(
                array("username" => $_POST["contact"]))->settings->displayName);
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    private function addContact()
    {
        global $globalMongo;
        $user = $this->contact["username"];
        $details = array("displayName" => $this->contact["displayName"]);
        if (isset($user["contact"]))
            unset($user["contact"]);

        $query = array('username' => $_SESSION["user"]["username"]);
        $update = array('$set' => array("contacts.$user" => $details));

        if ($globalMongo["usersprivate"]->updateOne($query, $update))
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

?>