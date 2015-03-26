<?php 
include "./helper.php";

class DeleteMessage{
    public function __construct()
    {
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        // $this->mongo["userspublic"] = $this->mongo["collection"]->userspublic;
        $this->mongo["usersprivate"] = $this->mongo["collection"]->usersprivate;
    }
    public function __destruct()
    {
        if ($this->mongo)
        {
            $this->mongo["client"]->close();
        }
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

        if ($res = $this->mongo["usersprivate"]->update($query, $projection))
            {print_r($res);
                        return 1;}
        else
            {return 0;}
    }
}


function main()
{
    session_start();
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