<?php 

class ChangeAvatar{
    public function __construct()
    {
        
    }
    public function __destruct()
    {
        
    }
    private function updateAvatar()
    {
        global $globalMongo;
        $image = $_POST["avatar"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$set' => array("avatar" => $image));

        if ($globalMongo["userspublic"]->update($query, $projection))
            return 1;
        else
            return 0;
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->updateAvatar())
        {
            $ret->exitNow(0, "Could not change avatar");
        }
        $ret->exitNow(1, "Avatar Changed");
    }
}

?>