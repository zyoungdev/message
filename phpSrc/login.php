<?php 
class Login{
    public $clean = array();
    public $dirty = array();
    public $mongo = array();

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
            return 1;
        } 
        else
        {
            return 0;
        }
    }
    public function hashPW()
    {
        $_SESSION["user"]["hashedPW"] = Sodium::crypto_pwhash_scryptsalsa208sha256_str
          ($this->dirty["pw"], Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
                    Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);
    }
    public function createSalt()
    {
        $_SESSION["user"]["salt"] = Sodium::randombytes_buf(Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);
    }
    public function getSalt()
    {
        $_SESSION["user"]["salt"] = hex2bin($_SESSION["user"]["salt"]);
    }
    public function createKeyPair()
    {
        $out_len = Sodium::CRYPTO_SIGN_KEYPAIRBYTES;
        $_SESSION["user"]["keypair"] = Sodium::crypto_pwhash_scryptsalsa208sha256(
            $out_len, $this->dirty["pw"], $_SESSION["user"]["salt"],
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium::CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);
    }
    public function createSigningKeys()
    {
        $_SESSION["user"]["secret"] = Sodium::crypto_sign_secretkey($_SESSION["user"]["keypair"]);
        $_SESSION["user"]["public"] = Sodium::crypto_sign_publickey($_SESSION["user"]["keypair"]);
    }
    public function createNewUser()
    {
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');
        $newuser = array('username' => $this->clean["un"],
            'hashedPW' => $_SESSION["user"]["hashedPW"],
            'salt' => bin2hex($_SESSION["user"]["salt"]),
            'lastLogin' => $date->getTimestamp());

        if (!$this->mongo["users"]->save($newuser))
        {
            $this->cleanup();
            exit;
        }
    }
    public function passwordIsCorrect()
    {
        if (Sodium::crypto_pwhash_scryptsalsa208sha256_str_verify($_SESSION["user"]["hashedPW"], $this->dirty["pw"])) return 1;
        else return 0;
    }
    public function cleanup()
    {
        Sodium::sodium_memzero($_SESSION["user"]["hashedPW"]);
        Sodium::sodium_memzero($_SESSION["user"]["salt"]);
        Sodium::sodium_memzero($_SESSION["user"]["keypair"]);

        session_regenerate_id();
    }
}

//accepts $_POST["un"] and $_POST["pw"]
function main()
{
    $login = new Login;
    if (!$login->usernameIsClean())
    {
        echo "Username is not clean";
        exit;
    }

    //check if user is in the DB
    //if true then load up our user variables
    session_start();
    if ($login->userExists())
    {
        // verify password
        if (!$login->passwordIsCorrect())
        {
            echo "Password is incorrect";
            exit;
        }
        $login->getSalt();
        $login->createKeyPair();
        $login->createSigningKeys();
        $login->cleanup();
    }
    else
    {
        // hash password
        $login->hashPW();
        $login->createSalt();
        $login->createKeyPair();
        $login->createSigningKeys();
        $login->createNewUser();
        $login->cleanup();
    }
}
main();
