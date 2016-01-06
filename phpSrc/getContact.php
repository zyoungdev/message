<?php 

class GetContact{
    private $mongo;
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function userIsClean()
    {
        if (ctype_alnum($_POST["user"]))
            return true;
        else
            return false;
    }
    private function userExists()
    {
        global $globalMongo;
        $query = array("username" => $_POST["user"]);

        if ($res = $globalMongo["userspublic"]->findone($query))
        {
            $this->user = $res;
            $this->user["displayName"] = classToArray($globalMongo["usersprivate"]->findone($query)->settings->displayName);
            return 1;
        }
        else
        {
            return 0;
        }
    }
    private function send()
    {
        echo json_encode($this->user);
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->userIsClean())
        {
            $ret->exitNow(0, "The username is not clean");
        }
        if (!$this->userExists())
        {
            $ret->exitNow(0, "The user does not exist.");
        }
        $this->send();
    }
}

?>