<?php
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization");
header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token');
header("Access-Control-Allow-Headers: X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
  exit(0);
} 


ini_set('session.gc_maxlifetime', 1);
session_start();    

?>

<?php
require 'config.php';
require 'Slim/Slim.php';
//Load Composer's autoloader
require 'vendor/autoload.php';  
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use \Firebase\JWT\JWT;

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->post('/golocaldata','golocaldata');
$app->post('/getstockhistory','getstockhistory');
$app->post('/validateuser','validateuser');
$app->post('/validateotp','validateotp');
$app->post('/login','login');
$app->post('/setOtp','setOtp');
$app->post('/adduser','adduser');
$app->post('/blockuser','blockuser');
$app->post('/stockdata','stockdata');
$app->post('/deviceRegid','deviceRegid');
$app->post('/setportfolio','setportfolio');
$app->post('/getportfolio','getportfolio');
$app->post('/sellstock','sellstock');
$app->post('/userdetails','userdetails');
$app->post('/getrsidata','getrsidata');
$app->post('/getportrsi','getportrsi');
$app->post('/sendpush','sendpush');
$app->run();

date_default_timezone_set('Asia/Kolkata');

function golocaldata(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("===================Inside userauth==========================");
  debugLog("Data => ".$request->getBody());
  $location=$data->location;
  $contact=$data->contact;
  debugLog("======================================= Start Local Data  ======================================= \n");
  try {
    if( (!empty($location)) && (!empty($contact))){
      $currentdate = date('d-m-y H:i:s');
      $curdate=strtotime($currentdate);
      $db = getDB();
      $insert="INSERT INTO `local_booking`(`user_location`, `user_contact`, `cur_date`) VALUES ('".$location."','".$contact."',".$curdate.")";
        $insstmt = $db->prepare($insert);     
        $insstmt->execute();
        debugLog("INsert SQL===>".$insert);
        $myData = "success";
        $msgchat = json_encode(['msg'=>$myData]);
        echo '{"Data": ' .$msgchat . '}';
      }else{
        $myData = "invalidData";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End Golocal Data ======================================= \n");
  }

  function getstockhistory(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside getstockdata ==========================");
    debugLog("Data => ".$request->getBody());
    $date=$data->date;
    try {
      if((!empty($date))){
       $ndate=strtotime($date);
       debugLog("DATE ===>".$ndate."\n");
       $beginOfDay = strtotime("today", $ndate);
       $endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;
       debugLog("Begin OF Date  ===>".$ndate."END OF Day".$endOfDay);
       $db = getDB();  
       $date = date('d-M-Y',$ndate);
       $sql="SELECT * FROM stock_data where ( date > ".$beginOfDay." and date < ".$endOfDay.")";
         debugLog($sql."\n");
         $stmt = $db->prepare($sql);
         $stmt->execute();
         $mainCount=$stmt->rowCount();
         debugLog($mainCount."ACTUAL COUNT FOR \n");
         if ($mainCount > 0) {
           while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
            $arr[] = $cmsgdata;
          }
          $apiresponse = "success";
          $responseInfo = json_encode(['response'=>$apiresponse,'date' =>$date,'data' => $arr]);
        }else{
          $apiresponse = "failed";
          $responseInfo = json_encode(['response'=>$apiresponse,'date' =>$date,'data' => 0]);
        }


        echo '{"Data": ' .$responseInfo . '}'; 

      }else{
        $myData = "invalidData";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }







    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End getstock Data ======================================= \n");
  }


  function validateotp(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside ValidateOTP ==========================");
    debugLog("Data => ".$request->getBody());
    $contact=$data->contact;
    $otp=$data->otp;
    $checkflag = false;
    $user_id = 0;
    try {
      if((!empty($otp)) && (!empty($contact))){
        $currentdate = date('d-m-y H:i:s');
        $curdate=strtotime($currentdate);
        $db = getDB();
        $sql="SELECT * FROM `mob_register_info` where mobile_no = ".$contact." order by id DESC limit 1";
        debugLog("Query => ".$sql);
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        if ($mainCount > 0)
        {
          debugLog("m here 1");

          while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
            $dbotp = $cmsgdata->otp;
          }
          debugLog("dbotp => ".$dbotp."| user Otp => ".$otp);
          if($dbotp == $otp){
            $myData = "Success";
            $user_id = getuserData($contact);
            $checkflag = true;
          }else{
            $myData = "invalid otp";
          }

          $secretKey  = SECRET_KEY;
          $issuedAt   = new DateTimeImmutable();
          $serverName = "http://localhost/earnsapi/";
          $data = [
            'iat'  => $issuedAt->getTimestamp(),        
            'iss'  => $serverName,                       
            'nbf'  => $issuedAt->getTimestamp(),         
            'response'=>$myData,'status' => $checkflag,'userid'=>$user_id[0],'role'=>(int)$user_id[1],
          ];

          debugLog("FLAG ==>".$checkflag);
          if($checkflag){
            $api_data =JWT::encode( $data,$secretKey,'HS512');
            $api_response = json_encode(['token'=>$api_data,'status' => $checkflag,'userid'=>$user_id[0],'role'=>$user_id[1] ]);
          }else{
            $api_response = json_encode(['message'=>"Invalid Otp",'status' => $checkflag]); 
          }
          echo '{"Data": ' .$api_response.'}';
        }else{
          $myData = "No Record Found";
          debugLog($myData);
          $api_response = json_encode(['response'=>$myData,'status' => $checkflag,'userid'=>$user_id]);
          echo '{"Data": ' .$api_response . '}';
        }
      }else{
        debugLog("m here 3");
        $myData = "invalidData";
        $msgchat = json_encode(['response'=>$myData,'status' => $checkflag,'userid'=>$user_id]);
        echo '{"Data": ' .$msgchat . '}';
      }
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End ValidateOTP ======================================= \n");
  }



  function setOtp(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside getstockdata ==========================");
    debugLog("Data => ".$request->getBody());
    $user_id=$data->user_id;
    $otp=$data->otp;
    try {
      if((!empty($otp))){
        $currentdate = date('d-m-y H:i:s');
        $curdate=strtotime($currentdate);
        $db = getDB();

        $insertsql = "UPDATE `otptable` SET otp='".$otp."' where id=1";
        debugLog("Query => ".$insertsql);
        $insertstmt = $db->prepare($insertsql);
        $insertstmt->execute();


        $response = "Success";
        $status = 1;
        $responseInfo = json_encode(['response'=>$response,'status' => $status]);
        echo '{"myData": ' .$responseInfo. '}';
      }else{
        $response = "InvalidData";
        $status = 0;
        $responseInfo = json_encode(['response'=>$response,'status' => $status]);
        echo '{"myData": ' .$responseInfo. '}';
      }
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End getstock Data ======================================= \n");

  }

  function login(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside Login User ==========================");
    debugLog("Data => ".$request->getBody());
    $contact=$data->contact;
    $checkflag = 0;
    try{
      if(!empty($contact))
      {
        $currentdate = date('d-m-y H:i:s');
        $curdate=strtotime($currentdate);
        $db = getDB();

        $sql="SELECT * FROM customers where username ='".$contact."'";
        debugLog("Query => ".$sql);
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        debugLog("Count => ".$mainCount);
        if($mainCount > 0)
        {
          while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
            $dbstatus=$cmsgdata->status;
          }
          debugLog("User Status => ".$dbstatus);
          if($dbstatus == 0){
            debugLog("Valid User");
            $status = "success";
            $code = 1;
            $checkflag = 1;
            $sendotp = sendotp($contact);
          }else{
            debugLog("Invalid User");
            $status = "unauthrised user";
            $code = 0;
          }
          $msgchat = json_encode(['response'=>$status,'code'=>$code,'status' => $checkflag]);
          echo '{"Data": ' .$msgchat . '}';
        }else{
          $myData = "No Record Found";
          debugLog($myData);
          $myData= json_encode(['response' => $myData, 'status' => $checkflag]);
          echo '{"Data": ' .$myData . '}';
        }
      }else{
        $myData = "invalid Data";
        debugLog($myData);
        $myData= json_encode(['response' => $myData,"status" => $checkflag]);
        echo '{"Data": ' .$myData . '}';
      }
    }catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End Login User  ======================================= \n");
  }




  function adduser(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside Add User ==========================");
    debugLog("Data => ".$request->getBody());
    $user_id=$data->user_id;
    $contact=$data->mobile;
    $name=$data->name;
    $email=$data->email;
    try {
      if((!empty($contact)) && (!empty($name)) && (!empty($email))){
       debugLog("NOt Empty");
       $currentdate = date('d-m-y H:i:s');
       $curdate=strtotime($currentdate);
       $db = getDB();    
       $sql="SELECT * FROM customers where username='".$contact."' and email='".$email."'";
       debugLog($sql."\n");
       $stmt = $db->prepare($sql);
       $stmt->execute();
       $mainCount=$stmt->rowCount();
       debugLog($mainCount."ACTUAL COUNT FOR \n");
       if ($mainCount >0) {
        $status = "already exist";
        $code = 0;
        $msgchat = json_encode(['response'=>$status,'status'=>$code,]);
        echo '{"Data": ' .$msgchat . '}';
      }else{
        $insertsql = "INSERT INTO `customers`(`username`,`name`,`email`) VALUES ('".$contact."','".$name."','".$email."')";
          debugLog("Query => ".$insertsql);
          $insertstmt = $db->prepare($insertsql);
          $insertstmt->execute();
          $status = "success";
          $code = 1;
          $msgchat = json_encode(['response'=>$status,'status'=>$code,]);
          echo '{"Data": ' .$msgchat . '}';
        }


      }else{
        $myData = "invalidData";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End Add User  ======================================= \n");
  }

  function blockuser(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside Block User ==========================");
    debugLog("Data => ".$request->getBody());
    $user_id=$data->user_id;
    $contact=$data->contact;
    try {
      if((!empty($contact))){
        $currentdate = date('d-m-y H:i:s');
        $curdate=strtotime($currentdate);
        $db = getDB();    

        $sql = "UPDATE `customers` SET status = 1 WHERE contact = '".$contact."'";
        debugLog($sql);
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $status = "success";
        $code = 1;
        $msgchat = json_encode(['response'=>$status,'code'=>$code,]);
        echo '{"Data": ' .$msgchat . '}';



      }else{
        $myData = "invalidData";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End Block User  ======================================= \n");
  }


  function validateuser(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    debugLog("===================Inside getstockdata ==========================");
    debugLog("Data => ".$request->getBody());
    $user_id=$data->user_id;
    $status='';
    $code = 0;
    try {
      if((!empty($user_id)))
      {
        $currentdate = date('d-m-y H:i:s');
        $curdate=strtotime($currentdate);
        $db = getDB();
        $sql="SELECT * FROM customers where user_id='".$user_id."'";
        debugLog($sql."\n");
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        debugLog($mainCount."ACTUAL COUNT FOR \n");
        if ($mainCount > 0) {
          while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
           debugLog($cmsgdata->status."While Status \n");
           $dbstatus=$cmsgdata->status;
         }
         if($dbstatus != 1){
           $response = "success";
           $code = 1;
         }else{
          $response = "Unauthoriz User";
        }
        $msgchat = json_encode(['response'=>$response,'code'=>$code,'validcode'=>$dbstatus]);
        echo '{"Data": ' .$msgchat . '}';
      }else{
        $response = "No Record Found";
        $msgchat = json_encode(['response'=>$response,'code'=>$code,]);
        echo '{"Data": ' .$msgchat . '}';
      }
    }else{
      $response = "invalidData";
      $myData= json_encode(['response' => $response]);
      echo '{"Data": ' .$myData . '}';
    }
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
  debugLog("======================================= End getstock Data ======================================= \n");

}







function resetpassword(){
  $request = \Slim\Slim::getInstance()->request();

  $data = json_decode($request->getBody());
  $useremail=$data->useremail;
  debugLog("=========================== Inside Reset password ============================== \n");
  debugLog($useremail. " THE RESET EMAIL PROVIDED \n");
  try {
   if(!empty($useremail)){
    debugLog($useremail." NOT EMPTY EMAIL IF \n");
    date_default_timezone_set('Asia/Kolkata');
    $db = getDB();
    $sql="SELECT email FROM reg_customers WHERE email= '".$useremail."'";
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR EMAIL RESET  \n");
    $custData = $stmt->fetch(PDO::FETCH_OBJ);    
    if ($mainCount != 0) {
      debugLog($mainCount."=> INSIDE  MAIN COUNT \n"); 
      $expFormat = date('Y-m-d H:i:s');
      $expDate = strtotime($expFormat);
      $key = md5(2418*2+$useremail);
      $addKey = substr(md5(uniqid(rand(),1)),3,10);
      $key = $key . $addKey;
      debugLog($key."=> RESET KEY \n");
      
      
      /*THE QUERY FOR SEARCHING SAME EMAIL ID */
      $sql1= "INSERT INTO `password_reset_temp` (`email`, `token`, `expDate`)
      VALUES ('".$useremail."', '".$key."', '".$expDate."')";
        debugLog($sql1."=> RESET SQL QUERY \n");
        $resetstmt = $db->prepare($sql1);
        $resetstmt->execute();
      //resetsendMail($useremail,$key);
        $resetemail =  resetsendMail($useremail,$key);

        debugLog("MAIL RESPONSE=> " .$resetemail);
//      debugLog($sql1."=> RESET SQL QUERY \n");
        $myData = "mailsent";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }else{
        $myData = "nouser";
        $myData= json_encode(['msg' => $myData]);
        echo '{"Data": ' .$myData . '}';
      }

    }else{
      $myData = "invalid";
      $myData= json_encode(['msg' => $myData]);
      echo '{"Data": ' .$myData . '}';    
    }

  }catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }    
  $debug.="\n\n";
  debugLog($debug);    
}

function resetsendMail($email,$key){

  $output='<p>Dear user,</p>';
  $output.='<p>Please click on the following link to reset your password.</p>';
  $output.='<p>-------------------------------------------------------------</p>';
  $output.='<p><a href="https://www.webinovator.com/reset-password.php?key='.$key.'&email='.$email.'&action=reset" target="_blank">
  https://www.webinovator.com/reset-password.php?key='.$key.'&email='.$email.'&action=reset</a></p>'; 
  $output.='<p>-------------------------------------------------------------</p>';
  $output.='<p>Please be sure to copy the entire link into your browser.
  The link will expire after 1 hour for security reason.</p>';
  $output.='<p>If you did not request this forgotten password email, no action 
  is needed, your password will not be reset. However, you may want to log into 
  your account and change your security password as someone may have guessed it.</p>';   
  $output.='<p>Thanks,</p>';
  $output.=' Team <p>Selfy Study </p>';
  $date = date_default_timezone_set('Asia/Kolkata');
  $currentdate = date('d-m-y H:i:s');
  $curdate=strtotime($currentdate);
  $debug = '';
  debugLog("======================================= ABOVE EAMIL PHP MAILER ======================================= \n");
    $mail = new PHPMailer(true);// Passing `true` enables exceptions
    try {
      $debug.= "======================================= ".$mail." inside the try PHP MAILER ======================================= \n";
      $mail->SMTPSecure = false;
      $mail->SMTPAutoTLS = false;
      debugLog($debug."=> INSIDE THE EAMIL FUNCTION \n");
        $mail->SMTPDebug = 0;// Enable verbose debug output
        $mail->isSMTP();// Set mailer to use SMTP
        $mail->Host = ' mail.webinovator.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;// Enable SMTP authentication
        $mail->Username = 'khurshid@webinovator.com';// SMTP usernameDetails
        $mail->Password = 'Blackberrycurve@3g';// SMTP passwordde
        $mail->Port = 587;// TCP port to connect to      
        $mail->setFrom('info@webinovator.com', 'Password Reset');
        //$mail->addAddress('khurshid@webinovator.com', 'Khurshid Kalmani');     // Add a recipient
        //$mail->addAddress('khurshid@webinovator.com', 'Khurshid Kalmani');     // Add a recipient
        $mail->addAddress($email, "Password Reset");     // Add a recipient
        $mail->isHTML(true);// Set email format to HTML
        $mail->Subject = 'Password Recovery ';        
        $mail->Body  =$output; 
        $mail->AltBody =$output; 
        $mail->send();
        debugLog($mail);  
        debugLog("EMAIL Message has been sent Successfully \n");
        return 0;
        //$debug.='EMAIL Message has been sent Successfully';
      } catch (Exception $e) {
        debugLog('Message could not be sent. Mailer Error: '.$mail->ErrorInfo);
        debugLog('Message could not be sent. Mailer Error: '.$e);
        debugLog($debug."ERROR DEBUG MENU \n");
        return 1;
      }
      debugLog($debug."LAST DEBUG MENU \n");
    }
    
    function feedback(){
      $request = \Slim\Slim::getInstance()->request();
      $data = json_decode($request->getBody());
      $project_id=$data->proj_id;
      $username =$data->username;
      $email=$data->email;
      $body=$data->body;
      $sub=$data->subject;
      $user_id=$data->user_id;
      $currentdate = date('Y-m-d H:i:s');
      $curdate=strtotime($currentdate);
      $debug = '';

      try {
    //if((!empty($project_id))&&(!empty($user_id))){

        $db = getDB();
        $insert="INSERT INTO `feedback`( `timestamp`, `subject`,`bodytext`,`email`,`name`) VALUES (".$curdate.",'".$sub."','".$body."','".$email."','".$username."')";
          $insstmt = $db->prepare($insert);     
          $insstmt->execute();
          $sendemail =  sendMail($sub,$body,$email,$username);
          $apiresponse = "success";
          $responseInfo = json_encode(['response'=>$apiresponse]);
          echo '{"Data": ' .$responseInfo . '}';
    /*
    }else{
      $myData = "invalid customer Data";
      $myData= json_encode(['msg' => $myData]);
      echo '{"Custom": ' .$myData . '}';
    }
    */
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
  debugLog($debug);
}

function stockdata(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("======== Inside stockdata API ==========");
  debugLog("Data => ".$request->getBody());    
  $arr = array();
  $currentdate = date('Y-m-d 11:00:00');
  $curdate = strtotime($currentdate);
  $dt='';
  try {
    $db = getDB();  

    $datesql = "SELECT * FROM `refdate`";
    debugLog($datesql."\n");
    $dtstmt = $db->prepare($datesql);
    $dtstmt->execute();
    $mainCount=$dtstmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR \n");
    if ($mainCount > 0) {
     while($cmsgdata = $dtstmt->fetch(PDO::FETCH_OBJ)){
      $dt = $cmsgdata->curdate;
    }
  }

  debugLog("CUR DATE TABLE==>".$dt."\n");
  $date = date('d-M-Y',$dt);
  debugLog("New Date ==>".$date);

  $sql="SELECT * FROM stock_data where date >= ".$dt;
  debugLog($sql."\n");
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $mainCount=$stmt->rowCount();
  if ($mainCount > 0) {
   while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
    $arr[] = $cmsgdata;
  }
}

$apiresponse = "success";
$responseInfo = json_encode(['response'=>$apiresponse,'date' =>$date,'data' => $arr]);
echo '{"Data": ' .$responseInfo . '}'; 
} catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}   
debugLog("======== End stockdata API ==========");
}


function userdetails(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("======== Inside userdetails API ==========");
  debugLog("Data => ".$request->getBody());    
  $uid = $data->user_id;
  $arr = array();
  try {
    $db = getDB();  
    $sql="SELECT * FROM customers where user_id= ".$uid;
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR \n");
    if ($mainCount > 0) {
     while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
      $arr[] = $cmsgdata;
    }
  }


  $apiresponse = "success";
  $responseInfo = json_encode(['response'=>$apiresponse,'data' => $arr]);
  echo '{"Data": ' .$responseInfo . '}'; 
} catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}   
debugLog("======== End userdetails API ==========");
}


function setportfolio(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("======== Inside setportfolio API ==========");
  debugLog("Data => ".$request->getBody());    
  $uid = $data->user_id;
  $script = $data->scriptname;
  $ind = $data->industry;
  $shariaflag = $data->flag;
  $target = $data->target;
  $strategy = $data->strategy;
  $buy = $data->buy;
  $scripdate = $data->scripdate;
  $sell = $data->sell;
  $user_action = $data->user_action;
  $currentdate = date('d-m-y H:i:s');
  $curdate=strtotime($currentdate);
  $status = 0;  
  try {
    debugLog("INSIDE TRY");
    if((!empty($uid)) && (!empty($script)) && (!empty($curdate))){
      $db = getDB();  
      $datesql = "SELECT * FROM `cust_portfolio` WHERE scriptname='".$script."' and user_id =".$uid." and cur_date ='".$scripdate."'";
      debugLog("INSIDE SQL");
      debugLog($datesql."\n");
      $dtstmt = $db->prepare($datesql);
      $dtstmt->execute();
      $mainCount=$dtstmt->rowCount();
      debugLog($mainCount."ACTUAL COUNT FOR \n");
      if ($mainCount > 0) {
       debugLog("Record Present \n");
       $msg = "Record Present ";
       $st=0;
     }else{
      debugLog("Strategy====>".$strategy);
      if($strategy ==1){
        debugLog("BUY ==>".$buy);
        $insert = "INSERT INTO `cust_portfolio`(`scriptname`, `industry`, `target`, `buy`, `sell`, `cur_date`, `user_id`, `shariaflag`, `strategy`, `user_action`) VALUES ('".$script."','".$ind."','".$target."','".$buy."','".$sell."','".$scripdate."',".$uid.",".$shariaflag.",".$strategy.",".$user_action.")";
      }else{
        debugLog("BUY ==>".$buy);

        $insert = "INSERT INTO `cust_portfolio`( `scriptname`, `industry`, `buy`, `cur_date`, `user_id`, `shariaflag`, `strategy`) VALUES ('".$script."','".$ind."','".$buy."','".$scripdate."',".$uid.",".$shariaflag.",".$strategy.")";
      }
      debugLog("Insert SQl===>".$insert."\n");
      $insstmt = $db->prepare($insert);
      $insstmt->execute(); 
      $st=1;   
      $msg = "success";
    }     
    $responseInfo = json_encode(['response'=>$msg,'status' => $st]);
    echo '{"Data": ' .$responseInfo . '}'; 
  }else{
    $msg ="Invalid Data";
    $responseInfo = json_encode(['response'=>$msg,'status' => $st]);
    echo '{"myData": ' .$responseInfo. '}';
  }

} catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}   
debugLog("======== End setportfolio API ==========");
}

function getportfolio(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("======== Inside getportfolio API ==========");
  debugLog("Data => ".$request->getBody());    
  $arr = array();
  $uid = $data->user_id;
  $currentdate = date('Y-m-d 11:00:00');
  $curdate = strtotime($currentdate);
  $dt='';
  $finalarray=array();
  $checkflag=0;
  $newdata=array();
  
  try {
    $db = getDB();  
    $sql="SELECT * FROM `cust_portfolio` where user_id=".$uid;
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR \n");
    if ($mainCount > 0) {
     while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
       if($cmsgdata->strategy==2){
         debugLog("Hurray in IF");
         $newdata=rsiportfun($uid,$cmsgdata->scriptname);
         $cmsgdata->rsi_data = $newdata[1];
         $cmsgdata->checkflag = $newdata[0];
         $cmsgdata->lowval = $newdata[2];
       //$checkflag=$newdata[0][2];
         $finalarray[]=["data" => $cmsgdata];
         debugLog("Hurray in IF JSON DATA ===>".json_encode($newdata));
       }else{
         $finalarray[]=["data" => $cmsgdata];
       }
     }
   }

    //var_dump($finalarray);
    //exit;
   $apiresponse = "success";
   $responseInfo = json_encode(['response'=>$apiresponse,'data' => $finalarray]);
   echo '{"Data": ' .$responseInfo . '}'; 
 } catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}   
