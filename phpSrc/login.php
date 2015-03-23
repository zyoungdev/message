<?php 
class Login{
    public $clean = array();
}


if (!$_POST["username"]) return 0;
if (!$_POST["password"]) return 0;

$un = $_POST["username"];
$pw = $_POST["password"];

echo $un . " " . $pw;


 ?>