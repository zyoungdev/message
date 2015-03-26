<?php

class Returning{
    public $code;
    public $message;
    public function exitNow($c, $m)
    {
        $this->code = $c;
        $this->message = $m;
        if ($this->code == 0)
        {
            unloadSession();
            session_regenerate_id();
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
}
function openDB()
{
    $mongo["client"] = new Mongo();
    $mongo["collection"] = $mongo["client"]->messageApp;
    $mongo["userspublic"] = $mongo["collection"]->userspublic;
    $mongo["usersprivate"] = $mongo["collection"]->usersprivate;

    return $mongo;
}
function closeDB($db)
{
    if (isset($db))
        $db->close();
}

?>