debugLog("======== End getportfolio API ==========");
}

function sellstock(){
  $request = \Slim\Slim::getInstance()->request();
  $data = json_decode($request->getBody());
  debugLog("======== Inside sellstock API ==========");
  debugLog("Data => ".$request->getBody());    
  $dbid = $data->id;
  $uid = $data->user_id;
  try {
    $db = getDB();  
    $sql="delete FROM `cust_portfolio` where id=".$dbid." and user_id=".$uid;
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $apiresponse = "success";
    $arr = 1;
    $responseInfo = json_encode(['response'=>$apiresponse,'status' => $arr]);
    echo '{"Data": ' .$responseInfo . '}'; 
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }   
  debugLog("======== End sellstock API ==========");
}


function getuserData($contact){
  debugLog("======== Inside getuserData Function ==========");
  debugLog("contact => ".$contact);
  $uid = 0;
  $role = 0;
  $udetail = array();
  if(!empty($contact)){
    $db = getDB();
    $sql="SELECT * FROM customers where username = ".$contact;
    debugLog("Query => ".$sql);
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    if ($mainCount > 0)
    {
      while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
        $uid=$cmsgdata->user_id;
        $role=$cmsgdata->role;
      }
    }
    debugLog("User Id => ".$uid." User Role  => ".$role);
  }
  debugLog("======== End getuserData Function ==========");
    // return $uid;
  return $udetail=[$uid,$role];
}

