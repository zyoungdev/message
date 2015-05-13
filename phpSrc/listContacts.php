<?php
include_once("globals.php");
include "./helper.php";

class ListContacts{
    private $contacts;
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
        // session_write_close();
        closeDB($this->mongo["client"]);
    }
    private function getContacts()
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

$contacts = new ListContacts;
$contacts->main();

?>