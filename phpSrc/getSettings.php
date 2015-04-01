<?php 
include "./helper.php";


class GetSettings{
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
    public function getSettings()
    {
        $query = array("username" => $_SESSION["user"]["username"]);

        if ($res = $this->mongo["usersprivate"]->findone($query))
            echo json_encode($res["settings"]);

    }
}

function main()
{
    $get = new GetSettings;
    $ret = new Returning;

    $get->getSettings();
}

main();

?>