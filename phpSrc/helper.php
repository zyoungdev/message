<?php
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
        Sodium::sodium_memzero($_SESSION["user"]["username"]);
    if(isset($_SESSION["user"]["lastLogin"]))
        // echo "lastLogin";
        unset($_SESSION["user"]["lastLogin"]);
    if(isset($_SESSION["user"]["logintime"]))
        // echo "logintime";
        Sodium::sodium_memzero($_SESSION["user"]["logintime"]);
    if(isset($_SESSION["user"]["key"]["hashedPW"]))
        // echo "hashedPW";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["hashedPW"]);
    if(isset($_SESSION["user"]["key"]["salt"]))
        // echo "salt";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["salt"]);
    if(isset($_SESSION["user"]["key"]["challenge"]))
        // echo "challenge";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["challenge"]);
    if(isset($_SESSION["user"]["key"]["challengeKey"]))
        // echo "challengeKey";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["challengeKey"]);
    if(isset($_SESSION["user"]["key"]["nonce"]))
        // echo "nonce";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["nonce"]);
    if(isset($_SESSION["user"]["key"]["keypair"]))
        // echo "keypair";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["keypair"]);
    if(isset($_SESSION["user"]["key"]["secret"]))
        // echo "secret";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["secret"]);
    if(isset($_SESSION["user"]["key"]["public"]))
        // echo "public";
        Sodium::sodium_memzero($_SESSION["user"]["key"]["public"]);
    unset($_SESSION);
    session_destroy();
}
function openDB()
{
    $mongo["client"] = new Mongo();
    $mongo["collection"] = $mongo["client"]->messageApp;
    $mongo["userspublic"] = $mongo["collection"]->userspublic;
    $mongo["usersprivate"] = $mongo["collection"]->usersprivate;
    $mongo["messages"] = $mongo["collection"]->messages;

    return $mongo;
}
function closeDB($db)
{
    if (isset($db))
        $db->close();
}

function challengeIsDecrypted($db)
{
    $ptChallenge = "This is the challenge";

    $query = array("username" => $_SESSION["user"]["username"]);
    $projection = array('_id' => 0, "key" => 1);

    $key = $db["usersprivate"]->findone($query, $projection)["key"];
    
    $plaintext = Sodium::crypto_secretbox_open(hex2bin($key["challenge"]),
       hex2bin($key["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));

    if ($plaintext == $ptChallenge) return true;
    else return false;
}

?>