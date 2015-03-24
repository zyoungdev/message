<?php 

/* Stored */
//user
    //username
    //logintime
    //key
        //hashedpw
        //salt
        //ChallengeKey
        //nonce

/* Session */
//user
    //username
    //logintime
    //key
        //hashedpw
        //salt
        //ChallengeKey
        //nonce
        //secret
        //public

class Returning{
    public $code;
    public $message;
    public function exitNow($c, $m)
    {
        $this->code = $c;
        $this->message = $m;
        echo json_encode($this);
        exit;
    }
}


class Login{
    public $clean = array();
    public $dirty = array();
    public $mongo = array();
    public $challenge = "This is the challenge";

    public function __construct()
    {
        $this->mongo["client"] = new Mongo();
        $this->mongo["collection"] = $this->mongo["client"]->messageApp;
        $this->mongo["users"] = $this->mongo["collection"]->users;

        $this->dirty["pw"] = $_POST["password"];
    }
    public function __destruct()
    {
        if ($this->mongo)
        {
            $this->mongo["client"]->close();
        }
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
        if ($user = $this->mongo["users"]->findone(array("username" => $this->clean["un"])))
        {
            $_SESSION["user"] = $user;

            $_SESSION["user"]["key"]["public"] = hex2bin($_SESSION["user"]["key"]["public"]);
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    public function hashPW()
    {
        $_SESSION["user"]["key"]["hashedPW"] = Sodium::crypto_pwhash_scryptsalsa208sha256_str
          ($this->dirty["pw"], Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
                    Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);
    }
    public function createSaltNonce()
    {
        $_SESSION["user"]["key"]["salt"] = Sodium::randombytes_buf(Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);
        $_SESSION["user"]["key"]["nonce"] = Sodium::randombytes_buf(Sodium::CRYPTO_SECRETBOX_NONCEBYTES);
    }
    public function getSalt()
    {
        $_SESSION["user"]["key"]["salt"] = hex2bin($_SESSION["user"]["key"]["salt"]);
        $_SESSION["user"]["key"]["nonce"] = hex2bin($_SESSION["user"]["key"]["nonce"]);
    }
    public function createMasterKeys()
    {
        $keypairLength = Sodium::CRYPTO_SIGN_KEYPAIRBYTES;
        $challengeSecretLength = Sodium::CRYPTO_SECRETBOX_KEYBYTES;

        //Create Keypair
        $_SESSION["user"]["key"]["keypair"] = Sodium::crypto_pwhash_scryptsalsa208sha256(
            $keypairLength, $this->dirty["pw"], $_SESSION["user"]["key"]["salt"],
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        //Create Challenge Secret
        $_SESSION["user"]["key"]["challengeKey"] = Sodium::crypto_pwhash_scryptsalsa208sha256(
            $challengeSecretLength, $this->dirty["pw"], $_SESSION["user"]["key"]["salt"],
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);
    }
    public function createSigningKeys()
    {
        $_SESSION["user"]["key"]["secret"] = Sodium::crypto_sign_secretkey($_SESSION["user"]["key"]["keypair"]);
        $_SESSION["user"]["key"]["public"] = Sodium::crypto_sign_publickey($_SESSION["user"]["key"]["keypair"]);
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
            $_SESSION["user"]["key"]["nonce"], $_SESSION["user"]["key"]["challengeKey"]);
    }
    public function challengeIsDecrypted()
    {
        $plaintext = Sodium::crypto_secretbox_open(hex2bin($_SESSION["user"]["key"]["challenge"]),
            $_SESSION["user"]["key"]["nonce"], $_SESSION["user"]["key"]["challengeKey"]);

        if ($plaintext == $this->challenge) return true;
        else return false;
    }
    public function createNewUser()
    {
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');
        $newuser = array('username' => $this->clean["un"],
            'lastLogin' => $date->getTimestamp(),
            'key' => array(
                'public' => bin2hex($_SESSION["user"]["key"]["public"]),
                'hashedPW' => $_SESSION["user"]["key"]["hashedPW"],
                'salt' => bin2hex($_SESSION["user"]["key"]["salt"]),
                'nonce' => bin2hex($_SESSION["user"]["key"]["nonce"]),
                'challenge' => bin2hex($_SESSION["user"]["key"]["challenge"])
            )
            );

        if (!$this->mongo["users"]->save($newuser))
        {
            $this->cleanup();
            return 0;
        }
        else
        {
            return 1;
        }
    }
    public function passwordIsCorrect()
    {
        if (Sodium::crypto_pwhash_scryptsalsa208sha256_str_verify($_SESSION["user"]["key"]["hashedPW"], $this->dirty["pw"])) return 1;
        else return 0;
    }
    public function cleanup()
    {
        Sodium::sodium_memzero($_SESSION["user"]["key"]["hashedPW"]);
        Sodium::sodium_memzero($_SESSION["user"]["key"]["salt"]);
        Sodium::sodium_memzero($_SESSION["user"]["key"]["keypair"]);

        session_regenerate_id();
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
    session_start();
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
        if (!$login->challengeIsDecrypted())
        {
            $return->exitNow(0, "Challenge not decrypted\n");
        }
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
        $login->cleanup();

        $return->exitNow(1, "Welcome! " . $_SESSION["user"]["username"]);
    }
}
if ($_POST["username"] && $_POST["password"])
{
    logUserIn();
}
