<?php 

session_start();
include_once "globals.php";

include_once "addContact.php";
include_once "changeAvatar.php";
include_once "deleteContact.php";
include_once "deleteMessage.php";
include_once "deleteMultipleContacts.php";
include_once "deleteMultipleMessages.php";
include_once "downloadMessages.php";
include_once "getAvatar.php";
include_once "getContact.php";
include_once "getSettings.php";
include_once "listContacts.php";
include_once "listMessages.php";
include_once "login.php";
include_once "sendMessage.php";
include_once "updateSettings.php";
include_once "verify.php";
include_once "viewMessage.php";

$globalMongo = openDB();

if (isset($_POST["login"]) && $_POST["login"])
{
    $login = new Login;

    if (isset($_POST["username"]) && isset($_POST["password"]))
    {
        $login->logUserIn();
    }
}
else if (isset($_POST["changePassword"]) && $_POST["changePassword"])
{
    $login = new Login;
    $login->changePassword();
}
else
{
    $verify = new Verify;
    $verify->challengeIsDecrypted();
    session_regenerate_id();

    if (isset($_POST["addContact"]) && $_POST["addContact"])
    {
        $add = new AddContact;

        if ($_POST["contact"])
        {
            $add->main();
        }
    }
    elseif (isset($_POST["changeAvatar"]) && $_POST["changeAvatar"])
    {
        $change = new ChangeAvatar;

        if ($_POST["avatar"])
            $change->main();
    }
    elseif (isset($_POST["checkSession"]) && $_POST["checkSession"])
    {
        // We have already verified the user session
        $ret = new Returning;
        $ret->exitNow(1, "Welcome back " . $_SESSION["user"]["username"]);
    }
    elseif (isset($_POST["deleteContact"]) && $_POST["deleteContact"])
    {
        // $del = new DeleteContact;

        // if ($_POST["username"])
        //     $del->main();
    }
    elseif (isset($_POST["deleteMessage"]) && $_POST["deleteMessage"])
    {
        $del = new DeleteMessage;

        if ($_POST["timestamp"] && $_POST["username"])
        {
            $del->main();
        }
    }
    elseif (isset($_POST["deleteMultipleContacts"]) && $_POST["deleteMultipleContacts"])
    {
        $del = new DeleteMultipleContacts;

        if ($_POST["contacts"])
        {
            $del->main();
        }
    }
    elseif (isset($_POST["deleteMultipleMessages"]) && $_POST["deleteMultipleMessages"])
    {
        $del = new DeleteMultipleMessages;

        if ($_POST["messages"] && $_POST["deleteMessages"])
        {
            $del->main();
        }
    }
    elseif (isset($_POST["downloadMessages"]) && $_POST["downloadMessages"])
    {
        $down = new DownloadMessages;
        $down->main();
    }
    elseif (isset($_POST["getAvatar"]) && $_POST["getAvatar"])
    {
        $av = new GetAvatar;

        if ($_POST["user"])
        {
            $av->main();
        }
    }
    elseif (isset($_POST["getContact"]) && $_POST["getContact"])
    {
        $get = new GetContact;

        if (isset($_POST["user"]))
            $get->main();
    }
    elseif (isset($_POST["getSettings"]) && $_POST["getSettings"])
    {
        $get = new GetSettings;
        $get->main();
    }
    elseif (isset($_POST["listContacts"]) && $_POST["listContacts"])
    {
        $contacts = new ListContacts;
        $contacts->main();
    }
    elseif (isset($_POST["listMessages"]) && $_POST["listMessages"])
    {
        $list = new ListMessages;
        $list->main();
    }
    elseif (isset($_POST["sendMessage"]) && $_POST["sendMessage"])
    {
        $send = new SendMessage;

        if ($_POST["recipient"] && $_POST["plaintext"] && $_POST["messageSize"])
        {
            $send->sendMessage();
        }
    }
    elseif (isset($_POST["updateSettings"]) && $_POST["updateSettings"])
    {
        $up = new UpdateSettings;
        $up->main();
    }
    elseif (isset($_POST["viewMessage"]) && $_POST["viewMessage"])
    {
        $view = new ViewMessage;

        if ($_POST["username"] && $_POST["timestamp"])
        {
            $view->main();
        }
    }
}

?>
