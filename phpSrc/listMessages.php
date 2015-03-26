<?php
include "./helper.php";

class ListMessages{
    public $messages;

    public function __construct()
    {
        session_start();
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        $this->mongo["userspublic"] = $this->mongo["collection"]->userspublic;
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
    public function getMessages()
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
    public function send()
    {
        echo json_encode($this->messages);
    }
}

function main()
{
    $list = new ListMessages;
    $return = new Returning;

    if (!$list->getMessages())
    {
        $return->exitNow(0, "Could not get message\n");
    }
    $list->send();

}
main();

?>