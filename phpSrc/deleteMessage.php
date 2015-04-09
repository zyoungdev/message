<?php 
include_once("globals.php");
include "./helper.php";

class DeleteMessage{
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
        session_write_close();
        closeDB($this->mongo["client"]);
    }
    public function isClean()
    {
        $pattern = "/[0-9]/";

        if (preg_match($pattern, $_POST["timestamp"]) && ctype_alnum($_POST["username"]))
            return 1;
        else
            return 0;
    }
    public function deleteMessage()
    {
        $message = $_POST["timestamp"];
        $user = $_POST["username"];

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('$unset' => array("messages.$user.$message" => ""));

        $find = array("messages" => 1);
        $id = $this->mongo["usersprivate"]->findone($query, $find)["messages"]["$user"]["$message"]["id"];
        $this->mongo["messages"]->remove(array('id' => $id));

        if ($this->mongo["usersprivate"]->update($query, $projection))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
}


function main()
{
    $del = new DeleteMessage;
    $return = new Returning;

    if (!$del->isClean())
    {
        $return->exitNow(0, "Not a timestamp\n");
    }
    if (!$del->deleteMessage())
    {
        $return->exitNow(0, "Could not delete message\n");
    }
    echo "We made it!\n";

}

if ($_POST["timestamp"] && $_POST["username"])
{
    main();
}

?>