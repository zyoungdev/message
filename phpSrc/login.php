<?php 
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
    // public $clean = array();
    // public $dirty = array();
    private $protectedUN = array("admin", "administrator", "root");
    private $mongo;
    private $dirty;

    public function __construct()
    {
        $this->dirty["pw"] = $_POST["password"];
    }
    public function __destruct()
    {
    }
    private function usernameIsClean()
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
    private function userExists()
    {
        global $globalMongo;
        //check DB if username exists
        foreach ($this->protectedUN as $key => $value) {
            if (strtolower($this->clean["un"]) == strtolower($value))
                return 0;
        }

        if ($globalMongo["userspublic"]->findone(array("username" => $this->clean["un"])))
        {
            if ($user = $globalMongo["usersprivate"]->findone(array("username" => $this->clean["un"])))
            {
                $user = classToArray($user);

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
    private function passwordIsCorrect()
    {
        global $globalMongo;
        if ( \Sodium\crypto_pwhash_str_verify(hex2bin($_SESSION["user"]["key"]["hashedPW"]), $this->dirty["pw"]) )
        {
            $query = array("username" => $_SESSION["user"]["username"]);
            $projection = array("messages" => 0,"contacts" => 0);
            if ($user = $globalMongo["usersprivate"]->findone($query, $projection))
            {
                $_SESSION["user"] = classToArray($user);
                // $_SESSION["user"]["key"]["public"] = $_SESSION["user"]["key"]["public"];
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
    private function hashPW()
    {
        $_SESSION["user"]["key"]["hashedPW"] = \Sodium\crypto_pwhash_str(
            $this->dirty["pw"], \Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                    \Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["hashedPW"] = bin2hex($_SESSION["user"]["key"]["hashedPW"]);
    }
    private function createSaltNonce()
    {
        $_SESSION["user"]["key"]["salt"] = \Sodium\randombytes_buf(\Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);
        $_SESSION["user"]["key"]["salt"] = bin2hex($_SESSION["user"]["key"]["salt"]);
        $_SESSION["user"]["key"]["nonce"] = \Sodium\randombytes_buf(\Sodium\CRYPTO_SECRETBOX_NONCEBYTES);
        $_SESSION["user"]["key"]["nonce"] = bin2hex($_SESSION["user"]["key"]["nonce"]);
    }
    private function getSalt()
    {
        logThis($_SESSION);
        $_SESSION["user"]["key"]["salt"] = $_SESSION["user"]["key"]["salt"];
        $_SESSION["user"]["key"]["nonce"] = $_SESSION["user"]["key"]["nonce"];
    }
    private function createMasterKeys()
    {
        $keypairLength = \Sodium\CRYPTO_BOX_SECRETKEYBYTES;
        $challengeSecretLength = \Sodium\CRYPTO_SECRETBOX_KEYBYTES;

        //Create Secret
        $_SESSION["user"]["key"]["secret"] = \Sodium\crypto_pwhash_scryptsalsa208sha256(
            $keypairLength, $this->dirty["pw"], hex2bin($_SESSION["user"]["key"]["salt"]),
            \Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["secret"] = bin2hex($_SESSION["user"]["key"]["secret"]);

        //Create Challenge Secret
        $_SESSION["user"]["key"]["challengeKey"] = \Sodium\crypto_pwhash_scryptsalsa208sha256(
            $challengeSecretLength, $this->dirty["pw"], hex2bin($_SESSION["user"]["key"]["salt"]),
            \Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE);

        $_SESSION["user"]["key"]["challengeKey"] = bin2hex($_SESSION["user"]["key"]["challengeKey"]);
    }
    private function createSigningKeys()
    {
        $_SESSION["user"]["key"]["public"] = \Sodium\crypto_box_publickey_from_secretkey(hex2bin($_SESSION["user"]["key"]["secret"]));
        $_SESSION["user"]["key"]["public"] = bin2hex($_SESSION["user"]["key"]["public"]);
    }
    private function encryptChallenge()
    {
        global $challenge;

        /*
            Hard code encrypt $this->challenge. Secure? Must be a known value
            We use this so we can encrypt a value in the database without using the secret
            This ensures that if the [user][key][challenge] is stolen and decrypted
            The adversary can't derive the secret, only the [user][key][challengeKey]
            This is also easier to quickly check that we are the same user without using a password
        */
        $_SESSION["user"]["key"]["challenge"] = \Sodium\crypto_secretbox($challenge, 
           hex2bin( $_SESSION["user"]["key"]["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));
        $_SESSION["user"]["key"]["challenge"] = bin2hex($_SESSION["user"]["key"]["challenge"]);
    }
    private function decryptChallenge()
    {
        global $globalMongo;
        if (!challengeIsDecrypted($globalMongo))
            return 0;
        else
            return 1;
        // $plaintext = \Sodium\crypto_secretbox_open(hex2bin($_SESSION["user"]["key"]["challenge"]),
        //    hex2bin($_SESSION["user"]["key"]["nonce"]), hex2bin($_SESSION["user"]["key"]["challengeKey"]));

        // if ($plaintext == $this->challenge) return true;
        // else return false;
    }
    private function updateLoginTime()
    {
        global $globalMongo;
        date_default_timezone_set('America/Los_Angeles');
        $date = new DateTime('NOW');

        $query = array("username" => $_SESSION["user"]["username"]);
        $update = array('$set' => array("lastLogin" => $date->getTimestamp()));

        $globalMongo["userspublic"]->updateOne($query, $update);
    }
    private function createNewUser()
    {
        global $globalMongo;
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
            ),
            'settings' => array(
                'mPerPage' => 10,
                'displayName' => "Anonymous",
                'nested' => true
            )
        );
        $newuser = array('username' => $this->clean["un"],
            'lastLogin' => $date->getTimestamp(),
            'avatar' => "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCADIAMgDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD/AD/6KKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoooov93YAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=",
            'key' => array(
                'public' => $_SESSION["user"]["key"]["public"]
            )
        );

        $_SESSION["user"]["settings"] = array('mPerPage' => 10, 'displayName' => "Anonymous");
        

        if ($globalMongo["usersprivate"]->insertOne($newuserprivate))
        {
            if (!$globalMongo["userspublic"]->insertOne($newuser))
                return 0;
            else
                return 1;
        }
        else
        {
            return 0;
        }
    }
    private function updatePassword()
    {
        global $globalMongo;
        $query = array("username" => $_SESSION["user"]["username"]);
        $privateuser = array('key' => array(
                'hashedPW' => $_SESSION["user"]["key"]["hashedPW"],
                'public' => $_SESSION["user"]["key"]["public"],
                'salt' => $_SESSION["user"]["key"]["salt"],
                'challenge' => $_SESSION["user"]["key"]["challenge"],
                'nonce' => $_SESSION["user"]["key"]["nonce"]
            ));
        $publicuser = array('key' => array(
                'public' => $_SESSION["user"]["key"]["public"]
            ));
        $pripro = array('$set' => $privateuser);
        $pubpro = array('$set' => $publicuser);

        if ($res = $globalMongo["usersprivate"]->updateOne($query, $pripro))
        {
            if ($globalMongo["userspublic"]->updateOne($query, $pubpro))
            {
                $globalMongo["usersprivate"]->updateOne($query, array('$set' => array("messages" => new stdClass())));
                return 1;
            }
        }
        else
        {
            return 0;
        }
    }
    private function cleanup()
    {
        if(isset($_SESSION["user"]["key"]["hashedPW"]))
            // echo "hashedPW";
            \Sodium\memzero($_SESSION["user"]["key"]["hashedPW"]);
        if(isset($_SESSION["user"]["key"]["salt"]))
            // echo "salt";
            \Sodium\memzero($_SESSION["user"]["key"]["salt"]);
        if(isset($_SESSION["user"]["key"]["challenge"]))
            // echo "salt";
            \Sodium\memzero($_SESSION["user"]["key"]["challenge"]);
        if(isset($_SESSION["user"]["key"]["nonce"]))
            // echo "nonce";
            \Sodium\memzero($_SESSION["user"]["key"]["nonce"]);
        if(isset($_SESSION["user"]["key"]["keypair"]))
            // echo "keypair";
            \Sodium\memzero($_SESSION["user"]["key"]["keypair"]);

        session_regenerate_id();
    }
    public function logUserIn()
    {
        $return = new Returning;
        $verify = new Verify;

        unset($_SESSION["user"]);
        if (!$this->usernameIsClean())
        {
            $return->exitNow(0, "Username can only contain letters and numbers. \n");
        }

        //check if user is in the DB
        //if true then load up our user variables
        if ($this->userExists())
        {
            // verify password
            if (!$this->passwordIsCorrect())
            {
                $return->exitNow(0, "The password you provided is incorrect.\n");
            }
            $this->getSalt();
            $this->createMasterKeys();
            $this->createSigningKeys();
            if (!$verify->challengeIsDecrypted())
            {
                $return->exitNow(-1, "Challenge could not be decrypted");
            }
            $this->updateLoginTime();
            $this->cleanup();
            $return->exitNow(1, "Welcome! " . $_SESSION["user"]["username"]);
        }
        else
        {
            // hash password
            $this->hashPW();
            $this->createSaltNonce();
            $this->createMasterKeys();
            $this->createSigningKeys();
            $this->encryptChallenge();
            if (!$this->createNewUser())
            {
                $return->exitNow(0, "The new user could not be created.\n");
            }
            if (!$verify->challengeIsDecrypted())
            {
                $return->exitNow(-1, "Challenge could not be decrypted");
            }

            $this->cleanup();
            $return->exitNow(1, "Welcome " . $_SESSION["user"]["username"] . "!");
        }
    }
    public function changePassword()
    {
        $return = new Returning;
        $verify = new Verify;

        if (!$verify->challengeIsDecrypted())
        {
            $ret = new Returning;
            $ret->exitNow(-1, "Challenge could not be decrypted");
        }
        $this->hashPW();
        $this->createSaltNonce();
        $this->createMasterKeys();
        $this->createSigningKeys();
        $this->encryptChallenge();
        if (!$this->updatePassword())
        {
            $return->exitNow(0, "Unable to change password");
        }
        $return->exitNow(1, "Password has been updated.");
    }
}


