<?php
include "./helper.php";

class ViewMessage{
    public $message;
    public $plaintext = array();

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
        // $nonce = hex2bin($this->message["nonce"]);
        $nonce = "000000000000000000000000";

        if ($pt = Sodium::crypto_box_open($ciphertext,$nonce,$keypair))
        {
            $this->plaintext["sender"] = $this->message["sender"]["username"];
            $this->plaintext["plaintext"] = $pt;
            $this->plaintext["timestamp"] = $this->message["timestamp"];

            Sodium::sodium_memzero($keypair);
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
    session_start();
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