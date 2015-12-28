<?php 

class Verify{
    public function __construct(){
    }
    public function __destruct(){
    }
    public function challengeIsDecrypted()
    {
        global $challenge;
        global $globalMongo;
        
        if (!isset($_SESSION["user"]["username"])) return false;

        $query = array("username" => $_SESSION["user"]["username"]);
        $projection = array('_id' => 0, "key" => 1);

        $key = $globalMongo["usersprivate"]->findone($query, $projection)["key"];
        
        $plaintext = \Sodium\crypto_secretbox_open(hex2bin($key["challenge"]),
           hex2bin($key["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));

        if ($plaintext == $challenge) return true;
        else return false;
    }
}

 ?>