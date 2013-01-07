<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED); //show the errrors but ignore notices and deprecations
ini_set('display_errors',1); //show the errors
ini_set("sendmail_from", "info@penguincon.com");
if(!defined('DATABASE_TYPE')){
	define('DATABASE_TYPE','mysql'); //mysql OR mssql
}
if(!isset($_SESSION)){
	session_start();
}
define('DB_HOST','localhost');
define('DB_USER','');
define('DB_PASS','');

define('DB_DEFAULT','penguincon');
define('DB_SUPPORT','');
define('TEST_SITE',true);
define('USING_SSL',false);
define('SMTP_AVAILABLE',false);

if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
	define("CONNECTING_IP_ADDRESS",$_SERVER['HTTP_X_FORWARDED_FOR']);
} else {
	define("CONNECTING_IP_ADDRESS",$_SERVER['REMOTE_ADDR']);
}
require "php/mysql.functions.php";
require "php/db.functions.php";
require "php/SendEmail.inc";
require "php/Auth.class.php";
require "php/PasswordGenerator.class.php";
?>