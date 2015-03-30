<?php 
include "./helper.php";
/* Stored */
//user
    //username
    //logintime
    //key
        //public

/* Session */
//user
    //username
    //logintime
    //key
        //hashedPW
        //salt
        //ChallengeKey
        //nonce
        //secret
        //public




class Login{
    public $clean = array();
    public $dirty = array();
    public $mongo = array();
    public $challenge = "This is the challenge";
    public $protectedUN = array("admin", "administrator", "root");

    public function __construct()
    {
        session_start();
        $this->mongo = openDB();
        $this->dirty["pw"] = $_POST["password"];
    }
    public function __destruct()
    {
        // session_write_close();
        closeDB($this->mongo["client"]);
        
    }
    public function usernameIsClean()
    {
        $length = mb_strlen($_POST["username"]);
        if (ctype_alnum($_POST["username"]) && $length <= 64)
        {
            $this->clean["un"] = $_POST["username"];
            return true;
        }
        else
        {
            return false;
        }
    }
    public function userExists()
    {
        //check DB if username exists
        foreach ($this->protectedUN as $key => $value) {
            if (strtolower($this->clean["un"]) == strtolower($value))
                return 0;
        }

        if ($this->mongo["userspublic"]->findone(array("username" => $this->clean["un"])))
        {
            if ($user = $this->mongo["usersprivate"]->findone(array("username" => $this->clean["un"])))
            {
                $_SESSION["user"]["username"] = $user["username"];
                $_SESSION["user"]["key"]["hashedPW"] = $user["key"]["hashedPW"];
                return 1;
            }
        } 
        else
        {
            $_SESSION["user"]["username"] = $this->clean["un"];

            return 0;
        }
    }
    public function passwordIsCorrect()
    {
        if (Sodium::crypto_pwhash_scryptsalsa208sha256_str_verify(hex2bin($_SESSION["user"]["key"]["hashedPW"]), $this->dirty["pw"]))
        {
            $query = array("username" => $_SESSION["user"]["username"]);
            $projection = array("messages" => 0);
            if ($user = $this->mongo["usersprivate"]->findone($query, $projection))
            {
                $_SESSION["user"] = $user;
                $_SESSION["user"]["key"]["public"] = $_SESSION["user"]["key"]["public"];
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return 0;
        }
    }
    public function hashPW()
    {
        $_SESSION["user"]["key"]["hashedPW"] = Sodium::crypto_pwhash_scryptsalsa208sha256_str(
            $this->dirty["pw"], Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
                    Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["hashedPW"] = bin2hex($_SESSION["user"]["key"]["hashedPW"]);
    }
    public function createSaltNonce()
    {
        $_SESSION["user"]["key"]["salt"] = Sodium::randombytes_buf(Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);
        $_SESSION["user"]["key"]["salt"] = bin2hex($_SESSION["user"]["key"]["salt"]);
        $_SESSION["user"]["key"]["nonce"] = Sodium::randombytes_buf(Sodium::CRYPTO_SECRETBOX_NONCEBYTES);
        $_SESSION["user"]["key"]["nonce"] = bin2hex($_SESSION["user"]["key"]["nonce"]);
    }
    public function getSalt()
    {
        $_SESSION["user"]["key"]["salt"] = $_SESSION["user"]["key"]["salt"];
        $_SESSION["user"]["key"]["nonce"] = $_SESSION["user"]["key"]["nonce"];
    }
    public function createMasterKeys()
    {
        $keypairLength = Sodium::CRYPTO_BOX_SECRETKEYBYTES;
        $challengeSecretLength = Sodium::CRYPTO_SECRETBOX_KEYBYTES;

        //Create Secret
        $_SESSION["user"]["key"]["secret"] = Sodium::crypto_pwhash_scryptsalsa208sha256(
            $keypairLength, $this->dirty["pw"], hex2bin($_SESSION["user"]["key"]["salt"]),
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["secret"] = bin2hex($_SESSION["user"]["key"]["secret"]);

        //Create Challenge Secret
        $_SESSION["user"]["key"]["challengeKey"] = Sodium::crypto_pwhash_scryptsalsa208sha256(
            $challengeSecretLength, $this->dirty["pw"], hex2bin($_SESSION["user"]["key"]["salt"]),
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["challengeKey"] = bin2hex($_SESSION["user"]["key"]["challengeKey"]);
    }
    public function createSigningKeys()
    {
        $_SESSION["user"]["key"]["public"] = Sodium::crypto_box_publickey_from_secretkey(hex2bin($_SESSION["user"]["key"]["secret"]));
        $_SESSION["user"]["key"]["public"] = bin2hex($_SESSION["user"]["key"]["public"]);
    }
    public function encryptChallenge()
    {
        /*
            Hard code encrypt $this->challenge. Secure? Must be a known value
            We use this so we can encrypt a value in the database without using the secret
            This ensures that if the [user][key][challenge] is stolen and decrypted
            The adversary can't derive the secret, only the [user][key][challengeKey]
            This is also easier to quickly check that we are the same user without using a password
        */
        $_SESSION["user"]["key"]["challenge"] = Sodium::crypto_secretbox($this->challenge, 
           hex2bin( $_SESSION["user"]["key"]["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));
        $_SESSION["user"]["key"]["challenge"] = bin2hex($_SESSION["user"]["key"]["challenge"]);
    }
    public function decryptChallenge()
    {
        if (!challengeIsDecrypted($this->mongo))
            return 0;
        else
            return 1;
        // $plaintext = Sodium::crypto_secretbox_open(hex2bin($_SESSION["user"]["key"]["challenge"]),
        //    hex2bin($_SESSION["user"]["key"]["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));

        // if ($plaintext == $this->challenge) return true;
        // else return false;
    }
    public function updateLoginTime()
    {
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');

        $query = array("username" => $_SESSION["user"]["username"]);
        $update = array('$set' => array("lastLogin" => $date->getTimestamp()));

        $this->mongo["userspublic"]->update($query, $update);
    }
    public function createNewUser()
    {
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');
        $newuserprivate = array('username' => $this->clean["un"],
            'lastLogin' => $date->getTimestamp(),
            'key' => array(
                'hashedPW' => $_SESSION["user"]["key"]["hashedPW"],
                'public' => $_SESSION["user"]["key"]["public"],
                'salt' => $_SESSION["user"]["key"]["salt"],
                'challenge' => $_SESSION["user"]["key"]["challenge"],
                'nonce' => $_SESSION["user"]["key"]["nonce"]
            )
        );
        $newuser = array('username' => $this->clean["un"],
            'lastLogin' => $date->getTimestamp(),
            'key' => array(
                'public' => $_SESSION["user"]["key"]["public"]
            )
        );

        if ($this->mongo["usersprivate"]->save($newuserprivate))
        {
            if (!$this->mongo["userspublic"]->save($newuser))
                return 0;
            else
                return 1;
        }
        else
        {
            return 0;
        }
    }
    public function cleanup()
    {
        if(isset($_SESSION["user"]["key"]["hashedPW"]))
            // echo "hashedPW";
            Sodium::sodium_memzero($_SESSION["user"]["key"]["hashedPW"]);
        if(isset($_SESSION["user"]["key"]["salt"]))
            // echo "salt";
            Sodium::sodium_memzero($_SESSION["user"]["key"]["salt"]);
        if(isset($_SESSION["user"]["key"]["challenge"]))
            // echo "salt";
            Sodium::sodium_memzero($_SESSION["user"]["key"]["challenge"]);
        if(isset($_SESSION["user"]["key"]["nonce"]))
            // echo "nonce";
            Sodium::sodium_memzero($_SESSION["user"]["key"]["nonce"]);
        if(isset($_SESSION["user"]["key"]["keypair"]))
            // echo "keypair";
            Sodium::sodium_memzero($_SESSION["user"]["key"]["keypair"]);
    }
}

//accepts $_POST["username"] and $_POST["password"]
function logUserIn()
{


    $login = new Login;
    $return = new Returning;
    if (!$login->usernameIsClean())
    {
        $return->exitNow(0, "Username is not clean\n");
    }

    //check if user is in the DB
    //if true then load up our user variables
    if ($login->userExists())
    {
        // verify password
        if (!$login->passwordIsCorrect())
        {
            $return->exitNow(0, "Password is incorrect\n");
        }
        $login->getSalt();
        $login->createMasterKeys();
        $login->createSigningKeys();
        if (!challengeIsDecrypted($login->mongo))
        {
            $ret = new Returning;
            $ret->exitNow(-1, "Challenge could not be decrypted");
        }
        $login->updateLoginTime();
        $login->cleanup();
        $return->exitNow(1, "Welcome! " . $_SESSION["user"]["username"]);
    }
    else
    {
        
        // hash password
        $login->hashPW();

        $login->createSaltNonce();
        $login->createMasterKeys();
        $login->createSigningKeys();
        $login->encryptChallenge();
        if (!$login->createNewUser())
        {

            $return->exitNow(0, "Could not create new user in DB\n");
        }
        if (!challengeIsDecrypted($login->mongo))
        {
            $ret = new Returning;
            $ret->exitNow(-1, "Challenge could not be decrypted");
        }

        $login->cleanup();
        $return->exitNow(1, "Welcome! " . $_SESSION["user"]["username"]);
    }
}
if ($_POST["username"] && $_POST["password"])
{
    logUserIn();
}
