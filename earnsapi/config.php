<?php
//ob_start("ob_gzhandler");

/* DATABASE CONFIGURATION */


define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'nsebhav');
define("SITE_KEY",'AA74CDCC2BBRT935136HH7B63C27');
define('SECRET_KEY','bGS6lzFqvvSQ8ALbOxatm7/Vk7mLQyzqaS34Q4oR1ew=');  // secret key can be a random string and keep in secret from anyone
define('ALGORITHM','HS256');   // Algorithm used to sign the token


function getDB() { 
	$dbhost=DB_SERVER;
	$dbuser=DB_USERNAME;
	$dbpass=DB_PASSWORD;
	$dbname=DB_DATABASE;
	$dbConnection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbConnection->exec("set names utf8");
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}
/* DATABASE CONFIGURATION END */

/* API key encryption */

function apiToken($session_uid){
  $db = getDB();
  $sql = "SELECT token FROM user where id = ".$session_uid;
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $Data = $stmt->fetch(PDO::FETCH_OBJ);  
  if(!empty($Data->token)){
    return $Data->token;
  }else{
    $key=md5(SITE_KEY.$session_uid);
    return hash('sha256', $key);
  }





 /* $sql = "SELECT password FROM user where id = ".$session_uid;
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $Data = $stmt->fetch(PDO::FETCH_OBJ);  
  if(!empty($Data->password)){ 
    $key=md5($Data->password.SITE_KEY.$session_uid.session_id()."rto");
    return hash('sha256', $key);
  }
  else {
    $key=md5(SITE_KEY.$session_uid);
    return hash('sha256', $key);
  }*/


}

function GenerateNewToken($session_uid,$pass){
  $db = getDB();
  $key=md5($pass.SITE_KEY.$session_uid.session_id()."rto");
  $token=hash('sha256', $key);
  $upinstoken = "update `user` set token = '".$token."' where id = ".$session_uid;
  debugLog("Query => ".$upinstoken);
  $insertstmt = $db->prepare($upinstoken);
  $insertstmt->execute();
  return $token;
}

function RefreshToken($session_uid,$toldtoken){
  return $toldtoken;




/*  $db = getDB();
  $key=md5($toldtoken.SITE_KEY.$session_uid.session_id()."rto");
  $token=hash('sha256', $key);
  debugLog("REFRESHING NEW Token  ======> ".$token);  
  $upinstoken = "update `user` set token = '".$token."' where id = ".$session_uid;
  debugLog("Query => ".$upinstoken);
  $insertstmt = $db->prepare($upinstoken);
  $insertstmt->execute();
  return $token;*/
}


function debugLog($logdata) {
  $currentdate = date('Y-m-d H:i:s');
  $file = "debugapi.log";
  $data = "[".$currentdate."]# ".$logdata."\n";
  file_put_contents($file, $data,FILE_APPEND);
}

?>