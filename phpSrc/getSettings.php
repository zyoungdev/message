<?php 

class GetSettings{
    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    private function getSettings()
    {
        global $globalMongo;
        $q = array("username" => $_SESSION["user"]["username"]);
        $p = array('_id' => 0, 'messages' => 1);

        $this->settings = $globalMongo["usersprivate"]->findone($q)->settings;
        $this->settings = classToArray($this->settings);

        $ret = $globalMongo["usersprivate"]->findone($q, $p);
        $ret = classToArray($ret);

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

?>