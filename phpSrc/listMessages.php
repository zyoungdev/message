<?php
include "./helper.php";

class ListMessages{
    public $messages;

    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
    }
    public function __destruct()
    {
        session_write_close();
        closeDB($this->mongo["client"]);
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