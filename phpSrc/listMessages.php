<?php

class ListMessages{
    public function __construct()
    {
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        $this->mongo["userspublic"] = $this->mongo["collection"]->userspublic;
        $this->mongo["usersprivate"] = $this->mongo["collection"]->usersprivate;
    }
    public function __destruct()
    {
        if ($this->mongo)
        {
            $this->mongo["client"]->close();
        }
    }
    public function grabMessages()
    {
        $query = array('username' => $_SESSION["user"]["username"]);
        $projection = array(
            "_id" => 0, 
            'messages' => 1,
        );


        if ($result = $this->mongo["usersprivate"]->findone($query, $projection))
        {
            print_r(json_encode($result["messages"]));
        }
        else
        {
            echo "Query didn't work\n";
            exit;
        }
    }
}

function main()
{
    session_start();

    $list = new ListMessages;
    $list->grabMessages();

}
main();

?>