<?php 

class DeleteContact{
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function isClean()
    {
        if (ctype_alnum($_POST["username"]))
            return 1;
        else
            return 0;
    }
    private function deleteContact()
    {
        global $globalMongo;
        $user = $_POST["username"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$unset' => array("contacts.$user" => ""));

        if ($globalMongo["usersprivate"]->update($query, $projection))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    public function main()
    {
        $return = new Returning;

        if (!$this->isClean())
        {
            $return->exitNow(0, "The username you provided contains spaces or symbols. Username can only contain letters and numbers.\n");
        }
        if (!$this->deleteContact())
        {
            $return->exitNow(0, "The contact could not be deleted at this time.\n");
        }
        $return->exitNow(1, "Contact removed.");
    }
}

?>