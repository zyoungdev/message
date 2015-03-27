<?php
include "./helper.php";

class ViewMessage{
    public $message;
    public $plaintext = array();

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
    public function getMessage()
    {
        $timestamp = $_POST["timestamp"];
        $username = $_POST["username"];
        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('_id' => 0, 'messages' => 1);

        if ($result = $this->mongo["usersprivate"]->findone($query, $projection))
        {
            $this->message = $result["messages"]["$username"]["$timestamp"];
        }
        else
        {
            echo "Something went wrong\n";
            exit;
        }
    }
    public function decryptMessage()
    {
        $keypair = Sodium::crypto_box_keypair_from_secretkey_and_publickey(
            hex2bin($_SESSION["user"]["key"]["secret"]), 
            hex2bin($this->message["sender"]["public"]));

        $ciphertext = hex2bin($this->message["ciphertext"]);
        $nonce = hex2bin($this->message["nonce"]);

        if ($pt = Sodium::crypto_box_open($ciphertext,$nonce,$keypair))
        {
            $this->plaintext["sender"] = $this->message["sender"]["username"];
            $this->plaintext["plaintext"] = html_entity_decode($pt);
            $this->plaintext["timestamp"] = $this->message["timestamp"];

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
    public function returnMessage()
    {
        echo json_encode($this->plaintext);

        Sodium::sodium_memzero($this->plaintext["sender"]);
        Sodium::sodium_memzero($this->plaintext["plaintext"]);
        unset($this->plaintext["timestamp"]);
    }
}

function main()
{
    $view = new ViewMessage;
    $return = new Returning;

    $view->getMessage();
    if (!$view->decryptmessage())
    {
        $return->exit(0, "Could not decrypt message\n");
    }
    $view->returnMessage();
}

if ($_POST["username"] && $_POST["timestamp"])
{
    main();
}

?>