<?php
include_once("globals.php");
include "./helper.php";

class ListMessages{
    private $messages;
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
    private function getMessages()
    {
        $query = array('username' => $_SESSION["user"]["username"]);
        $projection = array(
            "_id" => 0, 
            'messages' => 1,
        );

        if ($result = $this->mongo["usersprivate"]->findone($query, $projection))
        {
            if (isset($result["messages"]))
            {
                $this->messages = $result["messages"];
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return 0;
        }
    }
    private function send()
    {
        echo json_encode($this->messages);
    }
    public function main()
    {
        $return = new Returning;

        if (!$this->getMessages())
        {
            $return->exitNow(0, "There are no messages to retrieve\n");
        }
        $this->send();

    }
}
$list = new ListMessages;
$list->main();

?>