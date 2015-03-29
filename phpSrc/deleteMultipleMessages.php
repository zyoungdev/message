<?php 
include "./helper.php";

class DeleteMultipleMessages{
    public $mongo;
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
        closeDB($this->mongo["client"]);
    }
    public function messagesAreClean()
    {
        foreach ($_POST as $key => $value) {
            if (!ctype_alnum($key))
                return 0;
        }
        return 1;
    }
    public function updateMessages()
    {
        $messages = json_decode($_POST["messages"]);

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("messages" => $messages));

        $ids = json_decode($_POST["deleteMessages"]);
        
        logThis($ids);
        if ($this->mongo["messages"]->remove(array("id" => array('$in' => $ids))))
        {
            if ($this->mongo["usersprivate"]->update($query, $projection))
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
}

function main()
{
    $del = new DeleteMultipleMessages;
    $ret = new Returning;

    if (!$del->messagesAreClean())
    {
        $ret->exitNow(0, "Messages aren't clean");
    }
    if (!$del->updateMessages())
    {
        $ret->exitNow(0, "Could not delete Messages");
    }
    $ret->exitNow(1, "Messages Updated Successfully");
}

if ($_POST["messages"] && $_POST["deleteMessages"])
{
    main();
}

?>