function getotp(){
  debugLog("======== Inside getotp Function ==========");
  $otp = 0;
  $db = getDB();
  $sql="SELECT * FROM `otptable`";
  debugLog("Query => ".$sql);
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $mainCount=$stmt->rowCount();
  if ($mainCount > 0)
  {
    while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
      $otp=$cmsgdata->otp;
    }
  }
  debugLog("OTP => ".$otp);
  debugLog("======== Inside getotp Function ==========");
  return $otp;
}

function sendotp($contact){
  debugLog("======== Inside sendotp Function ==========");
  debuglog("Data => ".$contact);
  $db = getDB();
  $currentdate = date('d-m-y H:i:s');
  $curdate=strtotime($currentdate);
  $otp = getotp();
  $insert = "INSERT INTO `mob_register_info`(`mobile_no`, `otp`, `clock`, `status`) VALUES (".$contact.",".$otp.",".$curdate.",0)";
    $insstmt = $db->prepare($insert);
    $insstmt->execute();
    debugLog("======== End sendotp Function ==========");

  }
  function deviceRegid(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());  
    debugLog("===================== Inside deviceRegid ============================ \n");
    $user_id = $data->user_id;
    $device_id = $data->device_id;  
    $currentdate = date('Y-m-d H:i:s');
    $curdate=strtotime($currentdate);
    debugLog("Data => ".$request->getBody());
    $udevice_id = '';
    try{
      if((!empty($user_id)) && (!empty($device_id)))
      {
        $db = getDB();
        $selsql = "SELECT * FROM mob_regid WHERE user_id = ".$user_id;
        debugLog($selsql);
        $stmt = $db->prepare($selsql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();

        while($udata = $stmt->fetch(PDO::FETCH_OBJ)){
          $udevice_id = $udata->device_id;
        }      
        if($mainCount > 0)
        {
          debugLog("Exisiting device id => ".$udevice_id." mob device id => ".$device_id);
          if($udevice_id != $device_id)
          {
            $updatesql = "UPDATE `mob_regid` SET `device_id`= '".$device_id."' WHERE user_id = ".$user_id; 
            debugLog($updatesql);
            $updatestmt = $db->prepare($updatesql);
            $updatestmt->execute();
          }else{
            debugLog("Already exists");
            echo json_encode("Already exists");
          }
        }else{
          $insertsql="INSERT INTO `mob_regid`(`user_id`,`device_id`, `created_at`) VALUES (".$user_id.",'".$device_id."',".$curdate.")";
            debugLog($insertsql);
            $insertstmt = $db->prepare($insertsql);
            $insertstmt->execute();
          }
          $db = null;
    //   echo json_encode("Success");
        }else{
    //   echo json_encode("failed");
        }
      } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }  


    function sendpushnotif($title,$msg){
      debugLog("================= Inside sendNotification =========================");
      define( 'API_ACCESS_KEY', 'AAAANS8H170:APA91bEud6BUPFP2KJ0b5TMJ6SqpS71G5DkYNRNasZyOdWeatqIm4JSfFRKDBNZFOXWFBmR9pzwKsTjIDujCYyAuiQM_r4S-Z4IzsriCGRo3rto7OUcKxrFnYFHLPVVU1-EfJS9Cuiut');
      $registrationIds = '';
      $db = getDB();
      $sql = "SELECT * from mob_regid" ;

    // $sql = "SELECT mob_regid.device_id, mob_regid.user_id FROM `customers` LEFT join mob_regid on mob_regid.user_id = customers.user_id WHERE phone_number ='".$contact."'";
      debugLog($sql);
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $mainCount=$stmt->rowCount();
      if($mainCount > 0){
        while($deviceId = $stmt->fetch(PDO::FETCH_OBJ)){
          debugLog("Device Id => ".$deviceId->device_id);
          debugLog("User Pending Notification before update  => ".$deviceId->notification_count);
          $addcount = $deviceId->notification_count + 1;
          $updatesql = "UPDATE `mob_regid` SET `notification_count`= ".$addcount." WHERE user_id = ".$deviceId->user_id;
          debugLog($updatesql);
          $updatestmt = $db->prepare($updatesql);
          $updatestmt->execute();
          $registrationIds  = [$deviceId->device_id];
            // userNotify($registrationIds,$contact,$addcount,$name);
          userNotify($registrationIds,$title,$msg);
        }
      }
      debugLog("================= End sendNotification =========================");
    }

    function userNotify($registrationIds,$restitle,$respmsg){
      debugLog("================= Inside userNotifiy ===================");
      $msg = array
      (
       'message'  =>$restitle,
       'title'    =>$respmsg,
       'subtitle' => 'New Business Received',
       'tickerText'   => 'New Customer Lead Received',
       'vibrate'  => 1,
       'sound'    => 1,
       'badge' => $count,
       'analytics_label' => 'AIMsg',
       'largeIcon'    => 'large_icon',
       'smallIcon'    => 'small_icon'
     );

      $fields = array
      (
        'registration_ids'  => $registrationIds,
        'data'          => $msg
      );

      $headers = array
      (
       'Authorization: key=' . API_ACCESS_KEY,
       'Content-Type: application/json'
     );
      $ch = curl_init();
      $url = 'https://fcm.googleapis.com/fcm/send';
      curl_setopt( $ch,CURLOPT_URL, $url );
      curl_setopt( $ch,CURLOPT_POST, true );
      curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
      curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
      $result = curl_exec($ch );
      curl_close( $ch );
    //echo $result;
      debugLog("Fields Data====>  ".json_encode($fields));
      debugLog("result ".$result);
      debugLog("================= End userNotifiy ===================");
    }

    function getrsidata(){
      $request = \Slim\Slim::getInstance()->request();
      $data = json_decode($request->getBody());
      debugLog("===================Inside getrsidata ==========================");
      debugLog("Data => ".$request->getBody());
      $dt='';

      try {
        $currentdate = date('d-m-y 11:00:00');
        $curdate=strtotime($currentdate);
        $db = getDB();

        /* Changes on 20 DEC by khurshid */
        $datesql = "SELECT * FROM `refdate`";
        debugLog($datesql."\n");
        $dtstmt = $db->prepare($datesql);
        $dtstmt->execute();
        $mainCount=$dtstmt->rowCount();
        debugLog($mainCount."ACTUAL COUNT FOR \n");
        if ($mainCount > 0) {
         while($cmsgdata = $dtstmt->fetch(PDO::FETCH_OBJ)){
          $dt = $cmsgdata->curdate;
        }
      }

      debugLog("CUR DATE TABLE==>".$dt."\n");
      debugLog("New Date ==>".date('d-M-Y',$dt));
      $date = date('d-M-Y',$dt);
      /* Changes on 20 DEC by khurshid */


      $sql="SELECT * FROM rsi_stock_data where date > ".$dt;
    //    $sql="SELECT * FROM rsi_stock_data where date > ".$curdate;
      debugLog($sql."\n");
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $mainCount=$stmt->rowCount();
      debugLog($mainCount."ACTUAL COUNT FOR RSI Data \n");
      $custData = $stmt->fetch(PDO::FETCH_OBJ);    
      if ($mainCount > 0) {
        while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){  
                // $finaldata[] = ['script'=>$cmsgdata->scriptname,'buy'=>$cmsgdata->buy,'shariaflag'=>$cmsgdata->shariaflag];
          $finaldata[] =$cmsgdata;;
        } 
      }
      $myData = "success";
      $msgchat = json_encode(['msg'=>$finaldata]);
      echo '{"Data": ' .$msgchat . '}';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    debugLog("======================================= End getrsidata Data ======================================= \n");
  }

  function getportrsi(){
   $request = \Slim\Slim::getInstance()->request();
   $data = json_decode($request->getBody());  
   debugLog("===================== Inside getportrsi ============================ \n");
   $user_id = $data->user_id;
   $stock = $data->stock;
   $currentdate = date('Y-m-d H:i:s');
   $curdate=strtotime($currentdate);
   debugLog("Data => ".$request->getBody());
   try {
    $db = getDB();
    $sql="SELECT * FROM `rsi_data` WHERE symbol ='".$stock."' order by timestamp desc limit 3";
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR EMAIL RESET  \n");
        // $custData = $stmt->fetch(PDO::FETCH_OBJ);    
    if ($mainCount > 0) {
     $custData = $stmt->fetchAll(PDO::FETCH_OBJ);
   }
   $myData = "success";
   $msgchat = json_encode(['msg'=>$custData]);
   echo '{"Data": ' .$msgchat . '}';
 } catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}
