<?php 
include "./helper.php";

class UpdateSettings{
    public $settings = array();
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
    public function setup()
    {
        if (isset($_POST["mPerPage"]))
        {
            $this->settings["mPerPage"] = (int) $_POST["mPerPage"];
            return 1;
        }
        else
        {
            return 0;
        }
    }
    public function update()
    {
        $query = array("username" => $_SESSION["user"]["username"]);
        $proj = array('$set' => array("settings" => $this->settings));

        logThis($this->settings);

        if ($this->mongo["usersprivate"]->update($query, $proj))
            return 1;
        else
            return 0;
    }
}

function main()
{
    $up = new UpdateSettings;
    $ret = new Returning;

    if (!$up->setup())
    {
        $ret->exitNow(0, "Could not setup settings");
    }
    if (!$up->update())
    {
        $ret->exitNow(0, "Could not update settings");
    }
    $ret->exitNow(1, "Settings updated");
}

main();


?>