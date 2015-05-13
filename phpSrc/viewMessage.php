<?php
include_once("globals.php");
include "./helper.php";

class ViewMessage{
    private $message;
    private $plaintext = array();
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
        // session_write_close();
        closeDB($this->mongo["client"]);
    }
    private function getMessage()
    {
        $timestamp = $_POST["timestamp"];
        $username = $_POST["username"];
        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('_id' => 0, 'messages' => 1);

        if ($result = $this->mongo["usersprivate"]->findone($query, $projection))
        {
            $this->message = $result["messages"]["$username"]["$timestamp"];

            $id = $result["messages"]["$username"]["$timestamp"]["id"];
            $mQuery = array('id' => $id);
            $this->message["ciphertext"] = $this->mongo["messages"]->findone($mQuery)["ciphertext"];
        }
        else
        {
            echo "Something went wrong\n";
            exit;
        }
    }
    private function decryptMessage()
    {
        $keypair = Sodium::crypto_box_keypair_from_secretkey_and_publickey(
            hex2bin($_SESSION["user"]["key"]["secret"]), 
            hex2bin($this->message["sender"]["public"]));

        $ciphertext = hex2bin($this->message["ciphertext"]);
        $nonce = hex2bin($this->message["nonce"]);


        if ($pt = Sodium::crypto_box_open($ciphertext,$nonce,$keypair))
        {
            $this->plaintext["sender"] = $this->message["sender"]["username"];
            $this->plaintext["displayname"] = $this->message["sender"]["displayName"];
            $this->plaintext["plaintext"] = html_entity_decode($pt);
            $this->plaintext["timestamp"] = $this->message["timestamp"];
            $this->plaintext["size"] = $this->message["size"];

            Sodium::sodium_memzero($keypair);
            Sodium::sodium_memzero($nonce);
            Sodium::sodium_memzero($pt);

            return 1;
        }
        else
        {
            return 0;
        }
    }
    private function returnMessage()
    {
        echo json_encode($this->plaintext);

        Sodium::sodium_memzero($this->plaintext["sender"]);
        Sodium::sodium_memzero($this->plaintext["plaintext"]);
        unset($this->plaintext);
        unset($this->message);
    }
    public function main()
    {
        $ret = new Returning;

        $this->getMessage();
        if (!$this->decryptmessage())
        {
            $ret->exitNow(0, "Could not decrypt message\n");
        }
        $this->returnMessage();
    }
}

$view = new ViewMessage;

if ($_POST["username"] && $_POST["timestamp"])
{
    $view->main();
}

?>