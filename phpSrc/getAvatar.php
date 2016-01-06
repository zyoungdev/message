<?php 

class GetAvatar{
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function userIsClean()
    {
        $length = mb_strlen($_POST["user"]);
        if (ctype_alnum($_POST["user"]) && $length <= 64)
            return true;
        else
            return false;
    }
    private function getAvatar()
    {
        global $globalMongo;
        $query = array("username" => $_POST["user"]);
        $res = $globalMongo["userspublic"]->findone($query)->avatar;
        $res = classToArray($res);

        echo $res;
    }
    public function main()
    {
        $ret = new Returning;

        // if ($av->userIsClean())
        // {
        //     $ret->exitNow(0, "User is not clean");
        // }
        $this->getAvatar();
    }
}



?>