debugLog("======================================= End getportrsi Data ======================================= \n");
}



function rsiportfun($uid,$script){
  /*"======================================= Returns the Portfolio Data Back  =======================================" ;*/

  // $currentdate = date('Y-m-d H:i:s');
  $currentdate = date('Y-m-d 17:00:00');
  $curdate=strtotime($currentdate);
  debugLog(" CUR DATE======>".$curdate." =====>\n");
  $arr = array(); 
  /*  $currentdate = date('Y-m-d 17:00:00');
  $curdate = strtotime($currentdate);*/
  debugLog("======================================= START Get RSIData ======================================= \n");
  try {
    $db = getDB();
    $k=0;
    $lowval=0;
    $flag=0;
    $sql="SELECT * FROM `rsi_data` WHERE symbol ='".$script."' order by timestamp desc limit 3";
    debugLog($sql."\n");
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mainCount=$stmt->rowCount();
    debugLog($mainCount."ACTUAL COUNT FOR EMAIL RESET  \n");
    if ($mainCount > 0) {
      while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
       if($k==0){
         $cmsgdata->rsi = 55;
         if($cmsgdata->rsi < 57 ){
           $flag=1;
           $lowval=getdaylowdata($cmsgdata->rsi,$cmsgdata->symbol,$cmsgdata->timestamp);
           debugLog("IF COUNT HERE ");      
           $finaldata[] = $cmsgdata->rsi;
         }else{
           $finaldata[] = $cmsgdata->rsi;
         }
       }else{
         $finaldata[] = $cmsgdata->rsi;
       }  
       $k++;
     }
   }
   debugLog("Data here===>".json_encode($finaldata));
 } catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}
