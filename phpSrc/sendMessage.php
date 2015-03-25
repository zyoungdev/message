<?php 
class Message{
    public $sender = array();
    public $recipient;
    public $nonce;
    public $ciphertext;
    public $timestamp;
}
class SendMessage{
    public $clean = array();
    public $mongo = array();
    public $message = array();
    public $recipient;

    public function __construct()
    {
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        $this->mongo["userspublic"] = $this->mongo["collection"]->userspublic;
        $this->mongo["usersprivate"] = $this->mongo["collection"]->usersprivate;

        $message->sender["username"] = $_SESSION["user"]["username"];
        $message->sender["public"] = $_SESSION["user"]["key"]["public"];

    }
    public function __destruct()
    {
        if ($this->mongo)
        {
            $this->mongo["client"]->close();
        }
    }
    public function recipientIsClean()
    {
        $length = mb_strlen($_POST["recipient"]);
        if (ctype_alnum($_POST["recipient"]) && $length <= 64)
        {
            $this->clean["un"] = $_POST["recipient"];
            return true;
        }
        else
        {
            return false;
        }
    }
    public function userExists()
    {
        if ($this->recipient = $this->mongo["userspublic"]->findone(
                array("username" => $this->clean["un"])))
        {
            $this->message["recipient"]["username"] = $this->recipient["username"];
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    public function escapePlaintext()
    {

        $this->clean["plaintext"] = htmlentities($_POST["plaintext"], ENT_QUOTES);
    }
    public function encryptPlaintext()
    {
        $keypair = Sodium::crypto_box_keypair_from_secretkey_and_publickey(
            $_SESSION["user"]["key"]["secret"], hex2bin($this->recipient["key"]["public"]));

        $this->message["nonce"] = Sodium::randombytes_buf(Sodium::CRYPTO_BOX_NONCEBYTES);
        $this->message["ciphertext"] = Sodium::crypto_box(
            $this->clean["plaintext"],$this->message["nonce"],$keypair);

        $this->message["ciphertext"] = bin2hex($this->message["ciphertext"]);
        $this->message["nonce"] = bin2hex($this->message["nonce"]);

        Sodium::sodium_memzero($keypair);
    }
    public function send()
    {
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');
        $this->message["timestamp"] = $date->getTimestamp();
        $this->message["sender"]["username"] = $_SESSION["user"]["username"];
        $this->message["sender"]["public"] = bin2hex($_SESSION["user"]["key"]["public"]);
        $time = $this->message["timestamp"];
        $sender = $this->message["sender"]["username"];

        $query = array('username' => $this->clean["un"]);
        $update = array('$set' => array("messages.$sender.$time" => $this->message));

        if ($this->mongo["usersprivate"]->update($query, $update))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
}

function sendMessage()
{
    session_start();
    $send = new SendMessage;

    if (!$send->recipientIsClean())
    {
        echo "Recipient is not clean\n";
        exit;
    }
    if (!$send->userExists())
    {
        echo "User doesn't exist\n";
        exit;
    }
    $send->escapePlaintext();
    $send->encryptPlaintext();

    if (!$send->send())
    {
        echo "Could not send message\n";
        exit;
    }

}
if ($_POST["recipient"] && $_POST["plaintext"])
{
    sendMessage();
}

?>