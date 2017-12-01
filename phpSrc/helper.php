<?php
include_once("globals.php");


function logThis($l)
{
    file_put_contents("log", print_r($l, true));
}
class Returning{
    public $code;
    public $message;
    public function exitNow($c, $m)
    {
        $this->code = $c;
        $this->message = $m;
        if ($this->code == 0)
        {
            // unloadSession();
        }
        else if ($this->code == -1)
        {
            session_regenerate_id();
            unloadSession();
        }
        echo json_encode($this);
        exit;
    }
}
function unloadSession()
{
    if(isset($_SESSION["user"]["username"]))
        // echo "username";
        sodium_memzero($_SESSION["user"]["username"]);
    if(isset($_SESSION["user"]["lastLogin"]))
        // echo "lastLogin";
        unset($_SESSION["user"]["lastLogin"]);
    if(isset($_SESSION["user"]["logintime"]))
        // echo "logintime";
        sodium_memzero($_SESSION["user"]["logintime"]);
    if(isset($_SESSION["user"]["key"]["hashedPW"]))
        // echo "hashedPW";
        sodium_memzero($_SESSION["user"]["key"]["hashedPW"]);
    if(isset($_SESSION["user"]["key"]["salt"]))
        // echo "salt";
        sodium_memzero($_SESSION["user"]["key"]["salt"]);
    if(isset($_SESSION["user"]["key"]["challenge"]))
        // echo "challenge";
        sodium_memzero($_SESSION["user"]["key"]["challenge"]);
    if(isset($_SESSION["user"]["key"]["challengeKey"]))
        // echo "challengeKey";
        sodium_memzero($_SESSION["user"]["key"]["challengeKey"]);
    if(isset($_SESSION["user"]["key"]["nonce"]))
        // echo "nonce";
        sodium_memzero($_SESSION["user"]["key"]["nonce"]);
    if(isset($_SESSION["user"]["key"]["keypair"]))
        // echo "keypair";
        sodium_memzero($_SESSION["user"]["key"]["keypair"]);
    if(isset($_SESSION["user"]["key"]["secret"]))
        // echo "secret";
        sodium_memzero($_SESSION["user"]["key"]["secret"]);
    if(isset($_SESSION["user"]["key"]["public"]))
        // echo "public";
        sodium_memzero($_SESSION["user"]["key"]["public"]);
    unset($_SESSION);
    session_destroy();
}
function openDB()
{
    $db["client"] = new Mongo();
    $db["collection"] = $db["client"]->messageApp;
    $db["userspublic"] = $db["collection"]->userspublic;
    $db["usersprivate"] = $db["collection"]->usersprivate;
    $db["messages"] = $db["collection"]->messages;

    return $db;
}
function closeDB($db)
{
    if (isset($db))
        $db->close();
}

function challengeIsDecrypted($db)
{
    global $challenge;
    $query = array("username" => $_SESSION["user"]["username"]);
    $projection = array('_id' => 0, "key" => 1);

    $key = $db["usersprivate"]->findone($query, $projection)["key"];
    
    $plaintext = sodium_crypto_secretbox_open(hex2bin($key["challenge"]),
       hex2bin($key["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));

    if ($plaintext == $challenge) return true;
    else return false;
}

?>
