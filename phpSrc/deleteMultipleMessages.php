<?php 

class DeleteMultipleMessages{
    // private $mongo;
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function messagesAreClean()
    {
        foreach ($_POST as $key => $value) {
            if (!ctype_alnum($key))
                return 0;
        }
        return 1;
    }
    private function updateMessages()
    {
        global $globalMongo;
        $messages = json_decode($_POST["messages"]);

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("messages" => $messages));

        $ids = json_decode($_POST["deleteMessages"]);
        
        if ($globalMongo["messages"]->remove(array("id" => array('$in' => $ids))))
        {
            if ($globalMongo["usersprivate"]->update($query, $projection))
            {
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
    public function main()
    {
        $ret = new Returning;

        if (!$this->messagesAreClean())
        {
            $ret->exitNow(0, "Messages aren't clean");
        }
        if (!$this->updateMessages())
        {
            $ret->exitNow(0, "Could not delete Messages");
        }
        $ret->exitNow(1, "Messages Removed Successfully");
    }
}

?>