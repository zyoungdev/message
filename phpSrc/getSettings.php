<?php 
include_once("globals.php");
include "./helper.php";


class GetSettings{
    private $mongo;
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
    private function getSettings()
    {
        $q = array("username" => $_SESSION["user"]["username"]);
        $p = array('_id' => 0, 'messages' => 1);

        $this->settings = $this->mongo["usersprivate"]->findone($q)["settings"];
        $ret = $this->mongo["usersprivate"]->findone($q, $p);

        $this->settings["user"] = $_SESSION["user"]["username"];

        if (isset($ret["messages"]))
        {
            $this->settings["allowance"] = 0;
            foreach ($ret["messages"] as $user => $userval) {
                foreach ($ret["messages"][$user] as $time => $timeval) {
                    $this->settings["allowance"] += $ret["messages"][$user][$time]["size"];
                }
            }
        }
        else
        {
            $this->settings["allowance"] = 0;
        }


        echo json_encode($this->settings);
    }
    public function main()
    {
        $ret = new Returning;

        $this->getSettings();
    }
}

$get = new GetSettings;
$get->main();

?>