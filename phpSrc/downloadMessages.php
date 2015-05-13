<?php 
include_once("globals.php");
include "./helper.php";

class DownloadMessages{
    private $messages;
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
    private function getMessages()
    {
        $query = array('username' => $_SESSION["user"]["username"]);
        $projection = array(
            "_id" => 0, 
            'messages' => 1,
        );

        if ($this->messages = $this->mongo["usersprivate"]->findone($query, $projection)["messages"])
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    private function getIDS()
    {
        // logThis($this->messages);
    }
    private function decryptMessages()
    {
        $mes = array();
        foreach ($this->messages as $usr => $usrval) {
            foreach ($this->messages[$usr] as $time => $timeval) {
                $mes = $this->messages[$usr][$time];

                $cipher = $this->mongo["messages"]->findone(array('id' => $mes["id"]))["ciphertext"];

                $keypair = Sodium::crypto_box_keypair_from_secretkey_and_publickey(
                    hex2bin($_SESSION["user"]["key"]["secret"]), 
                    hex2bin($mes["sender"]["public"]));

                $ciphertext = hex2bin($cipher);
                $nonce = hex2bin($mes["nonce"]);

                $pt = Sodium::crypto_box_open($ciphertext,$nonce,$keypair);

                $this->messages[$usr][$time]["plaintext"] = $pt;

                unset($this->messages[$usr][$time]["nonce"]);
                unset($this->messages[$usr][$time]["size"]);
                unset($this->messages[$usr][$time]["id"]);
                unset($this->messages[$usr][$time]["timestamp"]);
                unset($this->messages[$usr][$time]["sender"]);
            }            
        }
    }
    private function returnData()
    {
        echo json_encode($this->messages);
    }
    public function main()
    {
        $ret = new Returning;

        if (!$this->getMessages())
        {
            $ret->exitNow(0, "Could not get messages");
        }
        // $this->getIDS();
        $this->decryptMessages();
        $this->returnData();
    }
}

$down = new DownloadMessages;

$down->main();
?>