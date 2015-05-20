<?php

class ListMessages{
    private $messages;

    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function getMessages()
    {
        global $globalMongo;
        $query = array('username' => $_SESSION["user"]["username"]);
        $projection = array(
            "_id" => 0, 
            'messages' => 1,
        );

        if ($result = $globalMongo["usersprivate"]->findone($query, $projection))
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

?>