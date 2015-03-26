<?php
include "./helper.php";

class ListContacts{
    public $contacts;

    public function __construct()
    {
        session_start();
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        // $this->mongo["userspublic"] = $this->mongo["collection"]->userspublic;
        $this->mongo["usersprivate"] = $this->mongo["collection"]->usersprivate;
    }
    public function __destruct()
    {
        session_write_close();
        if ($this->mongo)
        {
            $this->mongo["client"]->close();
        }
    }
    public function getContacts()
    {
        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('_id' => 0, "contacts" => 1);

        if ($this->contacts = $this->mongo["usersprivate"]->findone($query, $projection))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    public function send()
    {
        echo json_encode($this->contacts["contacts"]);
    }
}


function main()
{
    $contacts = new ListContacts;
    $return = new Returning;

    if (!$contacts->getContacts())
    {
        $return->exitNow(0, "Could not get contacts\n");
    }
    $contacts->send();
}

main();

?>