$arr = [$flag,$finaldata,$lowval];
  //var_dump($arr);
debugLog("======================================= End Get RSIData ======================================= \n");
return  $arr;
}


function getdaylowdata($rsi,$symbol,$date){
 $db = getDB();
 $sell = 0;
 $flag = 0;
 debugLog("======================================= Start getdaylowdata ======================================= \n");
 $sql="SELECT * FROM `nse_bhavcopy` WHERE symbol ='".$symbol."' and timestamp ='".$date."'";  
 debugLog("SQL HERE ===>".$sql);
 $stmt = $db->prepare($sql);
 $stmt->execute();
 $mainCount=$stmt->rowCount();
 debugLog($mainCount."ACTUAL COUNT FOR EMAIL RESET  \n");
 if ($mainCount > 0) {
  while($cmsgdata = $stmt->fetch(PDO::FETCH_OBJ)){
    $sell = $cmsgdata->low;
  }
  $flag=1;
}
debugLog("Sell Value => ".$sell);
return $sell;
}


function sendpush(){
 $request = \Slim\Slim::getInstance()->request();
 $data = json_decode($request->getBody());  
 debugLog("===================== Start SendPush API ============================ \n");
 debugLog("Data => ".$request->getBody());
 $user_id = $data->user_id;
 $title = $data->push_title;
 $msg = $data->push_msg;
 try {
  $db = getDB();
  sendpushnotif($title,$msg);
  $myData = "success";
  $msgchat = json_encode(['response'=>$myData]);
  echo '{"Data": ' .$msgchat . '}';
} catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
}

}
?>
