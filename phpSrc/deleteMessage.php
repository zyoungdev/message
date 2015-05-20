<?php 

class DeleteMessage{
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function isClean()
    {
        $pattern = "/[0-9]/";

        if (preg_match($pattern, $_POST["timestamp"]) && ctype_alnum($_POST["username"]))
            return 1;
        else
            return 0;
    }
    private function deleteMessage()
    {
        global $globalMongo;
        $message = $_POST["timestamp"];
        $user = $_POST["username"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$unset' => array("messages.$user.$message" => ""));

        $find = array("messages" => 1);
        $id = $globalMongo["usersprivate"]->findone($query, $find)["messages"]["$user"]["$message"]["id"];
        $globalMongo["messages"]->remove(array('id' => $id));

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
            $return->exitNow(0, "Not a timestamp\n");
        }
        if (!$this->deleteMessage())
        {
            $return->exitNow(0, "Could not delete message\n");
        }
        echo "We made it!\n";
    }
}

?>