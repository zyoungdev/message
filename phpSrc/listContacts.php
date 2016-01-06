<?php

class ListContacts{
    private $contacts;

    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function getContacts()
    {
        global $globalMongo;
        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('_id' => 0, "contacts" => 1);

        if ($this->contacts = $globalMongo["usersprivate"]->findone($query, $projection))
        {
            $this->contacts = classToArray($this->contacts);
            return 1;
        }
        else
        {
            return 0;
        }
    }
    private function send()
    {
        echo json_encode($this->contacts["contacts"]);
    }
    public function main()
    {
        $return = new Returning;

        if (!$this->getContacts())
        {
            $return->exitNow(0, "There are no contacts to be displayed.\n");
        }
        $this->send();
    }
}

?>